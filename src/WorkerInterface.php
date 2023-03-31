<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway;

interface WorkerInterface
{
    public function start($daemon = false);

    public function onStart();

    public function onConnect($fd);

    public function onMessage($fd, $revData);

    public function onClose($fd);
}
