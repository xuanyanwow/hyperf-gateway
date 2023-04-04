<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\message\register;

use Friendsofhyperf\Gateway\message\BaseMessage;

class GatewayInfoMessage extends BaseMessage
{
    public const CMD = 'GatewayInfoMessage';

    public string $cmd = self::CMD;

    public function __construct(public array $list)
    {
    }
}
