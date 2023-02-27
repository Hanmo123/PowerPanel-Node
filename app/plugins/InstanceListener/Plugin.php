<?php

namespace app\plugins\InstanceListener;

use app\Framework\Model\Instance;
use app\Framework\Plugin\PluginBase;

class Plugin extends PluginBase
{
    public string $name = 'InstanceListener';
    public string $version = '1.0.0';

    public function onLoad(): void
    {
        $this->registerEvents(new EventListener());

        $this->getLogger()->info('加载完成');
    }

    protected function Init()
    {
    }

    public function ReportStats()
    {
        // $this->getLogger()->debug('正在计算容器储存用量...');

        // $usage = [];
        // $dataPath = Config::Get()['storage_path']['instance_data'];
        // exec('du -s ' . escapeshellarg($dataPath) . '/*', $return);
        // foreach ($return as $row) {
        //     [$KBytes, $path] = explode("\t", $row);
        //     $uuid = str_replace($dataPath . '/', '', $path);

        //     $usage[$uuid] = $KBytes * 1024;

        // if (!Instance::isInstance($uuid)) continue;
        // Table::Get(Table::INSTANCE_STATS)->update($uuid, ['disk_usage' => $KBytes * 1024]);
        // }

        // $containers = Container::List();
    }
}
