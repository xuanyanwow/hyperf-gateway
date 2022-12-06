<?php

namespace Friendsofhyperf\Gateway\message;

class BaseMessage
{
    public function __toString(): string
    {
        return json_encode($this)."\n";
    }
}