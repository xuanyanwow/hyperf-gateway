<?php

namespace Friendsofhyperf\Gateway\message\register;

use Friendsofhyperf\Gateway\message\BaseMessage;

class ConnectMessage extends BaseMessage
{
    public string $class = self::class;
    public string $ip;
    public string $type;

    const TYPE_GATEWAY = 'gateway';
    const TYPE_BUSINESS = 'business';

    public function __construct(string $ip, string $type)
    {
        $this->ip = $ip;
        $this->type = $type;
    }
}