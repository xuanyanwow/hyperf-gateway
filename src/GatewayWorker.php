<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\business\BusinessConnectMessage;
use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\Connection;
use Friendsofhyperf\Gateway\worker\ConnectRegisterTrait;
use Swoole\Coroutine;

class GatewayWorker extends BaseWorker
{
    use ConnectRegisterTrait;

    /** 工作进程(含client短暂连接) */
    protected static array $businesses = [];

    /** 客户端 */
    protected static array $clients = [];

    protected int $startTime;

    private \Swoole\Server $server;

    public function __construct(
        public string $listenIp = '127.0.0.1',
        public int $listenPort = 9501,
        public string $lanIp = '127.0.0.1',
        public int $lanPort = 9502,
        public int $workerNumber = 1,
        public string $registerAddress = '127.0.0.1',
        public int $registerPort = 1236,
        public int $registerConnectTimeout = 3,
        public int $pingInterval = 10,
        public string $secretKey = '',
        public int $pingRegisterInterval = 3,
    ) {
    }

    public function onStart(int $workerId): void
    {
        if ($workerId === 0) {
            echo "\n====================\ngateway start \n====================\n\n";
            // 上报register
            $this->connectRegister();
        }

        $this->startTime = time();
        // 如果有设置心跳，则定时执行
        if ($this->pingInterval > 0) {
            $this->heartClient();
        }

        // 如果BusinessWorker ip不是127.0.0.1，则需要加gateway到BusinessWorker的心跳
        if ($this->lanIp !== '127.0.0.1') {
            $this->heartBusiness();
        }
    }

    public function onConnect($fd): void
    {
        $clientInfo = $this->server->getClientInfo($fd);
        $connection = new Connection($clientInfo);

        // 保存该连接的内部通讯的数据包报头，避免每次重新初始化
        $connection->gatewayHeader = [
            'local_ip' => ip2long($this->lanIp),
            'local_port' => $this->lanPort,
            'client_ip' => ip2long($clientInfo['remote_ip']),
            'client_port' => $clientInfo['remote_port'],
            'gateway_port' => $this->listenPort,
            'connection_id' => $fd,
            'flag' => 0,
        ];

        // 保存客户端连接 connection 对象
        self::$clients[$fd] = $connection;

        // 让 business 执行业务层的 onConnect 回调
        $this->sendToWorker('client connect', $connection);
    }

    public function onMessage($fd, $revData)
    {
        $connection = self::$clients[$fd];
        $this->sendToWorker('client message', $connection, $revData);
    }

    public function onClose($fd): void
    {
        /** @var Connection $connection */
        $connection = self::$clients[$fd];
        $connection->pingNotResponseCount = -1;
        // 尝试通知 worker，触发 Event::onClose
        $this->sendToWorker('client close', $connection);
    }

