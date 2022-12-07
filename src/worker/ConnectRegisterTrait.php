<?php

namespace Friendsofhyperf\Gateway\worker;

use Friendsofhyperf\Gateway\message\PingMessage;
use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;
use Swoole\Timer;

trait ConnectRegisterTrait
{
    /**
     * 维持与register的连接, 实现自动重连
     * TODO
     *
     * @param $client
     * @return void
     */
    private function heartRegister()
    {
        go(function(){
            while(true) {
                self::$registerClient->send(new PingMessage());
                Coroutine::sleep(5);
            }
        });
    }

    protected function connectRegister()
    {
        $client = new Client(SWOOLE_SOCK_TCP);

        if (!$client->connect('0.0.0.0', 1236, 3)) {
            echo "connect failed. Error: {$client->errCode}\n";
            return;
        }

        $this->onRegisterConnect($client);

        while(true){
            $data = $client->recv();

            if (!empty($data)){
                $data = json_decode($data, true);
                $breakWait = $this->onRegisterReceive($client, $data);
                if ($breakWait) {
                    $this->heartRegister();
                    break;
                }
            }

            Coroutine::sleep(3);
        }
    }
}