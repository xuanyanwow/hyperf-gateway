<?php
/**
 * This file is part of hongyan-server.
 *
 * @link     https://github.com/xuanyanwow/hyperf-gateway
 * @document https://github.com/xuanyanwow/hyperf-gateway/blob/master/README.md
 * @author   siam
 */
namespace Friendsofhyperf\Gateway\message\gateway;

class RedirectionMessage
{
    public const CMD_CONNECT = 'Redirection:Connect';

    public const CMD_MESSAGE = 'Redirection:Message';

    public const CMD_WEBSOCKET_CONNECT = 'Redirection:WebSocketConnect';

    public const CMD_CLOSE = 'Redirection:Close';
}
