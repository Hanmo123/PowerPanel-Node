<?php

namespace app\plugins\Console\Event;

use app\Framework\Model\Instance;
use app\Framework\Plugin\Event\EventBase;
use app\Framework\Wrapper\Request;
use app\Framework\Wrapper\Response;

class InstancePowerEvent extends EventBase
{
    public function __construct(
        public Request $request,
        public Response $response,
        public Instance $instance,
        public string $action
    ) {
    }
}
