<?php

namespace app\Framework;

use Swoole\Coroutine\Http\Server as HttpServer;

class Server
{
    static protected $server;

    static public function Boot()
    {
        Route::Init();

        $server = new HttpServer('0.0.0.0', 9502);

        $server->handle('/',            [RequestHandler::class, 'onRequest']);
        $server->handle('/websocket',   [WebSocketHandler::class, 'onConnect']);

        self::$server = $server;
        self::$server->start();
    }
}
