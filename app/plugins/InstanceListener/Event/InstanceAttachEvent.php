<?php

namespace app\plugins\InstanceListener\Event;

use app\Framework\Model\Instance;
use app\Framework\Plugin\Event\EventBase;

class InstanceAttachEvent extends EventBase
{
    public function __construct(
        public Instance $instance
    ) {
    }
}
