<?php

namespace app\plugins\Console;

use app\Framework\Plugin\Event\WebSocketConnectEvent;
use app\Framework\Plugin\Event\WebSocketMessageEvent;
use app\Framework\Plugin\EventListener as PluginEventListener;
use app\Framework\Plugin\EventPriority;

class EventListener extends PluginEventListener
{
    #[EventPriority(EventPriority::NORMAL)]
    public function onWebSocketConnect(WebSocketConnectEvent $ev)
    {
        // WebSocket 鉴权
        if ($ev->request->request->server['request_uri'] == '/websocket/console') {
            // TODO
        }
    }

    #[EventPriority(EventPriority::NORMAL)]
    public function onWebSocketMessage(WebSocketMessageEvent $ev)
    {
        $data = json_decode($ev->frame->data, true);
    }
}
