<?php

namespace app\plugins\Console;

use app\Framework\Plugin\PluginBase;

class Plugin extends PluginBase
{
    public string $name = 'Console';
    public string $version = '1.0.0';

    public function onLoad(): void
    {
        $this->registerEvents(new EventListener());
    }
}
