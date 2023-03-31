<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Swoole\Coroutine;
use Swoole\Process;

#[Command]
class StopCommand extends HyperfCommand
{
    /**
     * Execution in a coroutine environment.
     */
    protected bool $coroutine = false;

    /**
     * 执行的命令行.
     */
    protected ?string $name = 'gateway:stop';

    public function handle()
    {
        Process::kill(13443);
    }

    protected function getArguments()
    {
        return [
        ];
    }
}
