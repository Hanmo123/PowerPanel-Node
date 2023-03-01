<?php

namespace app\Framework\Plugin\Event;

use app\Framework\Model\Instance;

/**
 * 此事件不可拦截
 */
class InstanceStatusEvent extends EventBase
{
    public function __construct(
        public Instance $instance,
        public int $power
    ) {
    }
}
