<?php

namespace app\Framework\Wrapper;

use Swoole\Http\Request as SwooleRequest;

class Request
{
    public function __construct(
        public SwooleRequest $request
    ) {
    }
}
