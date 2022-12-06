<?php

namespace Friendsofhyperf\Gateway\message\register;

use Friendsofhyperf\Gateway\message\BaseMessage;

class GatewayInfoMessage extends BaseMessage
{
    public string $class = self::class;
    public string $list;


    public function __construct($list)
    {
        $this->list = $list;
    }
}