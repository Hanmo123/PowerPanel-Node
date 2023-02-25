<?php

namespace app\plugins\Token;

use app\Framework\Plugin\Event\RouteInitEvent;
use app\Framework\Plugin\EventListener as PluginEventListener;
use app\Framework\Plugin\EventPriority;
use app\plugins\Token\RouteHandler;

class EventListener extends PluginEventListener
{
    #[EventPriority(EventPriority::NORMAL)]
    public function onRouteInit(RouteInitEvent $ev)
    {
        $ev->routeCollector->addRoute('POST', '/api/panel/token', [RouteHandler::class, 'Generate']);
    }
}
