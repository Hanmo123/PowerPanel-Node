<?php

namespace app\plugins\InstanceListener\Event;

use app\Framework\Model\Instance;
use app\Framework\Plugin\Event\EventBase;
use Swoole\Coroutine\Http\Client;

class InstanceMessageEvent extends EventBase
{
    public function __construct(
        public Instance $instance,
        public mixed $data
    ) {
    }
}
