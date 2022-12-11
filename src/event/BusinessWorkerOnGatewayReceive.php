<?php

namespace Friendsofhyperf\Gateway\event;

use Friendsofhyperf\Gateway\message\gateway\ClientEventMessage;
use Friendsofhyperf\Gateway\message\PingMessage;

trait BusinessWorkerOnGatewayReceive
{
    public function businessWorkerOnGatewayReceive($client, $data)
    {
        self::debug('Business', 'gateway Receive', $data);

        switch ($data['class'] ?? '') {
            case PingMessage::CMD:
                self::debug('BusinessWorker', 'waitGateway', '收到gateway发来的心跳');
                break;
            case ClientEventMessage::CMD:
                $this->clientEvent($client,$data);
                break;
        }

    }

    protected function clientEvent($client,$data)
    {
        // 每一条任务消息都开协程处理
        \Hyperf\Engine\Coroutine::create(function () use ($client, $data) {
            // TODO
            $clientId = '123';
            $event = 'Message';// 也可能是断开和连接
            $this->onMessage($clientId, $data);
        });
    }
}