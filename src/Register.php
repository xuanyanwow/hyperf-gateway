<?php
namespace Friendsofhyperf\Gateway;

use Friendsofhyperf\Gateway\message\register\ConnectMessage;
use Friendsofhyperf\Gateway\message\register\GatewayInfoMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\TcpServerTrait;
use Swoole\Coroutine\Server\Connection;

class Register implements WorkerInterface
{

    protected array $gateways = [];

    public function onStart(): void
    {
        echo "\n====================\nregister start \n====================\n\n";

        // 读取配置文件，监听端口

    }

    public function onConnect($fd): void
    {
    }

    public function onMessage($conn,  $revData)
    {
        $revData = trim($revData);
        $revData = json_decode($revData, true);
        if (!isset($revData['class'])) return false;
        var_dump($revData);

        switch ($revData['class']) {
            // 区分是gateway还是business
            // gateway存起来
            // business直接返回所有gateway信息
            case ConnectMessage::CMD:
                if ($revData['type'] === ConnectMessage::TYPE_GATEWAY){
                    $this->gateways[$revData['ip']] = $conn;
                    return new SuccessMessage("register connected");
                }else if ($revData['type'] === ConnectMessage::TYPE_BUSINESS){
                    var_dump('return info');
                    return new GatewayInfoMessage($this->gateways);
                }
                break;

            default :
                break;
        }
    }

    public function onClose($fd): void
    {
        // 区分是gateway还是business
        // gateway删除 通知所有business
        var_dump('register 中有人断开' . $fd);

        if (!empty($this->gateways[$fd])){ unset($this->gateways[$fd]); };
    }

    public function start($daemon = false)
    {

        $server = new \Swoole\Server('0.0.0.0', 1236, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $server->set([
            'worker_num' => 1,
            'daemonize' => $daemon,
            'enable_coroutine' => true,
        ]);

        $server->on('WorkerStart', function(\Swoole\Server $server, int $workerId) {
            go(function(){
                $this->onStart();
            });
        });
        $server->on('connect', function ($server, $fd){
            $this->onConnect($fd);
        });
        $server->on('receive', function (\Swoole\Server $server, $fd, $reactor_id, $data) {
            $response = $this->onMessage($fd,$data);
            if (!empty($response)){
                $server->send($fd, $response);
            }
        });
        $server->on('close', function ($server, $fd) {
            $this->onClose($fd);
        });
        $server->start();
    }

    private function heartBeat(Connection $conn)
    {

    }
}