    public function start($daemon = false)
    {
        // gateway进程对外开放
        // 如为公网IP监听，直接换成0.0.0.0 ，否则用内网IP
        $listenIp = filter_var($this->listenIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            ? '0.0.0.0'
            : $this->listenIp;

        $this->server = $server = new \Swoole\Server($listenIp, $this->listenPort, SWOOLE_BASE, SWOOLE_SOCK_TCP);

        $this->startInternalServer();

        $server->set([
            'worker_num' => $this->workerNumber,
            'daemonize' => $daemon,
            'enable_coroutine' => true,
        ]);

        $server->on('WorkerStart', function (\Swoole\Server $server, int $workerId) {
            $this->onStart($workerId);
        });
        $server->on('connect', function (\Swoole\Server $server, $fd) {
            $this->onConnect($fd);
        });
        $server->on('receive', function (\Swoole\Server $server, $fd, $reactor_id, $data) {
            $response = $this->onMessage($fd, $data);
            // if (! empty($response)) {
            //     $server->send($fd, $response);
            // }
        });
        $server->on('close', function (\Swoole\Server $server, $fd) {
            $this->onClose($fd);
        });

        $server->start();
    }

    public static function routerBind($businesses, Connection $connection, $cmd, $buffer)
    {
        if (! isset($connection->businessworkerAddress) || ! isset($businesses[$connection->businessworkerAddress])) {
            $connection->businessworkerAddress = array_rand($businesses);
        }
        return $businesses[$connection->businessworkerAddress];
    }

    protected function sendToWorker($cmd, Connection $connection, $body = '')
    {
        if (empty(self::$businesses)) {
            // 没有可用的 worker
            // gateway 启动后 1-2 秒内 SendBufferToWorker fail 是正常现象，因为与 worker 的连接还没建立起来，
            // 所以不记录日志，只是关闭连接
            $time_diff = 2;
            if (time() - $this->startTime >= $time_diff) {
                $msg = 'SendBufferToWorker fail. The connections between Gateway and BusinessWorker are not ready. See http://doc2.workerman.net/send-buffer-to-worker-fail.html';
                echo $msg . PHP_EOL;
            }
            // $connection->destroy();
            return false;
        }

        // TODO 换成message 类
        $gatewayData = $connection->gatewayHeader;
        $gatewayData['cmd'] = $cmd;
        $gatewayData['body'] = $body;

        // 调用路由函数，选择一个worker把请求转发给它
        /** @var int $businessFd */
        $businessFd = call_user_func([$this, 'routerBind'], self::$businesses, $connection, $cmd, $body);

        if ($this->server->send($businessFd, json_encode($gatewayData)) === false) {
            $msg = 'SendBufferToWorker fail. May be the send buffer are overflow. See http://doc2.workerman.net/send-buffer-overflow.html';
            echo $msg . PHP_EOL;
            return false;
        }
        return true;
    }

    protected function startInternalServer()
    {
        $internalPort = $this->server->listen($this->lanIp, $this->lanPort, SWOOLE_SOCK_TCP);

        $internalPort->on('connect', function (\Swoole\Server $server, $fd) {
            $this->onInternalConnect($server, $fd);
        });

        $internalPort->on('close', function (\Swoole\Server $server, $fd) {
            $this->onInternalClose($server, $fd);
        });

        $internalPort->on('receive', function (\Swoole\Server $server, $fd, $reactor_id, $data) {
            $this->onInternalReceive($server, $fd, $reactor_id, $data);
        });
    }

    // onInternalClose
    protected function onInternalClose($server, $fd)
    {
        var_dump('有 business 从gateway断开了');
        var_dump($this->server->getClientInfo($fd));
        if (isset(self::$businesses[$fd])) {
            unset(self::$businesses[$fd]);
        }
    }

    // onInternalReceive
    protected function onInternalReceive($server, $fd, $reactor_id, $revData)
    {
        var_dump('gateway内部连接收到消息');
        // 转发到business
        var_dump($revData);
        $revData = json_decode($revData, true);
        if (empty($revData) && empty($revData['class'])) {
            return false;
        }

        switch ($revData['class']) {
            case BusinessConnectMessage::CMD:
                self::$businesses[$fd] = $fd;
                return new SuccessMessage('business connected');
            default:
                break;
        }
    }

    protected function onRegisterConnect($client)
    {
        $client->send(new ConnectMessage(join(':', [
            $this->lanIp,
            $this->lanPort, // 内部端口
        ]), ConnectMessage::TYPE_GATEWAY, $this->secretKey));
    }

    protected function onRegisterClose()
    {
        var_dump('检测到 register 断开');
        // 可能要断线重连
    }

    protected function onInternalConnect($server, $fd)
    {
    }

    protected function onRegisterReceive($client, $data)
    {
        var_dump($data);
        // TODO 最复杂的部分
    }

    /**
     * 维持business心跳和客户端心跳.
     */
    private function heartClient()
    {
        go(function () {
            while (true) {
                var_dump('维持客户端心跳');
                // 遍历所有客户端连接
                foreach (self::$clients as $fd => $connection) {
                    // 上次发送的心跳还没有回复次数大于限定值就断开
                    // if ($this->pingNotResponseLimit > 0
                    //     && $connection->pingNotResponseCount >= $this->pingNotResponseLimit * 2
                    // ) {
                    //     $connection->destroy();
                    //     continue;
                    // }

                    // $connection->pingNotResponseCount 为 -1 说明最近客户端有发来消息，则不给客户端发送心跳
                    ++$connection->pingNotResponseCount;
                    if ($connection->pingNotResponseCount === 0
                        // || ($this->pingNotResponseLimit > 0 && $connection->pingNotResponseCount % 2 === 1)
                    ) {
                        continue;
                    }

                    $this->server->send($fd, new PingMessage());
                }
                Coroutine::sleep($this->pingInterval);
            }
        });
    }

    private function heartBusiness()
    {
        go(function () {
            while (true) {
                var_dump('维持business心跳');
                foreach (self::$businesses as $fd) {
                    $this->server->send($fd, new PingMessage());
                }
                Coroutine::sleep(3);
            }
        });
    }
}
