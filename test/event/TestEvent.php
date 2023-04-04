<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\Test\event;

use Friendsofhyperf\Gateway\interface\EventInterface;

class TestEvent implements EventInterface
{
    public function onConnect($connection)
    {
        echo 'test event onConnect';
        var_dump($connection);
    }

    public function onMessage($connection, $data)
    {
        echo 'test event onMessage';
        var_dump($connection);
        var_dump($data);
    }

    public function onClose($connection)
    {
        echo 'test event onClose';
        var_dump($connection);
    }

    public function onWebsocketConnect($connection, $httpHeader)
    {
        echo 'test event onWebsocketConnect';
        var_dump($connection);
        var_dump($httpHeader);
    }
}
