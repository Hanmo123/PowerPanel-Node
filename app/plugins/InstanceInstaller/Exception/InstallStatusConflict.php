<?php

namespace app\plugins\InstanceInstaller\Exception;

use Exception;

class InstallStatusConflict extends Exception
{
    public function __construct($message = '仅已停止的实例可执行重装操作', $code = 400)
    {
        parent::__construct($message, $code);
    }
}
