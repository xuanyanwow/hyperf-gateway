<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Swoole\Timer;

class Register extends BaseWorker
{
    protected array $gateways = [];

    protected \Swoole\Server $server;

    /** fd => 验证超时定时器 ID */
    private $authTimeoutTimerMap = [];

    public function onStart(): void
    {
        echo "\n====================\nregister start \n====================\n\n";

        // 读取配置文件，监听端口
    }

    public function onConnect($fd): void
    {
        // 要求10秒内发送验证
        $this->authTimeoutTimerMap[$fd] = Timer::after(1000, function ($fd) {
            $connection = $this->server->getClientInfo($fd);
            echo 'Register auth timeout (' . $connection['remote_ip'] . '). See http://doc2.workerman.net/register-auth-timeout.html' . PHP_EOL;
            $this->server->close($fd);
        }, $fd);
    }

    public function onMessage($fd, $revData)
    {
        $this->clearTimer($fd);
        $revData = trim($revData);
        $revData = json_decode($revData, true);
        if (! isset($revData['class'])) {
            return false;
        }
        var_dump($revData);

        switch ($revData['class']) {
            // 区分是gateway还是business
            // gateway存起来
            // business直接返回所有gateway信息
            case ConnectMessage::CMD:
                if ($revData['type'] === ConnectMessage::TYPE_GATEWAY) {
                    $this->gateways[$revData['ip']] = $fd;
                    return new SuccessMessage('register connected');
                }
                if ($revData['type'] === ConnectMessage::TYPE_BUSINESS) {
                    return new GatewayInfoMessage($this->gateways);
                }
                break;
            default:
                break;
        }
    }

    public function onClose($fd): void
    {
        $this->clearTimer($fd);
        // 区分是gateway还是business
        // gateway删除 通知所有business
        var_dump('register 中有人断开' . $fd);

        if (! empty($this->gateways[$fd])) {
            unset($this->gateways[$fd]);
        }
    }

    public function start($daemon = false)
    {
        $this->server = $server = new \Swoole\Server('0.0.0.0', 1236, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $server->set([
            'worker_num' => 1,
            'daemonize' => $daemon,
            'enable_coroutine' => true,
        ]);

        $server->on('WorkerStart', function (\Swoole\Server $server, int $workerId) {
            go(function () {
                $this->onStart();
            });
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

    private function clearTimer($fd)
    {
        if (isset($this->authTimeoutTimerMap[$fd])) {
            Timer::clear($this->authTimeoutTimerMap[$fd]);
            unset($this->authTimeoutTimerMap[$fd]);
        }
    }
}
