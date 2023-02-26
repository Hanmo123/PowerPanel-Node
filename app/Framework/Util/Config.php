<?php

namespace app\Framework\Util;

class Config
{
    static $config;

    static public function Get()
    {
        if (!isset(self::$config)) self::$config = json_decode(file_get_contents('config.json'), true);
        return self::$config;
    }

    static public function Init()
    {
        if (!is_file('config.json'))
            copy('config.example.json', 'config.json');
    }
}