<?php
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\TcpServerTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Server\Connection;
use Swoole\Process;

class Register implements WorkerInterface
{
    use TcpServerTrait;

    protected array $gateways = [];

    public function onStart(): void
    {
        echo "\n====================\nregister start \n====================\n\n";

        // 读取配置文件，监听端口

        $server = new \Swoole\Coroutine\Server('0.0.0.0', 1236, false, true);

        Process::signal(SIGTERM, function () use ($server) {
            $server->shutdown();
        });

        $server->handle(function (Connection $conn) {
            $this->tcpServerHandle($conn);
        });

        $server->start();
    }

    public function onConnect($clientId): void
    {
    }

    public function onMessage($conn,  $revData)
    {
        $revData = json_decode($revData, true);
        if (!isset($revData['class'])) return false;

        switch ($revData['class']) {
            // 区分是gateway还是business
            // gateway存起来
            // business直接返回所有gateway信息
            case ConnectMessage::class:
                if ($revData['type'] === ConnectMessage::TYPE_GATEWAY){
                    $this->gateways[$revData['ip']] = $conn;
                    return new SuccessMessage("register connected");
                }else if ($revData['type'] === ConnectMessage::TYPE_BUSINESS){
                    return new GatewayInfoMessage($this->gateways);
                }
                break;

            default :
                break;
        }
    }

    public function onClose($conn): void
    {
        // 区分是gateway还是business
        // gateway删除 通知所有business
        var_dump($conn);
        var_dump('register 中有人断开');

        // if (!empty($this->gateways[$clientId])){ unset($this->gateways[$clientId]); };
    }

    public function start($daemon = false)
    {
        $process = new Process(function(){
            (new Register)->onStart();
        });
        $process->set([
            'enable_coroutine' => true
        ]);
        if ($daemon){
            $process->daemon();
        }

        $process->name('hyperf:gateway-register');
        $process->start();

        if (!$daemon){
            $process->wait();
        }
    }

    private function heartBeat(Connection $conn)
    {
        // 每隔一段时间发送心跳
        $conn->send('heartbeat');
    }
}