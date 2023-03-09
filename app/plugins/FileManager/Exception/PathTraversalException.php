<?php

namespace app\plugins\FileManager\Exception;

use League\Flysystem\PathTraversalDetected;

class PathTraversalException extends PathTraversalDetected
{
    public function __construct(string $message = '路径不合法', int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
