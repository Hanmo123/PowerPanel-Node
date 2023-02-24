<?php

namespace app\Framework;

use app\Framework\Plugin\PluginBase;

class Plugin
{
    static protected $list = [];

    static public function Load()
    {
        $list = array_diff(scandir(__DIR__ . '/../plugins'), ['.', '..']);
        foreach ($list as $dir) {
            $plugin = new ('\\app\\plugins\\' . $dir . '\\Plugin');
            if (self::Exists($plugin)) {
                // TODO 插件重复处理
                continue;
            }
            self::$list[$plugin->name] = $plugin;
        }

        self::Map(function (PluginBase $plugin) {
            if (!$plugin->onLoad()) {
                // TODO 插件启动失败处理
            }
        });
    }

    static public function Exists(PluginBase $plugin)
    {
        return isset(self::$list[$plugin->name]);
    }

    static public function Map(callable $callback)
    {
        return array_map($callback, self::$list);
    }
}
