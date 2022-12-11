<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway\worker;

trait LogTrait
{
    public static function debug($workerType, $scen, $message)
    {
        echo sprintf("%s - %s : %s\n", $workerType, $scen, is_string($message) ? $message : var_export($message, true));
    }
}
