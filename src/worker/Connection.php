<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\worker;

class Connection
{
    public $gatewayHeader;

    // 构造函数
    public function __construct(
        array $clientInfo
    ) {
        var_dump($clientInfo);
    }
}
