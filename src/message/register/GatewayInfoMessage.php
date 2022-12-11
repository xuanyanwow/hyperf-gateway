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

class GatewayInfoMessage extends BaseMessage
{
    public const CMD = 'GatewayInfoMessage';

    public string $class = self::CMD;

    public function __construct(public array $list)
    {
    }
}
