<?php

namespace app\Framework\Plugin\Event;

use app\Framework\Wrapper\Request;
use app\Framework\Wrapper\Response;

class WebSocketCloseEvent extends EventBase
{
    public function __construct(
        public Request $request,
        public Response $response
    ) {
    }
}
