<?php

namespace app\plugins\FileManager;

use app\Framework\Plugin\Event\RouteInitEvent;
use app\Framework\Plugin\EventListener as PluginEventListener;
use app\Framework\Plugin\EventPriority;
use FastRoute\RouteCollector;

class EventListener extends PluginEventListener
{
    #[EventPriority(EventPriority::NORMAL)]
    public function onRouteInit(RouteInitEvent $ev)
    {
        $r = $ev->routeCollector;
        $r->addGroup('/api/panel/files', function (RouteCollector $r) {
            $r->post('/list', [RouteHandler::class, 'GetList']);
            $r->post('/rename', [RouteHandler::class, 'Rename']);
            $r->post('/compress', [RouteHandler::class, 'Compress']);
            $r->post('/decompress', [RouteHandler::class, 'Decompress']);
            $r->post('/delete', [RouteHandler::class, 'Delete']);
            $r->post('/permission', [RouteHandler::class, 'GetPermission']);
            $r->put('/permission', [RouteHandler::class, 'SetPermission']);
            $r->post('/create', [RouteHandler::class, 'Create']);
            $r->post('/read', [RouteHandler::class, 'Read']);
            $r->post('/save', [RouteHandler::class, 'Save']);
        });
        $r->addGroup('/api/public/files', function (RouteCollector $r) {
            $r->addRoute('OPTIONS', '/upload', [RouteHandler::class, 'CORS']);
            $r->post('/upload', [RouteHandler::class, 'Upload']);
            $r->get('/download', [RouteHandler::class, 'Download']);
        });
    }
}
