<?php

namespace app\plugins\InstanceListener;

use app\Framework\Client\Docker\EventStream;
use app\Framework\Client\Panel;
use app\Framework\Logger;
use app\Framework\Model\Instance;
use app\Framework\Plugin\Event;
use app\Framework\Util\Config;
use app\plugins\InstanceListener\Event\InstanceDiskExceedEvent;
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

            $client = new EventStream();

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
        $logger = Logger::Get('InstanceListener');
        $logger->debug('正在扫描实例存储用量...');

        // 在扫描超大文件夹时使用 du 命令耗时较 PHP 递归迭代可减少 ~50%
        $chart = [];
        $dataPath = Config::Get()['storage_path']['instance_data'];
        exec('du -sb --apparent-size ' . escapeshellarg($dataPath) . '/*', $return);
        foreach ($return as $row) {
            [$bytes, $path] = explode("\t", $row);
            $uuid = str_replace($dataPath . '/', '', $path);
            $chart[$uuid] = $bytes;
        }

        $logger->info('正在上报实例统计数据...');
        $client = new Panel();
        $client->put('/api/node/ins/stats', [
            'data' => array_map(function (Instance $instance) use ($chart, $logger) {
                $instance->stats->disk = $chart[$instance->uuid] ?? 0;

                // TODO 是否需要设置超时时间 并强制关机？
                if ($instance->status == Instance::STATUS_RUNNING && $instance->isDiskExceed()) {
                    // 磁盘空间超出限制
                    if (Event::Dispatch(
                        new InstanceDiskExceedEvent($instance)
                    )) {
                        $instance->stop();
                        $logger->info('实例 ' . $instance->uuid . ' 的存储空间超限 正在关机');
                    }
                }

                return [
                    'id' => $instance->id,
                    'status' => $instance->status,
                    'resources' => $instance->stats->toArray()
                ];
            }, Instance::$list)
        ]);
    }
}
