<?php

namespace app\Framework;

use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\RouteInitEvent;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

class Route
{
    static Dispatcher $dispatcher;

    static public function Init()
    {
        self::$dispatcher = simpleDispatcher(function (RouteCollector $r) {
            Event::Dispatch(new RouteInitEvent($r));
        });
    }

    static public function Dispatch($method, $uri)
    {
        return self::$dispatcher->dispatch($method, $uri);
    }
}
