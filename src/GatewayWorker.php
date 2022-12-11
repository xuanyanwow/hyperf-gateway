<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\event\GatewayWorkerInnerTCP;
use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\ConnectRegisterTrait;
use Friendsofhyperf\Gateway\worker\LogTrait;
use Swoole\Coroutine\Client;
use Swoole\Server;
use Swoole\Timer;

use const lanIP;
use const lanPort;

class GatewayWorker implements WorkerInterface
{
    use ConnectRegisterTrait;
    use LogTrait;

    protected static array $businesses = [];

    private static Client $registerClient;

    private Server $server;

    private GatewayWorkerInnerTCP $_innerTcp;

    public function onStart(): void
    {
        echo "\n====================\ngateway start \n====================\n\n";

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

    public function onMessage($fd, $revData): mixed
    {
        echo "gateway worker message\n";
        // 全量转发到business即可
        // $this->sendToBusiness();

        return false;
    }

    public function onClose($fd): void
    {
        var_dump('有client从gateway断开了');

        // TODO
        // 清理 uid 数据
        // 清理 group 数据
        // 触发 onClose
    }

    public function start($daemon = false)
    {
        // 读取配置文件，监听端口

        $this->server = $server = new Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $server->set([
            'worker_num' => 2,
            'daemonize' => $daemon,
            'enable_coroutine' => true,
            'hook_flags' => swoole_hook_flags(),
        ]);

        $server->on('WorkerStart', function (Server $server, int $workerId) {
            // swoole进程模型与workerman不一样
            // swoole woker_num设置后，多个进程监听同一个端口，自动分配链接到多个进程上，所以只需要上报register一次就好了
            if ($workerId == 0) {
                $this->onStart();
            }
        });
        $server->on('connect', function ($server, $fd) {
            $this->onConnect($fd);
        });
        $server->on('receive', function (Server $server, $fd, $reactor_id, $data) {
            $response = $this->onMessage($fd, $data);
            if (! empty($response)) {
                $server->send($fd, $response);
            }
        });
        $server->on('close', function ($server, $fd) {
            $this->onClose($fd);
        });

        // 开启内部监听，用于监听 worker 的连接已经连接上发来的数据
        $this->_innerTcp = new GatewayWorkerInnerTCP($this);

        $server->start();
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function addBusiness($fd, $business)
    {
        self::$businesses[$fd] = $business;
    }

    public function delBusiness($fd)
    {
        if (isset(self::$businesses[$fd])) {
            unset(self::$businesses[$fd]);
        }
    }

    protected function onRegisterConnect(Client $client)
    {
        self::debug('Gateway', 'onRegisterConnect', '上报gateway地址');
        $client->send(new ConnectMessage(lanIP . ':' . lanPort, ConnectMessage::TYPE_GATEWAY));
    }

    protected function onRegisterReceive($client, $data)
    {
        if ($data['class'] ?? '' == SuccessMessage::CMD) {
            self::$registerClient = $client;
            return;
        }
    }

    /**
     * 维持business心跳和客户端心跳.
     */
    private function heartClient()
    {
        Timer::tick(3000, function () {
            self::debug('gateway', 'heart', '数量' . count(self::$businesses));
            foreach (self::$businesses as $fd) {
                $this->server->send($fd, new PingMessage());
            }
        });
    }
}
