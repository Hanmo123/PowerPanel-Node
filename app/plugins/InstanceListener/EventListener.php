<?php

namespace app\plugins\InstanceListener;

use app\Framework\Model\Instance;
use app\Framework\Plugin\Event\InstanceListedEvent;
use app\Framework\Plugin\EventListener as PluginEventListener;
use app\Framework\Plugin\EventPriority;

class EventListener extends PluginEventListener
{
    #[EventPriority(EventPriority::NORMAL)]
    public function onInstanceListed(InstanceListedEvent $ev)
    {
        StdioHandler::Init();
    }
}
