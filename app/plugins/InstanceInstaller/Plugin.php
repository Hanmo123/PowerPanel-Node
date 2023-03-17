<?php

namespace app\plugins\InstanceInstaller;

use app\Framework\Plugin\PluginBase;

class Plugin extends PluginBase
{
    public string $name = 'InstanceInstaller';
    public string $version = '1.0.0';

    public function onLoad(): void
    {
        $this->registerEvents(new EventListener());

        $this->getLogger()->info('加载完成');
    }
}
