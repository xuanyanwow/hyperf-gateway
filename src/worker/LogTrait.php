<?php

namespace Friendsofhyperf\Gateway\worker;

trait LogTrait
{
    public static function info($workerType, $scen, $message)
    {
        echo sprintf("%s - %s : %s\n", $workerType, $scen, is_string($message) ? $message : var_export($message, true));
    }
}