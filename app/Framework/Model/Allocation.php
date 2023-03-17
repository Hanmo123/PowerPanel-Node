<?php

namespace app\Framework\Model;

class Allocation
{
    static public array $filter = [
        'id', 'ip', 'port', 'alias'
    ];

    public function __construct(
        public int $id,
        public string $ip,
        public int $port,
        public string $alias
    ) {
    }

    public function getBinding()
    {
        return [
            'HostIp' => $this->ip,
            'HostPort' => strval($this->port)
        ];
    }
}
