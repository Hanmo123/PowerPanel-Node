<?php

namespace app\plugins\InstanceListener\Event;

use app\Framework\Model\Instance;
use app\Framework\Model\InstanceStats;
use app\Framework\Plugin\Event\EventBase;
use CurlHandle;

class InstanceStatsUpdateEvent extends EventBase
{
    public function __construct(
        public Instance $instance,
        public InstanceStats $stats,
        public array $chunk,
        public CurlHandle $ch
    ) {
    }
}
