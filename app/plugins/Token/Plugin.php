<?php

namespace app\plugins\Token;

use app\Framework\Plugin\PluginBase;

class Plugin extends PluginBase
{
    public string $name = 'Token';
    public string $version = '1.0.0';

    public function onLoad(): void
    {
        $this->getLogger()->info('加载成功');

        $this->registerEvents(new EventListener());
    }
}
