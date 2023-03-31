<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
use Friendsofhyperf\Gateway\Register;

require __DIR__ . '/../vendor/autoload.php';

(new Register())->start(false);
