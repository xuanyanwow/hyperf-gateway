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

class ConnectMessage extends BaseMessage
{
    public const CMD = 'ConnectMessage';

    public const TYPE_GATEWAY = 'gateway';

    public const TYPE_BUSINESS = 'business';

    public string $class = self::CMD;

    public function __construct(public string $ip, public string $type)
    {
    }
}
