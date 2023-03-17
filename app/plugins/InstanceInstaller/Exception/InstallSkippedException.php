<?php

namespace app\plugins\InstanceInstaller\Exception;

use Exception;

class InstallSkippedException extends Exception
{
    public function __construct()
    {
        parent::__construct('此镜像已被设置为跳过安装过程', 400);
    }
}
