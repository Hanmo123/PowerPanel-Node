<?php

namespace app\plugins\InstanceInstaller;

use app\Framework\Model\Instance;
use app\Framework\Request\Middleware;
use app\Framework\Request\Middleware\PanelAuthMiddleware;
use Swoole\Http\Request;
use Swoole\Http\Response;

use function Co\go;

class Controller
{
    #[Middleware(PanelAuthMiddleware::class)]
    static public function Reinstall(Request $request, Response $response)
    {
        $instance = Instance::Get($request->post['attributes']['uuid']);
        go(function () use ($instance) {
            try {
                $instance->reinstall();
            } catch (\Throwable $th) {
            }
        });
        return ['code' => 200];
    }
}
