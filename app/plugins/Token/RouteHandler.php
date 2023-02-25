<?php

namespace app\plugins\Token;

use app\Framework\Token;
use Swoole\Http\Request;
use Swoole\Http\Response;

class RouteHandler
{
    static public function Generate(Request $request, Response $response)
    {
        $attr = $request->post['attributes'];

        return [
            'code' => 200,
            'attributes' => [
                'token' => Token::Create(
                    $attr['permission'],
                    $attr['data'],
                    $attr['created_at'],
                    $attr['expire_at']
                )->token
            ]
        ];
    }
}
