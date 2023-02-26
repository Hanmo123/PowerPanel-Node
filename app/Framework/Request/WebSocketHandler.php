<?php

namespace app\Framework\Request;

use app\Framework\Logger;
use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\WebSocketCloseEvent;
use app\Framework\Plugin\Event\WebSocketConnectedEvent;
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
        @Logger::Get()->info('[' . $req->server['remote_addr'] . ':' . $req->server['remote_port'] . '] [WS] ' . $req->server['request_uri'] . (isset($req->server['query_string']) ? '?' . $req->server['query_string'] : null));

        if (!Event::Dispatch(
            new WebSocketConnectEvent($wrapperReq = new WrapperRequest($req), $wrapperRes = new WrapperResponse($res))
        )) return;  // 插件拦截事件 拒绝连接

        // WebSocket 握手
        if (!$res->upgrade())
            return Logger::Get()->info('[' . $req->server['remote_addr'] . ':' . $req->server['remote_port'] . '] [WS] 握手失败');

        if (!Event::Dispatch(
            new WebSocketConnectedEvent($wrapperReq, $wrapperRes)
        )) return;  // 插件拦截事件 拒绝连接

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

        self::onClose($wrapperReq, $wrapperRes);
    }

    static public function onClose(WrapperRequest $req, WrapperResponse $res)
    {
        Event::Dispatch(new WebSocketCloseEvent($req, $res));
    }
}
