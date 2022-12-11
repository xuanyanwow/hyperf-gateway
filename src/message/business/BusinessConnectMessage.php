<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway\message\business;

use Friendsofhyperf\Gateway\message\BaseMessage;

class BusinessConnectMessage extends BaseMessage
{
    public const CMD = 'BusinessConnectMessage';

    public string $class = self::CMD;

    public string $ip;

    public string $type;

    public function __construct(string $ip, string $type)
    {
        $this->ip = $ip;
        $this->type = $type;
    }
}
