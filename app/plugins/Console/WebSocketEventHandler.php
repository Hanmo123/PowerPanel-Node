<?php

namespace app\plugins\Console;

use app\Framework\Exception\TokenInvalidException;
use app\Framework\Exception\TokenNotFoundException;
use app\Framework\Model\Instance;
use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\WebSocketCloseEvent;
use app\Framework\Plugin\Event\WebSocketConnectedEvent;
use app\Framework\Plugin\Event\WebSocketConnectEvent;
use app\Framework\Plugin\Event\WebSocketMessageEvent;
use app\Framework\Plugin\EventListener;
use app\Framework\Plugin\EventPriority;
use app\Framework\Wrapper\Response;
use app\plugins\InstanceListener\Event\InstanceStdinEvent;
use app\plugins\InstanceListener\Event\InstanceStdoutEvent;
use app\plugins\InstanceListener\StdioHandler;
use app\plugins\Token\Token;

class WebSocketEventHandler extends EventListener
{
    /**
     * @var array<Response>
     */
    static protected $connections = [];

    #[EventPriority(EventPriority::NORMAL)]
    public function onWebSocketConnect(WebSocketConnectEvent $ev)
    {
        try {
            $request = $ev->request->request;
            $response = $ev->response->response;

            // WebSocket 鉴权
            if ($ev->request->path() == '/ws/console') {
                if (!isset($request->get['token']))
                    throw new TokenNotFoundException();

                $token = Token::Get($request->get['token']);
                if ($token->isExpired())
                    throw new TokenInvalidException();

                $ev->response->token = $token;

                self::$connections[$response->fd] = $ev->response;
            }
        } catch (\Throwable $th) {
            $response->header('Content-Type', 'application/json');
            $response->status($th->getCode());
            $response->end(json_encode([
                'code' => $th->getCode(),
                'msg' => $th->getMessage()
            ], JSON_UNESCAPED_UNICODE));

            return false;
        }
    }

    #[EventPriority(EventPriority::NORMAL)]
    public function onWebSocketConnected(WebSocketConnectedEvent $ev)
    {
        WebSocketHandler::onConnect($ev->request, $ev->response);
    }

    #[EventPriority(EventPriority::NORMAL)]
    public function onInstanceStdout(InstanceStdoutEvent $ev)
    {
        $base64 = base64_encode($ev->data);
        foreach (self::$connections as $conn) {
            if ($conn->token->data['instance'] != $ev->instance->uuid) continue;
            if (!$conn->token->isPermit('console.read')) return;
            $conn->send([
                'type' => 'stdout',
                'data' => $base64
            ]);
        }
    }

    #[EventPriority(EventPriority::NORMAL)]
    public function onWebSocketMessage(WebSocketMessageEvent $ev)
    {
        if ($ev->request->path() != '/ws/console') return;

        $data = json_decode($ev->frame->data, true);
        switch ($data['type'] ?? null) {
            case 'stdin':
                // 检查权限
                if (!$ev->response->token->isPermit('console.write')) return;
                if (!Event::Dispatch(
                    new InstanceStdinEvent(
                        $ev->request,
                        $ev->response,
                        $instance = Instance::Get($ev->response->token->data['instance']),
                        base64_decode($data['data'])
                    )
                )) return;
                StdioHandler::Write($instance, base64_decode($data['data']));
                break;
        }
    }

    #[EventPriority(EventPriority::NORMAL)]
    public function onWebSocketClose(WebSocketCloseEvent $ev)
    {
        unset(self::$connections[$ev->response->response->fd]);
    }
}
