<?php

namespace app\controller;

use app\client\Event\Swoole as EventClient;
use app\event\Event;
use app\event\EventData;
use app\event\Handler;
use app\model\Container;
use app\model\ContainerDetail;
use app\model\Instance;
use app\process\Listener;
use app\util\SwooleLogger as Log;

class ListenerEvent
{
    static Listener $listener;
    static $list = [
        'instance.power'            => [self::class, 'onInstancePower'],
        'instance.stdio.stdin'      => [self::class, 'onInstanceStdin'],
        'instance.disk.exceeded'    => [self::class, 'onInstanceDiskExceeded']
    ];

    static public function onInstancePower(...$params)
    {
        go(function (Handler $handler, string $uuid, string $power) {
            $instance = new Instance($uuid);
            switch ($power) {
                case 'start':
                    $detail = new ContainerDetail($instance, $instance->getDetail());

                    if ($instance->getInstanceStats()['disk_usage'] > $detail->disk * 1024 * 1024) {
                        $eventData = new EventData('instance.message', $uuid, '实例储存空间已达上限 无法启动');
                        $eventData->call();
                        EventClient::Publish($eventData);
                        return;
                    }

                    if ($detail->isSuspended) {
                        $eventData = new EventData('instance.message', $uuid, '实例已被暂停 无法启动');
                        $eventData->call();
                        EventClient::Publish($eventData);
                        return;
                    }

                    Container::Create($detail);
                    $instance->setInstanceStatus(Instance::STATUS_STARTING)
                        ->start();
                    self::$listener->attach($instance, $detail->appConfig);

                    $eventData = new EventData('instance.status', $instance->uuid, Instance::STATUS_STARTING);
                    $eventData->call();
                    EventClient::Publish($eventData);

                    Log::info('实例 ' . $instance->uuid . ' 已创建 正在启动');
                    break;
                case 'stop':
                    $detail = new ContainerDetail($instance, $instance->getDetail());

                    $eventData = new EventData('instance.stdio.stdin', $instance->uuid, base64_encode($detail->appConfig['exit'] . PHP_EOL));
                    $eventData->call();
                    EventClient::Publish($eventData);

                    $eventData = new EventData('instance.status', $instance->uuid, Instance::STATUS_STOPPING);
                    $eventData->call();
                    EventClient::Publish($eventData);

                    Log::info('实例 ' . $instance->uuid . ' 正在关闭');
                    break;
                case 'restart':
                    Event::Get('instance.power', $instance->uuid)->call('stop');
                    Event::Get('instance.status', $instance->uuid)
                        ->addHandler(new Handler(function (Handler $handler, string $sub, int $status) use ($instance) {
                            if ($status == Instance::STATUS_STOPPED) {
                                Event::Get('instance.power', $instance->uuid)->call('start');
                                $handler->remove();
                            }
                        }));
                    break;
                case 'kill':
                    $instance->kill();
                    Log::info('实例 ' . $instance->uuid . ' 正在终止');
                    break;
            }
        }, ...$params);
    }

    static public function onInstanceStdin(Handler $handler, string $uuid, string $content)
    {
        if (!isset(self::$listener->attaching[$uuid])) return false;
        self::$listener->attaching[$uuid]->push(base64_decode($content));
    }

    static public function onInstanceDiskExceeded(Handler $handler, string $uuid)
    {
        Event::Get('instance.power', $uuid)->call('stop');
        
        $eventData = new EventData('instance.message', $uuid, '实例储存空间已达上限 无法启动');
        $eventData->call();
        EventClient::Publish($eventData);
        
        Log::info('实例 ' . $uuid . ' 储存空间超限 正在停止');
    }
}
