<?php

namespace app\Framework\Model;

use app\Framework\Client\Docker;
use app\Framework\Client\Panel;
use app\Framework\Logger;
use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\InstanceListedEvent;
use app\Framework\Plugin\Event\InstanceStatusUpdateEvent;
use app\Framework\Util\Config;
use app\plugins\FileManager\FileSystemHandler;
use app\plugins\InstanceListener\StdioHandler;

class Instance
{
    const STATUS_INSTALLING = 1;
    const STATUS_STARTING = 11;
    const STATUS_RUNNING = 21;
    const STATUS_STOPPING = 31;
    const STATUS_STOPPED = 41;

    static protected $CPUPeriod = 100000;

    static public array $filter = [
        'id', 'uuid', 'name', 'is_suspended', 'cpu', 'memory', 'swap', 'disk', 'image'
    ];

    /**
     * @var array<self>
     */
    static public $list;

    public int $status = self::STATUS_STOPPED;

    public InstanceStats $stats;

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
        $this->stats = new InstanceStats();
    }

    public function getBinds()
    {
        $binds = [];
        foreach ($this->app->data_path as $target => $source) {
            $binds[] = Instance::GetBasePath() . '/' . $this->uuid . $source . ':' . $target;
        }
        return $binds;
    }

    public function getLog()
    {
        $client = new Docker();
        $log = $client->get('/containers/' . $this->uuid . '/logs?tail=500&stdout=1&stderr=1', $code);
        return $code == 200 ? $log : false;
    }

    public function wait()
    {
        $client = new Docker();
        return $client->post('/containers/' . $this->uuid . '/wait', []);
    }

    public function start()
    {
        $client = new Docker();
        $client->post('/containers/create?name=' . $this->uuid, [
            'User' => 'root',   // TODO 更改 Docker 运行用户
            'Tty' => true,
            'AttachStdin' => true,
            'OpenStdin' => true,
            'Image' => $this->image,
            'Env' => [
                // TODO
            ],
            'WorkingDir' => $this->app->working_path,
            'Labels' => [
                'Service' => 'PowerPanel'
            ],
            'HostConfig' => [
                'AutoRemove' => true,
                'Binds' => $this->getBinds(),
                'Memory' => $this->memory * 1024 * 1024,
                'MemorySwap' => ($this->memory + $this->swap) * 1024 * 1024,
                'CpuPeriod' => self::$CPUPeriod,
                'CpuQuota' => self::$CPUPeriod * $this->cpu / 100,
                'Dns' => Config::Get()['docker']['dns'],
                // 'PortBindings' => array_map(fn (Allocation $allocation) => $allocation->getBindings(), $this->allocations)   // TODO
            ]
        ] + ($this->app->startup ? ['Cmd' => $this->app->startup] : []));
        $client->post('/containers/' . $this->uuid . '/start', []);

        $this->status = self::STATUS_STARTING;
        Event::Dispatch(
            new InstanceStatusUpdateEvent($this, $this->status)
        );
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
        $this->stop(true);
        $this->start();
    }

    public function kill()
    {
        $client = new Docker();
        $client->post('/containers/' . $this->uuid . '/kill', []);

        $this->status = self::STATUS_STOPPED;
        Event::Dispatch(
            new InstanceStatusUpdateEvent($this, $this->status)
        );
    }

    public function getFileSystemHandler()
    {
        return new FileSystemHandler($this);
    }

    static public function GetBasePath()
    {
        return Config::Get()['storage_path']['instance_data'];
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
}
