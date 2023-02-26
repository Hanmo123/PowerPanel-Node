<?php

namespace app\plugins\ContainerListener\Event;

use app\Framework\Model\Container;
use app\Framework\Plugin\Event\EventBase;
use app\Framework\Wrapper\Request;
use app\Framework\Wrapper\Response;

class ContainerStdinEvent extends EventBase
{
    public function __construct(
        public Request $request,
        public Response $response,
        public Container $container,
        public mixed $data
    ) {
    }
}
