<?php

namespace app\plugins\ContainerListener\Event;

use app\Framework\Model\Container;
use app\Framework\Plugin\Event\EventBase;

class ContainerAttachEvent extends EventBase
{
    public function __construct(
        public Container $container
    ) {
    }
}
