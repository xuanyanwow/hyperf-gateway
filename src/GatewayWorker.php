<?php
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\business\BusinessConnectMessage;
use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\ConnectRegisterTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

class GatewayWorker implements WorkerInterface
{
    use ConnectRegisterTrait;

    private static Client $registerClient;

    protected static array $businesses = [];

    private \Swoole\Server $server;

    private false|\Swoole\Server\Port $_innerTcpWorker;

    public function onStart(): void
    {
        echo "\n====================\ngateway start \n====================\n\n";

        // TODO 开启内部监听，用于监听 worker 的连接已经连接上发来的数据
        $this->_innerTcpWorker = $this->server->listen("0.0.0.0", "lanPort", SWOOLE_SOCK_TCP);
        $this->_innerTcpWorker->set([]);
        $this->_innerTcpWorker->on('connect', [$this, 'onWorkerConnect']);
        $this->_innerTcpWorker->on('receive', [$this, 'onWorkerConnect']);
        $this->_innerTcpWorker->on('close', [$this, 'onWorkerConnect']);


        // 上报register
        $this->connectRegister();

        // 维持business心跳和客户端心跳
        $this->heartClient();

        // TODO 执行用户定义的onStart回调
    }

    public function onConnect($fd): void
    {
        echo "gateway worker connect\n";
        // TODO

        // 保存该连接的内部通讯的数据包报头，避免每次重新初始化
        // 保存客户端连接 connection 对象
        // $this->_clientConnections[$connection->id] = [];
        //
        // // 如果用户有自定义 onConnect 回调，则执行
        // if ($this->_onConnect) {
        //     call_user_func($this->_onConnect, $connection);
        //     if (isset($connection->onWebSocketConnect)) {
        //         $connection->_onWebSocketConnect = $connection->onWebSocketConnect;
        //     }
        // }
        // if ($connection->protocol === '\Workerman\Protocols\Websocket' || $connection->protocol === 'Workerman\Protocols\Websocket') {
        //     $connection->onWebSocketConnect = array($this, 'onWebsocketConnect');
        // }

        // 转发给BusinessWorker进程
        // $this->sendToWorker(C::CMD_ON_CONNECT, $connection);
    }

    public function onMessage($fd, $revData)
    {
        echo "gateway worker message\n";
        // 全量转发到business即可
        // $this->sendToBusiness();

        // TODO 下面应该是onWorkerMessage里的
        var_dump($revData);
        $revData = json_decode($revData, true);
        if (empty($revData) && empty($revData['class'])) return false;

        switch ($revData['class']) {
            case BusinessConnectMessage::CMD:
                self::$businesses[$fd] = $fd;
                return new SuccessMessage("business connected");

            default :
                break;
        }

        return false;
    }

    public function onClose($fd): void
    {
        var_dump('有人从gateway断开了');
        if (isset(self::$businesses[$fd])) unset(self::$businesses[$fd]);

        // TODO
        // 清理 uid 数据
        // 清理 group 数据
        // 触发 onClose
    }

    public function start($daemon = false)
    {
        // 读取配置文件，监听端口

        $this->server = $server = new \Swoole\Server('0.0.0.0', 9501, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $server->set([
            'worker_num' => 1,
            'daemonize' => $daemon,
            'enable_coroutine' => true,
        ]);

        $server->on('WorkerStart', function(\Swoole\Server $server, int $workerId) {
            go(function(){
                $this->onStart();
            });
        });
        $server->on('connect', function ($server, $fd){
            $this->onConnect($fd);
        });
        $server->on('receive', function (\Swoole\Server $server, $fd, $reactor_id, $data) {
            $response = $this->onMessage($fd,$data);
            if (!empty($response)){
                $server->send($fd, $response);
            }
        });
        $server->on('close', function ($server, $fd) {
            $this->onClose($fd);
        });
        $server->start();
    }

    protected function onRegisterConnect($client)
    {
        $client->send(new ConnectMessage('127.0.0.1:9501', ConnectMessage::TYPE_GATEWAY));
    }

    protected function onRegisterReceive($client, $data)
    {
        if ($data['class'] ?? '' == SuccessMessage::CMD){
            self::$registerClient = $client;
            return true;
        }

        return false;
    }

    /**
     * 维持business心跳和客户端心跳
     *
     * @return void
     */
    private function heartClient()
    {
        go(function(){
            while (true) {
                var_dump("维持business心跳和客户端心跳");
                foreach (self::$businesses as $fd) {
                    $this->server->send($fd, new PingMessage());
                }
                Coroutine::sleep(3);
            }
        });

    }
}