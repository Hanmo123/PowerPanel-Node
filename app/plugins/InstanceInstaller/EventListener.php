<?php

namespace app\plugins\InstanceInstaller;

use app\Framework\Plugin\Event\RouteInitEvent;
use app\Framework\Plugin\EventListener as PluginEventListener;
use app\Framework\Plugin\EventPriority;

class EventListener extends PluginEventListener
{
    #[EventPriority(EventPriority::NORMAL)]
    public function onRouteInit(RouteInitEvent $ev)
    {
        $r = $ev->routeCollector;
        $r->post('/api/panel/ins/reinstall', [Controller::class, 'Reinstall']);
    }
}
