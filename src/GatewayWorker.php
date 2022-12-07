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

    public function onStart(): void
    {
        echo "\n====================\ngateway start \n====================\n\n";

        // 上报register
        $this->connectRegister();

        // 维持business心跳和客户端心跳
        $this->heartClient();
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