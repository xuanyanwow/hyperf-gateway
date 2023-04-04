<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\worker;

use Swoole\Coroutine;
use Swoole\Coroutine\Client;
use Swoole\Timer;

class TcpClient
{
    public const STATUS_INITIAL = 0;

    public const STATUS_CONNECTING = 1;

    public const STATUS_CONNECTED = 2;

    public const STATUS_CLOSING = -1;

    public const STATUS_CLOSED = -2;

    public $errCode;

    /** 心跳定时器ID */
    public int $pingTimer;

    /** 重连定时器ID */
    public int $reconnectTimer;

    protected $onConnect;

    protected $onMessage;

    protected $onClose;

    protected Client $client;

    // 构造函数
    public function __construct(
        protected $address,
        protected $port,
        protected $connectTimeout,
        /** 连接状态 */
        protected $status = self::STATUS_INITIAL,
    ) {
    }

    public function getAddressWithPort(): string
    {
        return $this->address . ':' . $this->port;
    }

    // onConnect setter
    public function setOnConnect(callable|array $onConnect): void
    {
        $this->onConnect = $onConnect;
    }

    // onMessage
    public function setOnMessage(callable|array $onMessage): void
    {
        $this->onMessage = $onMessage;
    }

    // onClose
    public function setOnClose(callable|array $onClose): void
    {
        $this->onClose = $onClose;
    }

    public function connect()
    {
        if (
            $this->status !== self::STATUS_INITIAL
            && $this->status !== self::STATUS_CLOSED
        ) {
            return;
        }
        $this->status = self::STATUS_CONNECTING;

        $this->client = $client = new Client(SWOOLE_SOCK_TCP);

        $res = $client->connect($this->address, $this->port, $this->connectTimeout);

        if (! $res) {
            $this->errCode = $client->errCode;
            $this->status = self::STATUS_CLOSED;
            return $res;
        }

        $this->status = self::STATUS_CONNECTED;
        if (! empty($this->reconnectTimer)) {
            Timer::clear($this->reconnectTimer);
        }

        go(function () use ($client) {
            if (! empty($this->onConnect)) {
                call_user_func($this->onConnect, $this);
            }
            while (true) {
                $data = $client->recv(-1);

                if (! empty($data)) {
                    if (! empty($this->onMessage)) {
                        call_user_func($this->onMessage, $this, $data);
                    }
                } else {
                    if (! empty($this->onClose)) {
                        $this->status = self::STATUS_CLOSED;
                        call_user_func($this->onClose, $this);
                    }
                    break;
                }

                Coroutine::sleep(0.05);
            }
        });

        return $res;
    }

    public function send(string $data, int $flag = 0)
    {
        return $this->client->send($data, $flag);
    }

    public function close()
    {
        return $this->client->close();
    }

    public function reconnect(int $tick = 0)
    {
        if ($tick > 0) {
            $this->reconnectTimer = Timer::tick($tick * 1000, function () {
                $this->connect();
            });
        }
        $this->connect();
    }
}
