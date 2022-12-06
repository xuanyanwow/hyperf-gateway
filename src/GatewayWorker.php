<?php
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\worker\TcpServerTrait;
use Swoole\Coroutine;

class GatewayWorker implements WorkerInterface
{

    public function onStart(): void
    {
        echo "\n====================\ngateway start \n====================\n\n";

        // 上报register
        $this->connectRegister();
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
    }

    public function onClose($fd): void
    {
        echo "gateway worker close\n";
    }

    public function start($daemon = false)
    {
        // 读取配置文件，监听端口

        $server = new \Swoole\Server('0.0.0.0', 9501, SWOOLE_BASE, SWOOLE_SOCK_TCP);
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

    private function connectRegister()
    {
        $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);

        if (!$client->connect('0.0.0.0', 1236, 1)) {
            echo "connect failed. Error: {$client->errCode}\n";
            return;
        }
        $client->send(new ConnectMessage('0.0.0.0:9501', ConnectMessage::TYPE_GATEWAY));
        // $client->close();
        while(1){
            // echo $client->recv();
            Coroutine::sleep(3);
        }
    }
}