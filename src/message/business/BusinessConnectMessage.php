<?php

namespace Friendsofhyperf\Gateway\message\business;

use Friendsofhyperf\Gateway\message\BaseMessage;

class BusinessConnectMessage extends BaseMessage
{
    const CMD = 'BusinessConnectMessage';

    public string $class = self::CMD;
    public string $ip;
    public string $type;


    public function __construct(string $ip, string $type)
    {
        $this->ip = $ip;
        $this->type = $type;
    }
}