<?php

namespace app\Framework;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

class Route
{
    static Dispatcher $dispatcher;

    static public function Init()
    {
        self::$dispatcher = simpleDispatcher(function (RouteCollector $r) {
            include __DIR__ . '/../config/Route.php';
        });
    }

    static public function Dispatch($method, $uri)
    {
        return self::$dispatcher->dispatch($method, $uri);
    }
}
