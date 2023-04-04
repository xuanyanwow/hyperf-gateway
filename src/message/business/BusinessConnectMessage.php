<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\message\business;

use Friendsofhyperf\Gateway\message\BaseMessage;

class BusinessConnectMessage extends BaseMessage
{
    public const CMD = 'BusinessConnectMessage';

    public string $cmd = self::CMD;

    public string $ip;

    public string $type;

    public function __construct(string $ip, string $type)
    {
        $this->ip = $ip;
        $this->type = $type;
    }
}
