<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway\event;

use Friendsofhyperf\Gateway\message\register\GatewayConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayDisconnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Swoole\Coroutine\Client;

trait BusinessWorkerOnRegisterReceive
{
    public function businessWorkerOnRegisterReceive($client, $data)
    {
        self::debug('Business', 'register Receive', $data);

        switch ($data['class'] ?? '') {
            case GatewayInfoMessage::CMD:
                $this->gatewayInfoList($client, $data);
                break;
            case GatewayConnectMessage::CMD:
                $this->gatewayReConnect($client, $data);
                break;
            case GatewayDisconnectMessage::CMD:
                $this->gatewayDisconnect($client, $data);
                break;
        }
    }

    /**
     * register通知 哪些gateway下线了，需要剔除掉.
     */
    public function gatewayDisconnect($client, $data)
    {
        foreach ($data['list'] ?? [] as $ip) {
            unset(self::$gateways[$ip]);
        }
    }

    /**
     * business上线的时候，一次性从register获取到现有的gateway列表
     * 需要依次建立链接.
     */
    protected function gatewayInfoList($client, $data)
    {
        $this->connectGateway($data['list'] ?? []);
    }

    /**
     * business启动后，gateway重连或者新gateway加入
     * 需要建立链接.
     */
    protected function gatewayReConnect($client, $data)
    {
        $this->connectGateway($data['list'] ?? []);
    }
}
