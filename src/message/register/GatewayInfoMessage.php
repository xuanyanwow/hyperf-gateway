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

    public string $class = self::CMD;

    public array $list = [];

    public function __construct($list)
    {
        $this->list = $list;
    }
}
