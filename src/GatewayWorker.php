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
use Friendsofhyperf\Gateway\worker\ConnectRegisterTrait;
use Swoole\Coroutine;

class GatewayWorker extends BaseWorker
{
    use ConnectRegisterTrait;

    protected static array $businesses = [];

    private \Swoole\Server $server;

    public function __construct(
        public string $lanIp = '127.0.0.1',
        public int $lanPort = 9501,
        public int $workerNumber = 1,
        public string $registerAddress = '127.0.0.1',
        public int $registerPort = 1236,
        public int $registerConnectTimeout = 3,
        public int $pingInterval = 30,
        public string $secretKey = '',
        public int $pingRegisterInterval = 3,
    ) {
    }

    public function onStart(): void
    {
        echo "\n====================\ngateway start \n====================\n\n";

        // 上报register
        $this->connectRegister();

        // 维持business心跳和客户端心跳
        // $this->heartClient();
    }

    public function onConnect($fd): void
    {
        echo "gateway worker connect\n";
        // 转发给business
    }

    public function onMessage($fd, $revData)
    {
        echo "gateway worker message\n";
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

        return false;
    }

    public function onClose($fd): void
    {
        var_dump('有人从gateway断开了');
        if (isset(self::$businesses[$fd])) {
            unset(self::$businesses[$fd]);
        }
    }

    public function start($daemon = false)
    {
        // gateway进程对外开放
        $this->server = $server = new \Swoole\Server('0.0.0.0', $this->lanPort, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $server->set([
            'worker_num' => $this->workerNumber,
            'daemonize' => $daemon,
            'enable_coroutine' => true,
        ]);

        $server->on('WorkerStart', function (\Swoole\Server $server, int $workerId) {
            $this->onStart();
        });
        $server->on('connect', function ($server, $fd) {
            $this->onConnect($fd);
        });
        $server->on('receive', function (\Swoole\Server $server, $fd, $reactor_id, $data) {
            $response = $this->onMessage($fd, $data);
            if (! empty($response)) {
                $server->send($fd, $response);
            }
        });
        $server->on('close', function ($server, $fd) {
            $this->onClose($fd);
        });

        $this->startInternalServer();

        $server->start();
    }

    protected function startInternalServer()
    {
        $internalPort = $this->server->listen($this->lanIp, $this->lanPort + 1, SWOOLE_SOCK_TCP);
        $internalPort->on('connect', function ($server, $fd) {
            $this->onInternalConnect($server, $fd);
        });
        echo "gateway internal server setting\n";
    }

    protected function onRegisterConnect($client)
    {
        $client->send(new ConnectMessage(join(':', [
            $this->lanIp,
            $this->lanPort + 1, // 内部端口
        ]), ConnectMessage::TYPE_GATEWAY, $this->secretKey));
    }

    protected function onRegisterClose()
    {
        var_dump('检测到 register 断开');
    }

    protected function onInternalConnect($server, $fd)
    {
        var_dump('gateway内部连接');
    }

    protected function onRegisterReceive($client, $data)
    {
        var_dump($data);
    }

    /**
     * 维持business心跳和客户端心跳.
     */
    private function heartClient()
    {
        go(function () {
            while (true) {
                var_dump('维持business心跳和客户端心跳');
                foreach (self::$businesses as $fd) {
                    $this->server->send($fd, new PingMessage());
                }
                Coroutine::sleep(3);
            }
        });
    }
}
