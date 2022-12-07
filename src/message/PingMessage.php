<?php

namespace Friendsofhyperf\Gateway\message;

class PingMessage extends BaseMessage
{
    const CMD = 'PingMessage';
    public string $class = self::CMD;
}