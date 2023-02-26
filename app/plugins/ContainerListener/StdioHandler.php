<?php

namespace app\plugins\ContainerListener;

use app\Framework\Client\Docker;
use app\Framework\Logger;
use app\Framework\Model\Container;
use app\Framework\Plugin\Event;
use app\plugins\ContainerListener\Event\ContainerAttachEvent;
use app\plugins\ContainerListener\Event\ContainerStdoutEvent;
use Swoole\WebSocket\CloseFrame;

class StdioHandler
{
    static array $list;

    static public function Init()
    {
        $list = Container::List();
        /** @var Container $container */
        foreach ($list as $container) {
            if (in_array($container->getState(), ['starting', 'running'])) {
                // 实例运行中 可监听
                self::Attach($container);
            }
        }
    }

    static public function Attach(Container $container)
    {
        go(function () use ($container) {
            Logger::Get('ContainerListener')->debug('实例 ' . $container->uuid . ' 开始监听');

            // 触发 ContainerAttach 事件
            if (!Event::Dispatch(
                new ContainerAttachEvent($container)
            )) return;

            $client = (new Docker())->getClient();
            $client->setHeaders(['Host' => 'localhost']);
            $client->upgrade('/containers/' . $container->uuid . '/attach/ws?stream=1&stdout=1');

            while (1) {
                $frame = $client->recv();

                if ($frame instanceof CloseFrame || is_string($frame)) {
                    // 连接断开返回 CloseFrame 或空字符串
                    $client->close();
                    break;
                }
                if ($frame === false) {
                    // 30s 内无消息即为超时 超时返回 false
                    continue;
                }

                // 触发 ContainerStdout 事件
                if (!Event::Dispatch(
                    new ContainerStdoutEvent($client, $container, $frame->data)
                )) return;
            }
        });
    }
}
