<?php
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\worker\TcpServerTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Server\Connection;
use Swoole\Process;

class GatewayWorker implements WorkerInterface
{
    use TcpServerTrait;

    public function onStart(): void
    {
        echo "\n====================\ngateway start \n====================\n\n";

        // 读取配置文件，监听端口
        // 上报register
        go(function (){
            $this->connectRegister();
        });

        $server = new \Swoole\Coroutine\Server('127.0.0.1', 9501, false, true);

        Process::signal(SIGTERM, function () use ($server) {
            $server->shutdown();
        });

        $server->handle(function (Connection $conn) {
            $this->tcpServerHandle($conn);
        });

        //开始监听端口
        $server->start();
    }

    public function onConnect( $clientId): void
    {
        echo "gateway worker connect\n";
        // 转发给business
    }

    public function onMessage( $clientId,  $revData): void
    {
        echo "gateway worker message\n";
        // 转发到business
    }

    public function onClose($conn): void
    {
        echo "gateway worker close\n";
    }

    public function start($daemon = false)
    {
        $process = new Process(function(){
            (new GatewayWorker)->onStart();
        });
        $process->set([
            'enable_coroutine' => true
        ]);
        if ($daemon){
            $process->daemon();
        }
        $process->start();
        $process->name('hyperf:gateway');

        if (!$daemon){
            $process->wait();
        }
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
            Coroutine::sleep(0.01);
            var_dump($client);
        }
    }
}