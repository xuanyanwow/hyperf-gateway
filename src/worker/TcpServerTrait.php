<?php

namespace Friendsofhyperf\Gateway\worker;

use Swoole\Coroutine;
use Swoole\Coroutine\Server\Connection;

trait TcpServerTrait
{
    public function tcpServerHandle(Connection $conn)
    {
        while (true) {
            //接收数据
            $data = $conn->recv(1);

            if ($data === '' || $data === false) {
                $errCode = swoole_last_error();
                $errMsg = socket_strerror($errCode);
                if ($errCode != 0 ){
                    echo "errCode: {$errCode}, errMsg: {$errMsg}\n";
                    // $this->onClose($conn);
                    // $conn->close();
                    break;
                }else{
                    // 定时心跳
                    $this->heartBeat($conn);
                    Coroutine::sleep(0.01);
                    continue;
                }
            }

            $response = $this->onMessage($conn, $data);

            if (!empty($response)){
                $conn->send($response);
            }

            Coroutine::sleep(0.01);
        }
    }

    public function heartBeat(Connection $conn)
    {
        // 每隔一段时间发送心跳
    }
}