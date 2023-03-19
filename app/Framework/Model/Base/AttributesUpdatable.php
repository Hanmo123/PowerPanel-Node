<?php

namespace app\Framework\Model\Base;

trait AttributesUpdatable
{
    public function updateAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }
}
