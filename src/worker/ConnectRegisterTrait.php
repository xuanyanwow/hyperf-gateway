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
use Swoole\Timer;

trait ConnectRegisterTrait
{
    protected TcpClient $registerClient;

    public function onRegisterClose()
    {
        var_dump('检测到 register 断开');
        // 断线重连
        if (! empty($this->registerClient->pingTimer)) {
            Timer::clear($this->registerClient->pingTimer);
        }
        $this->registerClient->reconnect(1);
    }

    protected function connectRegister()
    {
        // feature 多个register
        $this->registerClient = $client = new TcpClient($this->registerAddress, $this->registerPort, $this->registerConnectTimeout, 0);

        if (method_exists($this, 'onRegisterConnect')) {
            $client->setOnConnect([$this, 'onRegisterConnect']);
        }
        if (method_exists($this, 'onRegisterReceive')) {
            $client->setOnMessage([$this, 'onRegisterReceive']);
        }
        if (method_exists($this, 'onRegisterClose')) {
            $client->setOnClose([$this, 'onRegisterClose']);
        }

        if (! $client->connect()) {
            echo "connect failed. Error: {$client->errCode}\n";
            return;
        }

        // 不在本地服务器 保持心跳
        if (strpos($this->registerAddress, '127.0.0.1') !== 0) {
            $this->heartRegister();
        }
    }

    /**
     * 维持与register的连接.
     *
     * @param $client
     */
    private function heartRegister()
    {
        $this->registerClient->pingTimer = Timer::tick($this->pingRegisterInterval * 1000, function () {
            $res = $this->registerClient->send(new PingMessage());
            if (! $res) {
                var_dump('ping register 失败');
            }
        });
    }
}
