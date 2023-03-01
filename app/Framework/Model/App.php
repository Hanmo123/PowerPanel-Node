<?php

namespace app\Framework\Model;

class App
{
    static public array $filter = [
        'id', 'name', 'data_path', 'working_path', 'images', 'config', 'startup', 'skip_install', 'install_image', 'install_script'
    ];

    public array $data_path;
    public array $images;
    public array $config;
    public array $startup;

    public function __construct(
        public int $id,
        public string $name,
        string $data_path,
        public $working_path,
        string $images,
        string $config,
        string $startup,
        public bool $skip_install,
        public string $install_image,
        public string $install_script,
        public string $exit = 'stop',    // TODO
    ) {
        $this->data_path = json_decode($data_path, true);
        $this->images = json_decode($images, true);
        $this->config = json_decode($config, true);
        $this->startup = explode(' ', $startup);
    }
}
