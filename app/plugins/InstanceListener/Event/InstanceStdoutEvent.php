<?php

namespace app\plugins\InstanceListener\Event;

use app\Framework\Model\Instance;
use app\Framework\Plugin\Event\EventBase;
use Swoole\Coroutine\Http\Client;

class InstanceStdoutEvent extends EventBase
{
    public function __construct(
        public Client $client,
        public Instance $instance,
        public mixed $data
    ) {
    }
}
