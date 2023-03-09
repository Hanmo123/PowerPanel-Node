<?php

namespace app\plugins\FileManager\Middleware;

use Swoole\Http\Request;
use Swoole\Http\Response;

class CORSMiddleware
{
    static public function process(Request $request, Response $response)
    {
        $response->header('Access-Control-Allow-Credentials', true);
        $response->header('Access-Control-Allow-Origin', $request->header['origin'] ?? '*');
        $response->header('Access-Control-Allow-Methods', $request->header['access-control-request-method'] ?? '*');
        $response->header('Access-Control-Allow-Headers', $request->header['access-control-request-headers'] ?? '*');
    }
}
