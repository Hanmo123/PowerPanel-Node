<?php

namespace app\plugins\ContainerListener\Event;

use app\Framework\Model\Container;
use app\Framework\Plugin\Event\EventBase;
use Swoole\Coroutine\Http\Client;

class ContainerStdoutEvent extends EventBase
{
    public function __construct(
        public Client $client,
        public Container $container,
        public string $data
    ) {
    }
}
