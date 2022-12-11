<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
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
    protected $coroutine = false;

    /**
     * 执行的命令行.
     */
    protected $name = 'gateway:stop';

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
