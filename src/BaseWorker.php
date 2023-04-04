<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\interface\WorkerInterface;

abstract class BaseWorker implements WorkerInterface
{
    public static function log($message)
    {
        echo $message . PHP_EOL;
    }

    public static function debug($message, ...$args)
    {
        echo $message . PHP_EOL;
        // 输出args
        foreach ($args as $arg) {
            var_dump($arg);
        }
        echo '-------------------------' . PHP_EOL;
    }
}
