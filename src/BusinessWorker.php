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
use Friendsofhyperf\Gateway\worker\TcpClient;
use Swoole\Coroutine\Client;

class BusinessWorker extends BaseWorker
{
    use ConnectRegisterTrait;

    /** 连接通讯中的 gateway client */
    protected static array $gateways = [];

    /** 正在尝试连接的 gateway address */
    protected array $gatewayConnecting = [];

    /** register返回的gateway地址数组 用于校验是否需要重连 */
    protected array $gatewayAddresses = [];

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

    public function onStart(int $workerId): void
    {
        echo "\n====================\nbusiness start \n====================\n\n";

        $this->connectRegister();
    }

    public function onConnect($fd)
    {
        return false;
    }

    public function onMessage($fd, $revData)
    {
        self::debug('business worker message', $fd, $revData);
    }

    public function onClose($fd)
    {
        return false;
    }

    public function start($daemon = false)
    {
        go(function () {
            $this->onStart(0);
        });
    }

    public function onRegisterConnect($client)
    {
        // 发送secretKey - business 不关注 ip
        $client->send(new ConnectMessage('', ConnectMessage::TYPE_BUSINESS, $this->secretKey));
    }

    public function onRegisterReceive($client, $data)
    {
        $data = json_decode($data, true);

        $cmd = $data['class'] ?? '';

        switch ($cmd) {
            case GatewayInfoMessage::CMD:
                // feature 多个register中心  地址要合并 不能覆盖
                $this->gatewayAddresses = $data['list'] ?? [];
                $this->connectGateway($data['list'] ?? []);
                return;
            default:
                echo "Receive bad cmd:{$cmd} from Register.\n";
                break;
        }
    }

    public function onGatewayConnect($client)
    {
        self::debug('business worker onGatewayConnect', $client->getAddressWithPort());
        $client->send(new BusinessConnectMessage('', 'ok'));
    }

    public function onGatewayMessage(TcpClient $client, $data)
    {
        $data = json_decode($data, true);

        $class = $data['class'];
        switch ($class) {
            case SuccessMessage::CMD:
                $address = $client->getAddressWithPort();
                self::$gateways[$address] = $client;
                return;
            case PingMessage::CMD:
                return;
        }

        self::debug('business worker onGatewayMessage', $data);

        // TODO 最复杂的转发逻辑
    }

    public function onGatewayClose(TcpClient $client)
    {
        $address = $client->getAddressWithPort();
        unset(self::$gateways[$address], $this->gatewayConnecting[$address]);

        self::debug('business worker onGatewayClose', $address);

        // 不能直接重连，要判断一下地址是否还有效(register返回)
        if (isset($this->gatewayAddresses[$address])) {
            self::debug('business worker onGatewayClose 重连', $address);
            $client->reconnect(1);
        }
    }

    /**
     * 获取所有的gateway地址.
     */
    public function getGatewayAddresses()
    {
        return $this->gatewayAddresses;
    }

    private function connectGateway($addressList)
    {
        if (empty($addressList)) {
            return;
        }

        foreach ($addressList as $fd => $address) {
            if (! isset($this->gatewayConnecting[$address])) {
                $this->tryConnectGateway($address);
            }
        }
    }

    private function tryConnectGateway($address)
    {
        if (isset(self::$gateways[$address])) {
            self::debug('已经连接过了' . $address);
            unset($this->gatewayConnecting[$address]);
            return;
        }

        self::debug('尝试连接' . $address);
        $this->gatewayConnecting[$address] = true;

        $addressMap = explode(':', $address);
        $client = new TcpClient($addressMap[0], $addressMap[1], 3);

        // onGatewayConnect
        $client->setOnConnect([$this, 'onGatewayConnect']);

        // onGatewayMessage
        $client->setOnMessage([$this, 'onGatewayMessage']);

        // onGatewayClose
        $client->setOnClose([$this, 'onGatewayClose']);

        if (! $client->connect()) {
            self::debug("business连接gateway失败. Error: {$client->errCode}");
            return;
        }
        self::debug('business连接gateway成功' . $address);
    }
}
