<?php

namespace app\Framework\Plugin\Event;

use app\Framework\Model\Instance;

class ImagePullEvent extends EventBase
{
    public function __construct(
        public Instance $instance,
        public string $image
    ) {
    }
}
