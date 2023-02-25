<?php

namespace app\Framework\Plugin;

use app\Framework\Logger;

abstract class PluginBase
{
    public string $name, $description, $version, $author;

    public array $listener = [];

    abstract public function onLoad(): void;

    public function registerEvents(EventListener $eventListener)
    {
        Event::Register($eventListener);
    }

    public function getLogger()
    {
        return Logger::Get($this->name);
    }
}
