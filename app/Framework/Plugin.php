<?php

namespace app\Framework;

use app\Framework\Exception\PluginLoadException;
use app\Framework\Model\Instance;
use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\PluginLoadedEvent;
use app\Framework\Plugin\PluginBase;
use Throwable;

class Plugin
{
    static protected $list = [];

    static public function Load()
    {
        $logger = Logger::Get();

        // 加载插件
        $list = array_diff(scandir(__DIR__ . '/../plugins'), ['.', '..']);
        foreach ($list as $dir) {
            $plugin = new ('\\app\\plugins\\' . $dir . '\\Plugin');
            if (self::Exists($plugin)) {
                $logger->info('检测到同名插件 ' . $plugin->name . ' 位于 ' . $dir . ' 目录');
                continue;
            }
            self::$list[$plugin->name] = $plugin;
        }

        // 启动插件
        self::Map(function (PluginBase $plugin) use ($logger) {
            try {
                $plugin->onLoad();
            } catch (PluginLoadException $th) {
                $logger->info('插件 ' . $plugin->name . ' 启动失败：' . $th->getMessage());
            } catch (Throwable $th) {
                $logger->info('插件 ' . $plugin->name . ' 启动时出现未知问题：' . PHP_EOL . $th);
            }
        });

        $logger->info('插件已加载完成');

        Event::Dispatch(
            new PluginLoadedEvent()
        );
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
