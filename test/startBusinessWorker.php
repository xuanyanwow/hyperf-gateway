<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
use Friendsofhyperf\Gateway\BusinessWorker;
use Friendsofhyperf\Gateway\Test\event\TestEvent;

require __DIR__ . '/../vendor/autoload.php';

(new BusinessWorker(
    secretKey: 'friendsofhyperf'
))
    ->setCustomerEvent(new TestEvent())
    ->start(false);
