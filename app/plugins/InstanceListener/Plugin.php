<?php

namespace app\plugins\InstanceListener;

use app\Framework\Plugin\PluginBase;

class Plugin extends PluginBase
{
    public string $name = 'InstanceListener';
    public string $version = '1.0.0';

    public function onLoad(): void
    {
        $this->registerEvents(new EventListener());

        $this->getLogger()->info('加载完成');
    }
}
