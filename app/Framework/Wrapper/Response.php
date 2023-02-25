<?php

namespace app\Framework\Wrapper;

use Swoole\Http\Response as SwooleResponse;

class Response
{
    public function __construct(
        public SwooleResponse $response
    ) {
    }
}
