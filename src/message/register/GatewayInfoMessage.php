<?php

namespace Friendsofhyperf\Gateway\message\register;

use Friendsofhyperf\Gateway\message\BaseMessage;

class GatewayInfoMessage extends BaseMessage
{
    const CMD = 'GatewayInfoMessage';

    public string $class = self::CMD;
    public array $list = [];


    public function __construct($list)
    {
        $this->list = $list;
    }
}