<?php

namespace app\Framework;

use app\Framework\Util\Config;
use Swoole\Coroutine\Http\Server as HttpServer;

class Server
{
    static protected $server;

    static public function Boot()
    {
        Route::Init();

        Logger::Get()->info('服务器已启动在 http://0.0.0.0:' . Config::Get()['node_port']);
        $server = new HttpServer('0.0.0.0', Config::Get()['node_port']);

        $server->handle('/',            [RequestHandler::class, 'onRequest']);
        $server->handle('/ws',   [WebSocketHandler::class, 'onConnect']);

        self::$server = $server;
        self::$server->start();
    }
}
