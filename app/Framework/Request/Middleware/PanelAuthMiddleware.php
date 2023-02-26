<?php

namespace app\Framework\Request\Middleware;

use app\Framework\Exception\PanelAuthException;
use app\Framework\Util\Config;
use Swoole\Http\Request;
use Swoole\Http\Response;

class PanelAuthMiddleware extends MiddlewareBase
{
    static public function process(Request $request, Response $response)
    {
        if (
            !$request->header['authorization'] ||
            explode(' ', $request->header['authorization'], 2)[1] !== Config::Get()['node_token']
        ) throw new PanelAuthException('节点密钥错误', 401);
    }
}
