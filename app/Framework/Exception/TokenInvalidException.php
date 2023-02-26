<?php

namespace app\Framework\Exception;

use Exception;

class TokenInvalidException extends Exception
{
    public function __construct(string $message = 'Token 失效或权限不足', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
