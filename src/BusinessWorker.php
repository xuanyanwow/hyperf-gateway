<?php
namespace Friendsofhyperf\Gateway;

use Swoole\Process;

class BusinessWorker implements WorkerInterface
{
    protected static array $gateways = [];

    public static function onStart(): void
    {
        echo "business worker start\n";
        // 读取register信息
        // 连接gateway
    }

    public static function onConnect(string $clientId)
    {
        return false;
    }

    public static function onMessage(string $clientId, mixed $revData)
    {
        echo "business worker message\n";
        // 解析消息 调用逻辑
    }

    public static function onClose(string $conn)
    {
        return false;
    }

    public static function start($daemon = false)
    {

        $process = new Process(function(){
            BusinessWorker::onStart();
        });
        $process->set([
            'enable_coroutine' => true
        ]);
        if ($daemon){
            $process->daemon();
        }
        $process->start();
        $process->name('hyperf:business');

        if (!$daemon){
            $process->wait();
        }
    }
}