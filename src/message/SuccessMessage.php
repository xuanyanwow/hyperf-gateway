<?php

namespace Friendsofhyperf\Gateway\message;

class SuccessMessage extends BaseMessage
{
    const CMD = 'SuccessMessage';

    public string $class = self::CMD;
    public string $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}