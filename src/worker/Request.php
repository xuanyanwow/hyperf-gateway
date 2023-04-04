<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\worker;

class Request
{
    public function __construct(
        public string $clientIp,
        public int $clientPort,
        public string $gatewayIp,
        public int $gatewayPort,
        public int $internalPort,
        public string $connectionId,
        public string $clientId,
    ) {
    }
}
