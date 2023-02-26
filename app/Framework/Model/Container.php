<?php

namespace app\Framework\Model;

use app\Framework\Client\Docker;

class Container
{
    protected $state;

    public function __construct(
        public string $uuid
    ) {
    }

    public function getState()
    {
        return $this->state;
    }

    static public function List()
    {
        return array_map(
            function ($data){
                $container = new self(substr($data['Names'][0], 1));
                $container->state = $data['State'];
                return $container;
            },
            json_decode((new Docker())->get('/containers/json?filters={"label":["Service=PowerPanel"]}'), true)
        );
    }
}
