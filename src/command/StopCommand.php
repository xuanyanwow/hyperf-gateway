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
    protected $coroutine = false;

    /**
     * 执行的命令行
     */
    protected  $name = 'gateway:stop';


    public function handle()
    {
        // TODO 启动后生成pid文件 用于kill
        Process::kill(13443);
    }

    protected function getArguments()
    {
        return [
        ];
    }

}