<?php

namespace app\plugins\InstanceListener;

use app\Framework\Client\Panel;
use app\Framework\Model\Instance;
use app\Framework\Plugin\Event\InstanceListedEvent;
use app\Framework\Plugin\Event\InstanceStatusUpdateEvent;
use app\Framework\Plugin\EventListener as PluginEventListener;
use app\Framework\Plugin\EventPriority;

class EventListener extends PluginEventListener
{
    #[EventPriority(EventPriority::NORMAL)]
    public function onInstanceListed(InstanceListedEvent $ev)
    {
        StdioHandler::Init();
        StatsHandler::Init();
    }

    #[EventPriority(EventPriority::NORMAL)]
    public function onInstanceStatusUpdate(InstanceStatusUpdateEvent $ev)
    {
        $instance = $ev->instance;

        if ($ev->status == Instance::STATUS_INSTALLING) {
            // TODO 安装日志是否应该仅对管理员可见？
            StdioHandler::Attach($instance);
        } elseif ($ev->status == Instance::STATUS_STARTING) {
            StdioHandler::Attach($instance);
            StatsHandler::Listen($instance);
        } elseif ($ev->status == Instance::STATUS_STOPPED) {
            $instance->stats->reset();
        }

        $client = new Panel();
        $client->put('/api/node/ins/stats', [
            'data' => [
                $instance->uuid => [
                    'id' => $instance->id,
                    'status' => $instance->status,
                    'resources' => $instance->stats->toArray()
                ]
            ]
        ]);
    }
}
