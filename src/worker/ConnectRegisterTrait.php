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
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Swoole\Coroutine;
use Swoole\Coroutine\Client;

use const registerIp;
use const registerPort;

trait ConnectRegisterTrait
{
    use LogTrait;

    protected static ?Client $registerClient;

    protected ?int $registerHeartCoroutineId;

    protected function connectRegister()
    {
        $client = new Client(SWOOLE_SOCK_TCP);

        $isConnected = false;
        while (! $isConnected) {
            if (! $client->connect(registerIp, (int) registerPort, 0.5)) {
                self::debug('Worker', 'connect Register', $client->errCode);
                $client->close();
                \Hyperf\Utils\Coroutine::sleep(3);
                continue;
            }
            $isConnected = true;
        }

        self::debug("connect Register", "connect", "连接成功");


        self::$registerClient = $client;
        $this->onRegisterConnect($client);
        $this->heartRegister();

        \Hyperf\Engine\Coroutine::create(function () use ($client) {
            while (true) {
                Coroutine::sleep(0.01);

                // client退出了 让出当前协程，后续自动重连会创建新的协程监听
                if($client->errCode == 32){
                    return ;
                }

                $data = $client->recv();

                if (empty($data)){
                    continue;
                }
                self::debug("connect", "recv", $data);

                $data = json_decode($data, true);
                if (($data['class'] ?? '') == SuccessMessage::CMD) {
                    continue;
                }

                // 可以开协程处理消息
                self::debug("connect", "recv", 'test');
                $this->onRegisterReceive($client, $data);
            }
        });
    }

    /**
     * 维持与register的连接, 实现自动重连
     */
    private function heartRegister()
    {
        if (!empty($this->registerHeartCoroutineId)) return ;
        $this->registerHeartCoroutineId = Coroutine::create(function () {
            while (true) {
                Coroutine::sleep(5);
                if (empty(self::$registerClient)) {
                    continue;
                }
                $sendRes = self::$registerClient->send(PingMessage::make());

                // 检测是否断开连接了
                self::debug("connect Register", "heart", $sendRes);
                self::debug("connect Register", "heart", self::$registerClient->errCode);
                if ($sendRes === false || self::$registerClient->errCode == 32){
                    self::debug("connect Register", "retry connect", "检测到register断开，尝试重新连接");
                    self::$registerClient = null;
                    $this->connectRegister();
                    continue;
                }
            }
        });
    }
}
