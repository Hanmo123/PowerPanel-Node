<?php

namespace app\Framework\Plugin;

use app\Framework\Plugin\Event\EventBase;
use ReflectionMethod;

class Event
{
    static array $eventList = [];

    static public function Register(EventListener $eventListener)
    {
        foreach (get_class_methods($eventListener) as $method) {
            $event = substr($method, 2) . 'Event';

            $priority = (new ReflectionMethod($eventListener, $method))
                ->getAttributes(EventPriority::class)[0]
                ->getArguments()[0];

            if (isset(self::$eventList[$event])) {
                // 事件已被监听
                if (isset(self::$eventList[$event][$priority])) {
                    // 事件优先级存在
                    self::$eventList[$event][$priority][] = [$eventListener, $method];
                } else {
                    // 事件优先级不存在
                    self::$eventList[$event][$priority] = [
                        [$eventListener, $method]
                    ];
                }
            } else {
                // 事件未被监听
                self::$eventList[$event] = [
                    $priority => [
                        [$eventListener, $method]
                    ]
                ];
            }
        }

        // 按照优先级从高到低排序
        foreach (self::$eventList as $event => $list) {
            krsort(self::$eventList[$event]);
        }
    }

    static public function Dispatch(EventBase $event)
    {
        $eventName = $event->getEventName();
        if (isset(self::$eventList[$eventName])) {
            // 遍历优先级
            foreach (self::$eventList[$eventName] as $list) {
                // 遍历事件
                foreach ($list as $callable) {
                    if ($callable($event) === false) return false; // 事件返回 false 停止传递
                }
            }
        }
        return true;
    }
}
