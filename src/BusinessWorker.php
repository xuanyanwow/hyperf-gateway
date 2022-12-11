<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\event\BusinessWorkerOnRegisterReceive;
use Friendsofhyperf\Gateway\message\business\BusinessConnectMessage;
use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\ConnectRegisterTrait;
use Friendsofhyperf\Gateway\worker\LogTrait;
use Swoole\Coroutine\Client;

class BusinessWorker implements WorkerInterface
{
    use ConnectRegisterTrait;
    use LogTrait;
    use BusinessWorkerOnRegisterReceive;

    protected static array $gateways = [];

    protected static Client $registerClient;

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
        run(function () {
            $this->connectRegister();
            $this->onStart();
        });
        \Swoole\Event::wait();
    }

    protected function onRegisterConnect($client)
    {
        $client->send(new ConnectMessage('business的ip', ConnectMessage::TYPE_BUSINESS));
    }

    protected function onRegisterReceive($client, $data)
    {
        $this->businessWorkerOnRegisterReceive($client, $data);
    }

    private function connectGateway($addressList)
    {
        foreach ($addressList as $address) {
            if (isset(self::$gateways[$address])) {
                continue;
            }
            // 需要开协程去连接，在协程里wait任务 不然会造成堵塞问题
            // 这里需要记录协程ID，如果同一个IP 反复重连，创建新的之前应该把旧的协程停掉
            \Hyperf\Engine\Coroutine::create(function () use ($address) {
                $client = new Client(SWOOLE_SOCK_TCP);

                $addressMap = explode(':', $address);

                $isConnected = false;
                while (! $isConnected) {
                    if (! $client->connect($addressMap[0], $addressMap[1], 3)) {
                        self::debug('Business', 'connect Gateway Error', $client->errCode);
                        $client->close();
                        \Hyperf\Utils\Coroutine::sleep(3);
                        continue;
                    }
                    $isConnected = true;
                }

                self::debug('Business', 'connect Gateway Success', '');

                // send "I am a business worker and wait receive message from gateway"
                $client->send(new BusinessConnectMessage('测试worker ip', 'ok'));
                $data = $client->recv(10);
                if (! empty($data)) {
                    $data = json_decode($data, true);
                    if ($data['class'] != SuccessMessage::CMD) {
                        return;
                    }

                    self::$gateways[$address] = $client;
                    $this->waitGateway($client, $address);
                } else {
                    self::debug('Business', 'connectGateway', '等待鉴权握手 响应超时');
                }
            });
        }
    }

    /**
     * 监听gateway的任务分发.
     * @param mixed $address
     */
    private function waitGateway(Client $client, $address)
    {
        while (true) {
            $data = $client->recv(3);

            // 这里检测一下gateway列表，如果当前地址已经不在列表内，则代表gateway掉线  退出当前wait
            // register监听到gateway掉线后，会通知给business  删除列表
            if (empty(self::$gateways[$address])) {
                self::debug('Business', 'wait Gateway Job', '检测到gateway已经下线了' . $address);
                break;
            }

            if (! empty($data)) {
                $data = json_decode($data, true);
                if (! empty($data)) {
                    if ($data['class'] == PingMessage::CMD) {
                        self::debug('BusinessWorker', 'waitGateway', '收到gateway发来的心跳');
                        continue;
                    }
                    // 每一条任务消息都开协程处理
                    \Hyperf\Engine\Coroutine::create(function () use ($client, $data) {
                        $this->onMessage($client->exportSocket()->fd, $data);
                    });
                }
            }
            \Hyperf\Utils\Coroutine::sleep(0.01);
        }
    }
}
