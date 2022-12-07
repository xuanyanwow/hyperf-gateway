<?php
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\business\BusinessConnectMessage;
use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\ConnectRegisterTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

class BusinessWorker implements WorkerInterface
{
    use ConnectRegisterTrait;

    protected static array $gateways = [];
    private static Client $registerClient;

    public function onStart(): void
    {
        echo "\n====================\nbusiness start \n====================\n\n";

    }

    public function onConnect($fd)
    {
        return false;
    }

    public function onMessage($fd, $revData)
    {
        echo "business worker message\n";
        // 解析消息 调用逻辑
        var_dump($fd);
        var_dump($revData);
    }

    public function onClose($fd)
    {
        return false;
    }

    public function start($daemon = false)
    {
        go(function(){
            $this->connectRegister();
            $this->onStart();
        });
    }


    protected function onRegisterConnect($client)
    {
        $client->send(new ConnectMessage('business的ip', ConnectMessage::TYPE_BUSINESS));
    }

    protected function onRegisterReceive($client, $data)
    {
        if ($data['class'] ?? '' == GatewayInfoMessage::CMD) {
            self::$registerClient = $client;

            $this->connectGateway($data['list']);
            $this->heartRegister();
            $this->waitGateway();
            return false;// business不能跳出循环 要等待gateway的任务分发
        }

        var_dump($data);

        return false;
    }

    private function connectGateway($addressList)
    {
        foreach ($addressList as $address => $fd){
            if (isset(self::$gateways[$address])){
                continue;
            }
            $client = new Client(SWOOLE_SOCK_TCP);

            $addressMap = explode(':', $address);

            if (!$client->connect($addressMap[0], $addressMap[1], 3)) {
                echo "business连接gateway失败. Error: {$client->errCode}\n";
                continue;
            }
            // send "I am a business worker and wait receive message from gateway"
            $client->send(new BusinessConnectMessage("测试worker ip", "ok"));
            $data = $client->recv(10);
            if (!empty($data)){
                $data = json_decode($data, true);
                if ($data['class'] == SuccessMessage::CMD){
                    self::$gateways[$address] = $client;
                    continue;
                }
                // 连接gateway 响应错误
                var_dump("连接gateway 响应错误");
                var_dump($data);
            }
        }
    }

    /**
     * 监听gateway的任务分发
     * @return mixed
     */
    private function waitGateway()
    {
        while (true){
            /** @var Client $client */
            foreach (self::$gateways as $address => $client){
                $data = $client->recv(0.1);
                if (!empty($data)){
                    $data = json_decode($data, true);
                    if (!empty($data)){
                        if ($data['class'] == PingMessage::CMD) continue;
                        go(function() use ($client, $data){
                            $this->onMessage($client->exportSocket()->fd, $data);
                        });
                    }
                }
            }

            Coroutine::sleep(0.01);
        }
    }
}