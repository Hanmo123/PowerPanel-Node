<?php

namespace app\Framework;

use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\WebSocketConnectEvent;
use app\Framework\Plugin\Event\WebSocketMessageEvent;
use app\Framework\Wrapper\Request as WrapperRequest;
use app\Framework\Wrapper\Response as WrapperResponse;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;

class WebSocketHandler
{
    static public function onConnect(Request $req, Response $res)
    {
        if (!Event::Dispatch(
            new WebSocketConnectEvent($wrapperReq = new WrapperRequest($req), $wrapperRes = new WrapperResponse($res))
        )) return $res->close(); // 插件拦截事件 拒绝连接

        // WebSocket 握手
        $res->upgrade();

        // 循环接收 WebSocket 包
        while (1) {
            $frame = $res->recv(30);
            if ($frame instanceof CloseFrame || is_string($frame)) {
                // 连接断开返回 CloseFrame 或空字符串
                $res->close();
                break;
            }
            if ($frame === false) {
                // 30s 内无消息即为超时 超时返回 false
                // 保活连接 发送心跳包
                $res->push(json_encode(['type' => 'heartbeat']));
                continue;
            }

            Event::Dispatch(new WebSocketMessageEvent($wrapperReq, $wrapperRes, $frame));
        }

        self::onClose($req);
    }

    static public function onClose(Request $req)
    {
    }
}
