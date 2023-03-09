<?php

namespace app\Framework\Request;

use app\Framework\Logger;
use app\Framework\Request\Middleware;
use app\Framework\Route;
use FastRoute\Dispatcher;
use ReflectionMethod;
use Swoole\Http\Request as HttpRequest;
use Swoole\Http\Response as HttpResponse;

class HttpHandler
{
    static public function onRequest(HttpRequest $req, HttpResponse $res)
    {
        try {
            @Logger::Get()->info('[' . $req->server['remote_addr'] . ':' . $req->server['remote_port'] . '] [' . strtoupper($req->getMethod()) . '] ' . $req->server['request_uri'] . (isset($req->server['query_string']) ? '?' . $req->server['query_string'] : null));

            $res->header('Content-Type', 'application/json');

            // 解析 JSON Request POST
            if (isset($req->header['content-type']) && strtolower($req->header['content-type']) == 'application/json')
                $req->post = json_decode($req->rawContent(), true);

            $routeInfo = Route::Dispatch($req->getMethod(), $req->server['request_uri']);

            switch ($routeInfo[0]) {
                case Dispatcher::NOT_FOUND:
                    throw new \Exception('请求接口不存在。', 404);
                    break;
                case Dispatcher::METHOD_NOT_ALLOWED:
                    throw new \Exception('请求方法错误。', 405);
                    break;
                case Dispatcher::FOUND:
                    // 中间件调用
                    $attr = (new ReflectionMethod($routeInfo[1][0], $routeInfo[1][1]))
                        ->getAttributes(Middleware::class);
                    if (isset($attr[0])) foreach ($attr[0]->getArguments() as $middleware) {
                        // 存在中间件
                        $middleware::process($req, $res);
                    }

                    // 请求调用并返回
                    if (!$return = call_user_func($routeInfo[1], $req, $res, ...$routeInfo[2])) return;
                    $res->end(json_encode($return, JSON_UNESCAPED_UNICODE));
                    break;
            }
        } catch (\Throwable $th) {
            $res->status($th->getCode() ?: 500);
            $res->end(json_encode(['code' => $th->getCode() ?: 500, 'msg' => $th->getMessage()], JSON_UNESCAPED_UNICODE));
        }
    }
}
