<?php

namespace app\plugins\Attach;

use app\Framework\Plugin\Event\RouteInitEvent;
use app\Framework\Plugin\EventListener as PluginEventListener;
use app\Framework\Plugin\EventPriority;

class EventListener extends PluginEventListener
{
    #[EventPriority(EventPriority::NORMAL)]
    public function onRouteInit(RouteInitEvent $routeInitEvent)
    {
        var_dump($routeInitEvent->routeCollector);
    }
}
