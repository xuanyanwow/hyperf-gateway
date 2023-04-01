<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\business\BusinessConnectMessage;
use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\ConnectRegisterTrait;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

class BusinessWorker extends BaseWorker
{
    use ConnectRegisterTrait;

    protected static array $gateways = [];

    // private static Client $registerClient;

    public function __construct(
        public int $workerNumber = 1,
        public string $registerAddress = '127.0.0.1',
        public int $registerPort = 1236,
        public int $registerConnectTimeout = 3,
        public int $pingInterval = 30,
        public string $secretKey = '',
        public int $pingRegisterInterval = 3,
    ) {
    }

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
        go(function () {
            $this->connectRegister();
            $this->onStart();
        });
    }

    protected function onRegisterConnect($client)
    {
        // 发送secretKey - business 不关注 ip
        $client->send(new ConnectMessage('', ConnectMessage::TYPE_BUSINESS, $this->secretKey));
    }

    protected function onRegisterReceive($client, $data)
    {
        if ($data['class'] ?? '' == GatewayInfoMessage::CMD) {
            $this->connectGateway($data['list'] ?? []);
            $this->waitGateway();
            return;
        }

        var_dump($data);
    }

    private function connectGateway($addressList)
    {
        foreach ($addressList as $fd => $address) {
            if (isset(self::$gateways[$address])) {
                continue;
            }
            $client = new Client(SWOOLE_SOCK_TCP);

            $addressMap = explode(':', $address);

            if (! $client->connect($addressMap[0], $addressMap[1], 3)) {
                echo "business连接gateway失败. Error: {$client->errCode}\n";
                continue;
            }
            // send "I am a business worker and wait receive message from gateway"
            $client->send(new BusinessConnectMessage('', 'ok'));
            $data = $client->recv(10);
            if (! empty($data)) {
                $data = json_decode($data, true);
                if ($data['class'] == SuccessMessage::CMD) {
                    self::$gateways[$address] = $client;
                    continue;
                }
                // 连接gateway 响应错误
                var_dump('连接gateway 响应错误');
                var_dump($data);
            }
        }
    }

    /**
     * 监听gateway的任务分发.
     * @return mixed
     */
    private function waitGateway()
    {
        while (true) {
            /** @var Client $client */
            foreach (self::$gateways as $address => $client) {
                $data = $client->recv(0.1);
                if (! empty($data)) {
                    $data = json_decode($data, true);
                    if (! empty($data)) {
                        if ($data['class'] == PingMessage::CMD) {
                            continue;
                        }
                        go(function () use ($client, $data) {
                            $this->onMessage($client->exportSocket()->fd, $data);
                        });
                    }
                }
            }

            Coroutine::sleep(0.01);
        }
    }
}
