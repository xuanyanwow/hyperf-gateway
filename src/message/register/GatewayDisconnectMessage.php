<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway\message\register;

use Friendsofhyperf\Gateway\message\BaseMessage;

class GatewayDisconnectMessage extends BaseMessage
{
    public const CMD = 'GatewayDisconnectMessage';

    public string $class = self::CMD;

    public function __construct(public array $list)
    {
    }
}
