<?php

namespace app\plugins\InstanceListener;

use app\Framework\Client\Docker;
use app\Framework\Logger;
use app\Framework\Model\Instance;
use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\InstanceStatusUpdateEvent;
use app\plugins\InstanceListener\Event\InstanceAttachEvent;
use app\plugins\InstanceListener\Event\InstanceStdoutEvent;
use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\CloseFrame;

class StdioHandler
{
    /**
     * @var array<Client>
     */
    static array $list;

    static public function Init()
    {
        foreach (Instance::$list as $instance) {
            if ($instance->status != Instance::STATUS_RUNNING) continue;
            StdioHandler::Attach($instance);
        }
    }

    static public function Attach(Instance $instance)
    {
        go(function () use ($instance) {
            $logger = Logger::Get('InstanceListener');

            if (isset(self::$list[$instance->uuid]))
                return $logger->info('实例 ' . $instance->uuid . ' 已在监听');

            $logger->debug('实例 ' . $instance->uuid . ' 开始监听');

            // 触发 InstanceAttach 事件
            if (!Event::Dispatch(
                new InstanceAttachEvent($instance)
            )) return;

            $client = self::$list[$instance->uuid] = (new Docker())->getClient();
            $client->setHeaders(['Host' => 'localhost']);
            $client->upgrade('/containers/' . $instance->uuid . '/attach/ws?stream=1&stdout=1');

            while (1) {
                $frame = $client->recv();

                if ($frame instanceof CloseFrame || is_string($frame)) {
                    // 连接断开返回 CloseFrame 或空字符串
                    Logger::Get('InstanceListener')->info('实例 ' . $instance->uuid . ' 的监听连接已断开');
                    // TODO 异常断开判断
                    $client->close();
                    break;
                }
                if ($frame === false) {
                    // 30s 内无消息即为超时 超时返回 false
                    continue;
                }

                if ($instance->status == Instance::STATUS_STARTING) {
                    // 启动中状态 监测启动成功关键词
                    if (strpos($frame->data, $instance->app->config['done']) !== false) {
                        $instance->status = Instance::STATUS_RUNNING;
                        Event::Dispatch(
                            new InstanceStatusUpdateEvent($instance, $instance->status)
                        );
                    }
                }

                // 触发 InstanceStdout 事件
                if (!Event::Dispatch(
                    new InstanceStdoutEvent($client, $instance, $frame->data)
                )) continue;
            }
            unset(self::$list[$instance->uuid]);
        });
    }

    static public function Write(Instance $instance, mixed $content)
    {
        if (!isset(self::$list[$instance->uuid])) return false;
        self::$list[$instance->uuid]->push($content);
    }
}
