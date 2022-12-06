<?php

namespace Friendsofhyperf\Gateway\message;

class SuccessMessage extends BaseMessage
{
    public string $class = self::class;
    public string $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}