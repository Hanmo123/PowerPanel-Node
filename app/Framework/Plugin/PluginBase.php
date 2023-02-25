<?php

namespace app\Framework\Plugin;

abstract class PluginBase
{
    public string $name, $description, $version, $author;

    public array $listener = [];

    abstract public function onLoad(): void;

    public function registerEvents(EventListener $eventListener)
    {
        Event::Register($eventListener);
    }
}
