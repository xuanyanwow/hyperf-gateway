<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\command;

use Friendsofhyperf\Gateway\BusinessWorker;
use Friendsofhyperf\Gateway\GatewayWorker;
use Friendsofhyperf\Gateway\Register;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Swoole\Coroutine;
use Symfony\Component\Console\Input\InputArgument;

#[Command]
class StartCommand extends HyperfCommand
{
    /**
     * Execution in a coroutine environment.
     */
    protected bool $coroutine = false;

    /**
     * 执行的命令行.
     */
    protected ?string $name = 'gateway:start';

    public function handle()
    {
        // 启动的类型（三种）
        $type = $this->input->getArgument('type');

        // 是否后台运行
        $daemon = (bool) $this->input->getArgument('d') ?? false;

        match ($type) {
            'register' => (new Register())->start($daemon),
            'gateway' => (new GatewayWorker())->start($daemon),
            'business' => (new BusinessWorker())->start($daemon),
            default => $this->line('type error', 'error'),
        };
    }

    protected function getArguments()
    {
        return [
            ['type', InputArgument::REQUIRED, '启动的是gateway, business还是register'],
            ['d', InputArgument::OPTIONAL, '是否后台运行'],
        ];
    }
}
