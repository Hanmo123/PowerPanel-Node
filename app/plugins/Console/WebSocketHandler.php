<?php

namespace app\plugins\Console;

use app\Framework\Model\Instance;
use app\Framework\Wrapper\Request;
use app\Framework\Wrapper\Response;
use app\plugins\Token\Token;

class WebSocketHandler
{
    static public function onConnect(Request $request, Response $response)
    {
        if ($request->path() != '/ws/console') return;
        if (!isset($response->token)) return;

        /** @var Token $token */
        $token = $response->token;
        $instance = Instance::Get($token->data['instance'], false);

        // 发送实例当前状态
        if ($token->isPermit('console.status.get')) {
            $response->send([
                'type' => 'status',
                'data' => $instance->status
            ]);
        }

        // 发送实例日志
        if ($token->isPermit('console.history')) {
            $response->send([
                'type' => 'history',
                'data' => base64_encode($instance->getLog())
            ]);
        }
    }
}
