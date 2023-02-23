<?php

use app\controller\IndexController;
use FastRoute\RouteCollector;

/** @var RouteCollector $r */

$r->addRoute('GET', '/', [IndexController::class, 'Index']);
