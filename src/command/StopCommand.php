<?php

namespace Friendsofhyperf\Gateway\command;


use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Symfony\Component\Console\Input\InputArgument;
use Swoole\Process;
use Swoole\Coroutine;
use Swoole\Coroutine\Server\Connection;

#[Command]
class StopCommand extends HyperfCommand
{

    /**
     * Execution in a coroutine environment.
     */
    protected bool $coroutine = false;

    /**
     * 执行的命令行
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