<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Swoole\Timer;

class Register extends BaseWorker
{
    protected array $gateways = [];

    protected array $businessWorkerConnections = [];

    protected \Swoole\Server $server;

    /** fd => 验证超时定时器 ID */
    private $authTimeoutTimerMap = [];

    public function __construct(
        public string $registerAddress = '0.0.0.0',
        public int $registerPort = 1236,
        public string $secretKey = '',
    ) {
    }

    public function onStart(int $workerId): void
    {
        self::log("\n====================\nregister start \n====================");
    }

    public function onConnect($fd): void
    {
        // 要求10秒内发送验证
        $this->authTimeoutTimerMap[$fd] = Timer::after(1000, function ($fd) {
            $connection = $this->server->getClientInfo($fd);
            self::log('Register auth timeout (' . $connection['remote_ip'] . '). See http://doc2.workerman.net/register-auth-timeout.html');
        // $this->server->close($fd);
        }, $fd);
    }

    public function onMessage($fd, $revData)
    {
        $this->clearTimer($fd);

        $connection = $this->server->getClientInfo($fd);
        $originRevData = $revData;

        $revData = trim($revData);
        $revData = json_decode($revData, true);
        if (! isset($revData['cmd'])) {
            return false;
        }
        var_dump($revData);

        switch ($revData['cmd']) {
            // 区分是gateway还是business
            // gateway存起来
            // business直接返回所有gateway信息
            case ConnectMessage::CMD:
                $secretKey = $revData['secretKey'] ?? '';
                if ($secretKey !== $this->secretKey) {
                    self::log("Register secret_key error IP: {$connection['remote_ip'] } Error Key: {$secretKey} .");
                    $this->server->close($fd);
                    return;
                }

                if ($revData['type'] === ConnectMessage::TYPE_GATEWAY) {
                    if (empty($revData['ip'])) {
                        self::log('address not found.');
                        $this->server->close($fd);
                        return;
                    }
                    $this->gateways[$fd] = $revData['ip'];

                    $this->broadcastAddresses();

                    return new SuccessMessage('register connected');
                }
                // business 不关注客户端 ip
                if ($revData['type'] === ConnectMessage::TYPE_BUSINESS) {
                    $this->businessWorkerConnections[$fd] = $fd;
                    return new GatewayInfoMessage($this->gateways);
                }

                break;
            case PingMessage::CMD:
                return;
            default:
                self::log("Register unknown event:{$revData['cmd']} IP: {$connection['remote_ip'] } .");
                break;
        }
    }

    public function onClose($fd): void
    {
        $this->clearTimer($fd);
        // 区分是gateway还是business
        // gateway删除 通知所有business
        self::debug('register 中有人断开' . $fd);

        if (! empty($this->gateways[$fd])) {
            unset($this->gateways[$fd]);
            $this->broadcastAddresses();
        }
        if (! empty($this->businessWorkerConnections[$fd])) {
            unset($this->businessWorkerConnections[$fd]);
        }
    }

    public function start($daemon = false)
    {
        $this->server = $server = new \Swoole\Server($this->registerAddress, $this->registerPort, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $server->set([
            'worker_num' => 1,
            'daemonize' => $daemon,
            'enable_coroutine' => true,
        ]);

        $server->on('WorkerStart', function (\Swoole\Server $server, int $workerId) {
            $this->onStart($workerId);
        });
        $server->on('connect', function (\Swoole\Server $server, int $fd, int $reactorId) {
            $this->onConnect($fd);
        });
        $server->on('receive', function (\Swoole\Server $server, int $fd, int $reactor_id, string $data) {
            $response = $this->onMessage($fd, $data);
            if (! empty($response)) {
                $server->send($fd, $response);
            }
        });
        $server->on('close', function ($server, $fd) {
            $this->onClose($fd);
        });
        $server->start();
    }

    /**
     * 广播所有gateway地址.
     * @param null|mixed $connection
     */
    public function broadcastAddresses()
    {
        foreach ($this->businessWorkerConnections as $fd) {
            $this->server->send($fd, new GatewayInfoMessage($this->gateways));
        }
    }

    private function clearTimer($fd)
    {
        if (isset($this->authTimeoutTimerMap[$fd])) {
            Timer::clear($this->authTimeoutTimerMap[$fd]);
            unset($this->authTimeoutTimerMap[$fd]);
        }
    }
}
