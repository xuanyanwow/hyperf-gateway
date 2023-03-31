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

class ConnectMessage extends BaseMessage
{
    public const CMD = 'ConnectMessage';

    public const TYPE_GATEWAY = 'gateway';

    public const TYPE_BUSINESS = 'business';

    public string $class = self::CMD;

    public string $ip;

    public string $type;

    public function __construct(string $ip, string $type)
    {
        $this->ip = $ip;
        $this->type = $type;
    }
}
