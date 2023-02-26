<?php

namespace app\plugins\Console;

use app\Framework\Wrapper\Request;
use app\Framework\Wrapper\Response;

class WebSocketHandler
{
    static public function onConnect(Request $request, Response $response)
    {
        $response->send([
            'type' => 'status',
            'data' => 21
        ]);
    }
}
