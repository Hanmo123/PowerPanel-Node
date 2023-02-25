<?php

namespace app\plugins\Attach;

use Swoole\Http\Request;
use Swoole\Http\Response;

class Route
{
    static public function WebSocket(Request $req, Response $res)
    {
        $res->upgrade();
        var_dump('abc');
        go(function () use ($res) {
            while (1) {
                $res->recv();
            }
        });
    }
}
