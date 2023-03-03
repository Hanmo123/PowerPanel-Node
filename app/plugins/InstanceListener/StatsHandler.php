<?php

namespace app\plugins\InstanceListener;

use app\Framework\Client\Docker\Stats;
use app\Framework\Logger;
use app\Framework\Model\Instance;
use app\Framework\Plugin\Event;
use app\plugins\InstanceListener\Event\InstanceStatsUpdateEvent;
use CurlHandle;

class StatsHandler
{
    static protected array $list;

    static public function Init()
    {
        foreach (Instance::$list as $instance) {
            if ($instance->status != Instance::STATUS_RUNNING) continue;
            self::Listen($instance);
        }
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

            $logger->info('实例 ' . $instance->uuid . ' 的状态监听连接已断开');
        });
    }
}
