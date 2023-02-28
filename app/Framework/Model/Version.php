<?php

namespace app\Framework\Model;

class Version
{
    static public array $filter = [
        'id', 'name', 'version'
    ];

    public function __construct(
        public int $id,
        public string $name,
        public string $version
    ) {
    }
}
