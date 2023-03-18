<?php

namespace app\plugins\FileManager\Middleware;

use app\Framework\Model\Instance;
use app\Framework\Request\Middleware\MiddlewareBase;
use app\plugins\FileManager\Exception\InstanceFileSystemException;
use Swoole\Http\Request;
use Swoole\Http\Response;

class InstanceValidateMiddleware extends MiddlewareBase
{
    static public function process(Request $request, Response $response)
    {
        $instance = Instance::Get($request->post['attributes']['uuid'], false);
        if ($instance->isDiskExceed())
            throw new InstanceFileSystemException('实例存储空间超限 无法操作', 400);
        if ($instance->is_suspended)
            throw new InstanceFileSystemException('实例已被暂停 无法操作', 400);
    }
}
