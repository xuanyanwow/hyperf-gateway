<?php
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\business\BusinessConnectMessage;
use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\ConnectRegisterTrait;
use Friendsofhyperf\Gateway\worker\LogTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

class BusinessWorker implements WorkerInterface
{
    use ConnectRegisterTrait;
    use LogTrait;

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
        run(function(){
            $this->connectRegister();
            $this->onStart();
        });
        \Swoole\Event::wait();
    }


    protected function onRegisterConnect($client)
    {
        $client->send(new ConnectMessage('business的ip', ConnectMessage::TYPE_BUSINESS));
    }

    protected function onRegisterReceive($client, $data): bool
    {
        if ($data['class'] ?? '' == GatewayInfoMessage::CMD) {
            self::$registerClient = $client;

            $this->connectGateway($data['list']);
            $this->heartRegister();
            return true;
        }

        var_dump("register receive");
        var_dump($data);

        return false;
    }

    private function connectGateway($addressList)
    {
        foreach ($addressList as $address => $fd){
            if (isset(self::$gateways[$address])){
                continue;
            }
            //  需要开协程去连接，在协程里wait任务 不然会造成堵塞问题
            \Hyperf\Engine\Coroutine::create(function() use($address) {
                $client = new Client(SWOOLE_SOCK_TCP);

                var_dump("尝试连接geteway". $address);
                $addressMap = explode(':', $address);

                if (!$client->connect($addressMap[0], $addressMap[1], 3)) {
                    echo "business连接gateway失败. Error: {$client->errCode}\n";
                    return;// TODO 这里连接一次就停止 看看是否需要持续尝试
                }
                var_dump("建立gateway连接");

                // send "I am a business worker and wait receive message from gateway"
                $r = $client->send(new BusinessConnectMessage("测试worker ip", "ok"));
                var_dump($r);
                $data = $client->recv(10);
                var_dump($data);
                if (!empty($data)){
                    $data = json_decode($data, true);
                    if ($data['class'] != SuccessMessage::CMD){
                        return;
                    }

                    self::$gateways[$address] = $client;
                    $this->waitGateway($client);
                }else{
                    // 连接gateway 响应错误
                    var_dump("连接gateway 响应超时");
                    var_dump($data);
                }

            });
        }
    }

    /**
     * 监听gateway的任务分发
     */
    private function waitGateway(Client $client)
    {
        while (true){
            $data = $client->recv(0.1);

            // TODO 这里怎么捕获到gateway断开 尝试重连

            if (!empty($data)){
                $data = json_decode($data, true);
                if (!empty($data)){
                    if ($data['class'] == PingMessage::CMD) {
                        self::info("BusinessWorker", "waitGateway", "收到gateway发来的心跳");
                        continue;
                    }
                    // 每一条任务消息都开协程处理
                    \Hyperf\Engine\Coroutine::create(function() use ($client, $data){
                        $this->onMessage($client->exportSocket()->fd, $data);
                    });
                }
            }
            \Hyperf\Utils\Coroutine::sleep(0.01);
        }
    }
}