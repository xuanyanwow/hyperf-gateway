<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\message;

class SuccessMessage extends BaseMessage
{
    public const CMD = 'SuccessMessage';

    public string $class = self::CMD;

    public string $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}
