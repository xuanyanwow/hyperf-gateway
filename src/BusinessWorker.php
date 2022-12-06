<?php
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Swoole\Coroutine;
use Swoole\Process;

class BusinessWorker implements WorkerInterface
{
    protected static array $gateways = [];

    public function onStart(): void
    {
        echo "business worker start\n";
        // 读取register信息
        // 连接gateway
    }

    public function onConnect($fd)
    {
        return false;
    }

    public function onMessage($fd, $revData)
    {
        echo "business worker message\n";
        // 解析消息 调用逻辑
    }

    public function onClose($fd)
    {
        return false;
    }

    public function start($daemon = false)
    {
        go(function(){
            $this->connectRegister();
        });
    }

    private function connectRegister()
    {
        $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);

        if (!$client->connect('0.0.0.0', 1236, 1)) {
            echo "connect failed. Error: {$client->errCode}\n";
            return;
        }
        $client->send(new ConnectMessage('3232', ConnectMessage::TYPE_BUSINESS));
        // $client->close();
        while(true){
            $data = $client->recv();
            // 保存gateway信息
            if (!empty($data)){
                $data = json_decode($data, true);
                if (isset($data['class']) && $data['class'] == GatewayInfoMessage::CMD){
                    self::$gateways[] = $data['list'];
                }
            }
            Coroutine::sleep(3);
        }
    }
}