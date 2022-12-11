<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/components.
 * @link     https://github.com/friendsofhyperf/components
 * @document https://github.com/friendsofhyperf/components/blob/3.x/README.md
 * @contact  huangdijia@gmail.com
 */
namespace Friendsofhyperf\Gateway\worker;

use Friendsofhyperf\Gateway\message\PingMessage;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

use const registerIp;
use const registerPort;

trait ConnectRegisterTrait
{
    protected function connectRegister()
    {
        $client = new Client(SWOOLE_SOCK_TCP);

        if (! $client->connect(registerIp, registerPort, 3)) {
            echo "connect failed. Error: {$client->errCode}\n";
            return;
        }

        $this->onRegisterConnect($client);
        $this->heartRegister();

        \Hyperf\Engine\Coroutine::create(function () use ($client) {
            while (true) {
                $data = $client->recv();

                if (! empty($data)) {
                    $data = json_decode($data, true);
                    $this->onRegisterReceive($client, $data);
                }

                Coroutine::sleep(0.01);
            }
        });
    }

    /**
     * 维持与register的连接, 实现自动重连
     * TODO.
     *
     * @param $client
     */
    private function heartRegister()
    {
        go(function () {
            while (true) {
                if (empty(self::$registerClient)) {
                    Coroutine::sleep(5);
                    continue;
                }
                self::$registerClient->send(new PingMessage());
                Coroutine::sleep(5);
            }
        });
    }
}
