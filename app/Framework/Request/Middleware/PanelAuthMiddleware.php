<?php

namespace app\Framework\Request\Middleware;

use Swoole\Http\Request;
use Swoole\Http\Response;

class PanelAuthMiddleware extends MiddlewareBase
{
    static public function process(Request $request, Response $response)
    {
        throw new \Exception('666');
    }
}
