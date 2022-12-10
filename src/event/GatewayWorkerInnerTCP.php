<?php

namespace Friendsofhyperf\Gateway\event;

use Friendsofhyperf\Gateway\GatewayWorker;
use Friendsofhyperf\Gateway\message\business\BusinessConnectMessage;
use Friendsofhyperf\Gateway\message\SuccessMessage;
use Friendsofhyperf\Gateway\worker\LogTrait;
use Swoole\Server;
use Swoole\Server\Port;

class GatewayWorkerInnerTCP
{
    private Port $_innerTcpWorker;
    use LogTrait;


    /**
     * 开启内部监听，用于监听 worker 的连接已经连接上发来的数据[lanIP + lanPort]
     * @param GatewayWorker $gatewayWorker
     */
    public function __construct(protected GatewayWorker $gatewayWorker)
    {
        self::info("GatewayWorkerInnerTCP", "start", "设置内部监听端口" . \lanIP . ":" . \lanPort);

        $this->_innerTcpWorker = $gatewayWorker->getServer()->listen(\lanIP, \lanPort, SWOOLE_SOCK_TCP);
         $this->_innerTcpWorker->set([
             'enable_coroutine' => true,
             'hook_flags' => swoole_hook_flags(),
         ]);


        $this->_innerTcpWorker->on('connect',[$this,'onBusinessConnect']);
        $this->_innerTcpWorker->on('receive', [$this, 'onBusinessMessage']);
        $this->_innerTcpWorker->on('close', [$this, 'onBusinessClose']);
    }


    /**
     * 当 worker 通过内部通讯端口连接到 gateway 时
     * @return void
     */
    public function onBusinessConnect($fd)
    {
        self::info("GatewayWorkerInnerTCP", "onBusinessConnect", "有新连接 定时器启动 必须在规定时间内发来认证消息");
    }

    /**
     * 当 worker 通过内部通讯端口发送消息时
     */
    public function onBusinessMessage(Server $server, $fd, $reactor_id, $revData)
    {
        // TODO 除了business连接/gateway Client连接鉴权的CMD，其他都需要先判断是否已经授权认证过
        var_dump("message");
        var_dump($revData);
        $revData = json_decode($revData, true);
        if (empty($revData) || empty($revData['class'])) return false;

        switch ($revData['class']) {
            case BusinessConnectMessage::CMD:
                $this->gatewayWorker->addBusiness($fd,$fd);
                $server->send($fd, new SuccessMessage("business connected"));

            default :
                break;
        }

        return false;

        // 向某客户端发送数据
        // T出用户
        // 广播
        // 判断是否在线
        // ...
    }

    /**
     * 当 worker 退出时
     * @return void
     */
    public function onBusinessClose($fd)
    {
        self::info("GatewayWorkerInnerTCP", "onBusinessConnect", "内部连接断开");
        $this->gatewayWorker->delBusiness($fd);

        if (isset($connection->key)) {
//            unset($this->_workerConnections[$connection->key]);
//            if ($this->onBusinessWorkerClose) {
//                call_user_func($this->onBusinessWorkerClose, $connection);
//            }
        }
    }
}