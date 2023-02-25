<?php

namespace app\Framework;

use FastRoute\Dispatcher;
use Swoole\Http\Request as HttpRequest;
use Swoole\Http\Response as HttpResponse;

class RequestHandler
{
    static public function onRequest(HttpRequest $req, HttpResponse $res)
    {
        try {
            @Logger::Get()->info('[' . $req->server['remote_addr'] . ':' . $req->server['remote_port'] . '] [' . strtoupper($req->getMethod()) . '] ' . $req->server['request_uri'] . (isset($req->server['query_string']) ? '?' . $req->server['query_string'] : null));

            $res->header('Content-Type', 'application/json');

            $routeInfo = Route::Dispatch($req->getMethod(), $req->server['request_uri']);

            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                    throw new \Exception('请求接口不存在。', 404);
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    throw new \Exception('请求方法错误。', 405);
                    break;
                case Dispatcher::FOUND:
                    $res->end(json_encode(call_user_func($routeInfo[1], $req, $res, ...$routeInfo[2]), JSON_UNESCAPED_UNICODE));
                    break;
            }
        } catch (\Throwable $th) {
            $res->status($th->getCode() ?: 500);
            $res->end(json_encode(['code' => $th->getCode() ?: 500, 'msg' => $th->getMessage()], JSON_UNESCAPED_UNICODE));
        }
    }
}
