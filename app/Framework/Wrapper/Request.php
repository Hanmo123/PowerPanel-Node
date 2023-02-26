<?php

namespace app\Framework\Wrapper;

use Swoole\Http\Request as SwooleRequest;

class Request
{
    public function __construct(
        public SwooleRequest $request
    ) {
    }

    public function path()
    {
        return $this->request->server['request_uri'];
    }
}
