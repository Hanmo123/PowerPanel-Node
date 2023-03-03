<?php

namespace app\plugins\InstanceListener;

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
        if ($ev->status == Instance::STATUS_STARTING) {
            StdioHandler::Attach($ev->instance);
            StatsHandler::Listen($ev->instance);
        }
    }
}
