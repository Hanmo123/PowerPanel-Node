<?php

namespace app\plugins\Console;

use app\Framework\Model\Instance;
use app\Framework\Wrapper\Request;
use app\Framework\Wrapper\Response;

class WebSocketHandler
{
    static public function onConnect(Request $request, Response $response)
    {
        // TODO
        $response->send([
            'type' => 'status',
            'data' => Instance::Get($response->token->data['instance'], false)->status
        ]);
    }
}
