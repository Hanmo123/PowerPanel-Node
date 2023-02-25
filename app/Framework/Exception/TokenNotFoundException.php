<?php

namespace app\Framework\Exception;

use Exception;

class TokenNotFoundException extends Exception
{
    public function __construct(string $message = 'Token 不存在', int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
