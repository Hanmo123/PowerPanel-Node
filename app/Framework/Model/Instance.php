<?php

namespace app\Framework\Model;

use app\Framework\Client\Docker;
use app\Framework\Client\Panel;
use app\Framework\Logger;
use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\InstanceListedEvent;
use app\Framework\Plugin\Event\InstanceStatusUpdateEvent;
use app\plugins\InstanceListener\StdioHandler;

class Instance
{
    const STATUS_INSTALLING = 1;
    const STATUS_STARTING = 11;
    const STATUS_RUNNING = 21;
    const STATUS_STOPPING = 31;
    const STATUS_STOPPED = 41;

    static public array $filter = [
        'id', 'uuid', 'name', 'is_suspended', 'cpu', 'memory', 'swap', 'disk', 'image'
    ];

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
        public string $image,
        public App $app,
        public Version $version,
        public Allocation $allocation,
        public array $allocations
    ) {
    }

    public function wait()
    {
        $client = new Docker();
        return $client->post('/containers/' . $this->uuid . '/wait', []);
    }

    public function start()
    {
    }

    public function stop($wait = true)
    {
        StdioHandler::Write($this, $this->app->exit . PHP_EOL);

        // Stopping 事件
        $this->status = self::STATUS_STOPPING;
        Event::Dispatch(
            new InstanceStatusUpdateEvent($this, $this->status)
        );

        // Stopped 事件
        if ($wait) {
            $this->wait();
            $this->status = self::STATUS_STOPPED;
            Event::Dispatch(
                new InstanceStatusUpdateEvent($this, $this->status)
            );
        }
    }

    public function restart()
    {
    }

    public function kill()
    {
    }

    static public function Get(string $uuid, bool $refresh = true)
    {
        // TODO
        return self::$list[$uuid];
    }

    static protected function InitList()
    {
        $client = new Panel();
        foreach ($client->get('/api/node/ins')['attributes']['list'] as $data) {
            self::$list[$data['uuid']] = new self(
                ...array_intersect_key($data, array_flip(self::$filter)),
                ...[
                    'app' => new App(...array_intersect_key($data['app'], array_flip(App::$filter))),
                    'version' => new Version(...array_intersect_key($data['version'], array_flip(Version::$filter))),
                    'allocation' => new Allocation(...array_intersect_key($data['allocation'], array_flip(Allocation::$filter))),
                    'allocations' => array_map(function ($allocation) {
                        return new Allocation(...array_intersect_key($allocation, array_flip(Allocation::$filter)));
                    }, $data['allocations'])
                ]
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
