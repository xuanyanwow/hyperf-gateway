<?php

namespace Friendsofhyperf\Gateway\message;

class PongMessage extends BaseMessage
{
    const CMD = 'PongMessage';
    public string $class = self::CMD;
}