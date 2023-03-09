<?php

namespace app\plugins\FileManager\Middleware;

use app\Framework\Exception\TokenInvalidException;
use app\Framework\Request\Middleware\MiddlewareBase;
use app\plugins\Token\Token;
use Swoole\Http\Request;
use Swoole\Http\Response;

class TokenAuthMiddleware extends MiddlewareBase
{
    static public function process(Request $request, Response $response)
    {
        if (!isset($request->get['token']) || Token::Get($request->get['token'])->isExpired())
            throw new TokenInvalidException();
    }
}
