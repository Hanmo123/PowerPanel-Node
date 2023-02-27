<?php

namespace app\plugins\InstanceListener\Event;

use app\Framework\Model\Instance;
use app\Framework\Plugin\Event\EventBase;
use app\Framework\Wrapper\Request;
use app\Framework\Wrapper\Response;

class InstanceStdinEvent extends EventBase
{
    public function __construct(
        public Request $request,
        public Response $response,
        public Instance $instance,
        public mixed $data
    ) {
    }
}
