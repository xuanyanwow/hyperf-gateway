<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
use Friendsofhyperf\Gateway\GatewayWorker;

require __DIR__ . '/../vendor/autoload.php';

// 获取命令行传参
$options = getopt('', ['listenPort:', 'lanPort:']);
$listenPort = isset($options['listenPort']) ? (int) $options['listenPort'] : 9501;
$lanPort = isset($options['lanPort']) ? (int) $options['lanPort'] : 9502;

(new GatewayWorker(
    secretKey: 'friendsofhyperf',
    listenPort: $listenPort,
    lanPort: $lanPort,
))->start(false);

// php startGatewayWorker.php --listenPort=9501 --lanPort=9502
// php startGatewayWorker.php --listenPort=9503 --lanPort=9504
