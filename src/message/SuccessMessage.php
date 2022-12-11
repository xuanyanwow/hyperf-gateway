<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway\message;

class SuccessMessage extends BaseMessage
{
    public const CMD = 'SuccessMessage';

    public string $class = self::CMD;

    public function __construct(public string $message)
    {
    }
}
