<?php

namespace app\Framework\Plugin\Event;

use FastRoute\RouteCollector;

class RouteInitEvent extends EventBase
{
    public function __construct(
        public RouteCollector $routeCollector
    ) {
    }
}
