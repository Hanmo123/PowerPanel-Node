<?php

namespace app\Framework\Model;

use app\Framework\Client\Docker;
use app\Framework\Client\Panel;
use app\Framework\Logger;
use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\InstanceListedEvent;

class Instance
{
    const STATUS_INSTALLING = 1;
    const STATUS_STARTING = 11;
    const STATUS_RUNNING = 21;
    const STATUS_STOPPING = 31;
    const STATUS_STOPPED = 41;

    /**
     * @var array<self>
     */
    static public $list;

    public int $status = self::STATUS_STOPPED;

    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public bool $is_suspended,
        public int $cpu,
        public int $memory,
        public int $swap,
        public int $disk,
        public string $image
    ) {
    }

    static public function Get(string $uuid)
    {
        return self::$list[$uuid];
    }

    static protected function InitList()
    {
        $client = new Panel();
        foreach ($client->get('/api/node/ins')['attributes']['list'] as $data) {
            self::$list[$data['uuid']] = new self(
                ...array_intersect_key($data, array_flip(['id', 'uuid', 'name', 'is_suspended', 'cpu', 'memory', 'swap', 'disk', 'image']))
            );
        }
    }

    static protected function InitStatus()
    {
        return array_map(
            function ($data) {
                // TODO 差异容器处理
                self::Get(substr($data['Names'][0], 1))->status
                    = in_array($data['State'], ['starting', 'running']) ? self::STATUS_RUNNING : self::STATUS_STOPPED;
            },
            json_decode((new Docker())->get('/containers/json?filters={"label":["Service=PowerPanel"]}'), true)
        );
    }

    static public function Init()
    {
        $logger = Logger::Get();

        $logger->info('正在获取面板实例列表...');
        self::InitList();

        $logger->info('正在获取实例状态...');
        self::InitStatus();

        Event::Dispatch(
            new InstanceListedEvent()
        );
    }

    /**
     * 上报容器统计数据
     *
     * @param array $stats
     * @return array 储存空间超限的实例 UUID 列表
     */
    static public function ReportStats(array $stats): array
    {
        return (new Panel())->put('/api/node/ins/stats', [
            'data' => $stats
        ])['data'];
    }
}
