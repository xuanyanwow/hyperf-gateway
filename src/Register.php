<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayDisconnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\LogTrait;
use Swoole\Coroutine\Server\Connection;
use Swoole\Server;

use const registerPort;

class Register implements WorkerInterface
{
    use LogTrait;

    protected array $gateways = [];

    protected array $business = [];

    private Server $server;

    public function onStart(): void
    {
        echo "\n====================\nregister start \n====================\n\n";

        // 读取配置文件，监听端口
    }

    public function onConnect($fd): void
    {
        self::debug('Register', 'on connect', $fd);
    }

    public function onMessage($fd, $revData)
    {
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
                    $this->sendAllBusiness(new GatewayConnectMessage([$revData['ip']]));
                    return new SuccessMessage('register connected');
                }
                if ($revData['type'] === ConnectMessage::TYPE_BUSINESS) {
                    $this->business[$fd] = $fd;
                    return new GatewayInfoMessage(array_keys($this->gateways));
                }
                break;
            default:
                break;
        }
    }

    public function onClose($fd): void
    {
        self::debug('Register', 'on Close', $fd);

        // business
        if (! empty($this->business[$fd])) {
            self::debug('Register', 'onClose', 'business close ' . $fd);
            unset($this->business[$fd]);
            return;
        }

        // gateway删除
        foreach ($this->gateways as $ip => $gatewayFd) {
            if ($gatewayFd == $fd) {
                self::debug('Register', 'onClose', 'gateway close ' . $ip);
                unset($this->gateways[$ip]);
                $this->sendAllBusiness(new GatewayDisconnectMessage([$ip]));
            }
        }
    }

    public function start($daemon = false)
    {
        $this->server = $server = new Server('0.0.0.0', registerPort, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $server->set([
            'worker_num' => 1,
            'daemonize' => $daemon,
            'enable_coroutine' => true,
            'hook_flags' => swoole_hook_flags(),
        ]);

        $server->on('WorkerStart', function (Server $server, int $workerId) {
            $this->onStart();
        });
        $server->on('connect', function ($server, $fd) {
            $this->onConnect($fd);
        });
        $server->on('receive', function (Server $server, $fd, $reactor_id, $data) {
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

    protected function sendAllBusiness($message)
    {
        self::debug('register', 'send all business', $message);
        foreach ($this->business as $fd) {
            $this->server->send($fd, $message);
        }
    }

    private function heartBeat(Connection $conn)
    {
    }
}
