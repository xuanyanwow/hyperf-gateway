<?php

namespace Friendsofhyperf\Gateway\message\gateway;

use Friendsofhyperf\Gateway\message\BaseMessage;

class ClientEventMessage extends BaseMessage
{
    public const CMD = 'ClientEvent';

    public string $class = self::CMD;

    public function __construct(public $event, public $clientId, public $data)
    {
    }
}
