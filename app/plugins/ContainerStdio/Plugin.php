<?php

namespace app\plugins\Attach;

use app\Framework\Plugin\PluginBase;

class Plugin extends PluginBase
{
    public string $name = 'Attach';
    public string $version = '1.0.0';

    public function onLoad(): bool
    {
        return true;
    }
}
