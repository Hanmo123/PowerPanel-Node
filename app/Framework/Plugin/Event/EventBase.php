<?php

namespace app\Framework\Plugin\Event;

abstract class EventBase
{
    public function getEventName()
    {
        $explode = explode('\\', get_class($this));
        return end($explode);
    }
}
