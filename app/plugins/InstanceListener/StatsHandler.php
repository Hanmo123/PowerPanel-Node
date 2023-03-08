<?php

namespace app\plugins\InstanceListener;

use app\Framework\Client\Docker\Stats;
use app\Framework\Client\Panel;
use app\Framework\Logger;
use app\Framework\Model\Instance;
use app\Framework\Plugin\Event;
use app\Framework\Util\Config;
use app\plugins\InstanceListener\Event\InstanceStatsUpdateEvent;
use CurlHandle;
use Swoole\Timer;

use function Co\go;

class StatsHandler
{
    static protected array $list;

    static public function Init()
    {
        foreach (Instance::$list as $instance) {
            if ($instance->status != Instance::STATUS_RUNNING) continue;
            self::Listen($instance);
        }

        go([self::class, 'Push']);
        Timer::tick(Config::Get()['report_stats_interval'] * 1000, [self::class, 'Push']);
    }

    static public function Listen(Instance $instance)
    {
        go(function () use ($instance) {
            $logger = Logger::Get('InstanceListener');

            $client = new Stats();

            if (isset(self::$list[$instance->uuid]))
                return $logger->info('实例 ' . $instance->uuid . ' 状态已在监听');

            $logger->debug('实例 ' . $instance->uuid . ' 状态开始监听');

            $client->handle('/containers/' . $instance->uuid . '/stats', function (CurlHandle $ch, $chunk) use ($instance) {
                $instance->stats->update($chunk);

                if (!Event::Dispatch(
                    new InstanceStatsUpdateEvent($instance, $instance->stats, $chunk, $ch)
                )) return;
            });

            $logger->debug('实例 ' . $instance->uuid . ' 的状态监听连接已断开');
        });
    }

    static public function Push()
    {
        Logger::Get('InstanceListener')->debug('正在扫描实例存储用量...');
        // 在扫描超大文件夹时使用 du 命令耗时较 PHP 递归迭代可减少 ~50%
        $chart = [];
        $dataPath = Config::Get()['storage_path']['instance_data'];
        exec('du -s ' . escapeshellarg($dataPath) . '/*', $return);
        foreach ($return as $row) {
            [$KBytes, $path] = explode("\t", $row);
            $uuid = str_replace($dataPath . '/', '', $path);
            $chart[$uuid] = $KBytes * 1024;
        }

        Logger::Get('InstanceListener')->info('正在上报实例统计数据...');
        $client = new Panel();
        $client->put('/api/node/ins/stats', [
            'data' => array_map(function (Instance $instance) use($chart) {
                $instance->stats->disk = $chart[$instance->uuid] ?? 0;
                return [
                    'id' => $instance->id,
                    'status' => $instance->status,
                    'resources' => $instance->stats->toArray()
                ];
            }, Instance::$list)
        ]);
        // TODO
    }
}
