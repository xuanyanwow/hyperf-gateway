<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\worker;

use Friendsofhyperf\Gateway\message\PingMessage;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

trait ConnectRegisterTrait
{
    protected $registerClient;

    protected function connectRegister()
    {
        $this->registerClient = $client = new Client(SWOOLE_SOCK_TCP);

        if (! $client->connect($this->registerAddress, $this->registerPort, $this->registerConnectTimeout)) {
            echo "connect failed. Error: {$client->errCode}\n";
            return;
        }

        if (method_exists($this, 'onRegisterConnect')) {
            $this->onRegisterConnect($client);
        }

        // 不在本地服务器 保持心跳
        if (strpos($this->registerAddress, '127.0.0.1') !== 0) {
            $this->heartRegister();
        }

        while (true) {
            $data = $client->recv(-1);

            if (! empty($data)) {
                if (method_exists($this, 'onRegisterReceive')) {
                    $data = json_decode($data, true);
                    $this->onRegisterReceive($client, $data);
                }
            } else {
                $client->close();
                if (method_exists($this, 'onRegisterClose')) {
                    $this->onRegisterClose($client);
                }
                break;
            }

            Coroutine::sleep(1);
        }
    }

    /**
     * 维持与register的连接.
     *
     * @param $client
     */
    private function heartRegister()
    {
        go(function () {
            while (true) {
                $res = $this->registerClient->send(new PingMessage());
                Coroutine::sleep($this->pingRegisterInterval);
            }
        });
    }
}
