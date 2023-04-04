<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
interface EventInterface
{
    public function onConnect($connection);

    public function onMessage($connection, $data);

    public function onClose($connection);

    public function onWebsocketConnect($connection, $http_header);
}
