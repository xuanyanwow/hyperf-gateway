<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\utils;

use Exception;

class ClientUtil
{
    /**
     * 通讯地址到 client_id 的转换.
     *
     * @param int $gatewayIp
     * @param int $internalPort
     * @param int $connectionId
     * @return string
     */
    public static function addressToClientId($gatewayIp, $internalPort, $connectionId)
    {
        return bin2hex(pack('NnN', $gatewayIp, $internalPort, $connectionId));
    }

    /**
     * client_id 到通讯地址的转换.
     *
     * @param string $clientId
     * @return array
     * @throws Exception
     */
    public static function clientIdToAddress($clientId)
    {
        if (strlen($clientId) !== 20) {
            echo new Exception("client_id {$clientId} is invalid");
            return false;
        }
        return unpack('NgatewayIp/ninternalPort/NconnectionId', pack('H*', $clientId));
    }
}
