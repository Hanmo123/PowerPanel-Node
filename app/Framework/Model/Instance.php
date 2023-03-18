<?php

namespace app\Framework\Model;

use app\Framework\Client\Docker;
use app\Framework\Client\Docker\EventStream;
use app\Framework\Client\Panel;
use app\Framework\Exception\InstanceStartException;
use app\Framework\Logger;
use app\Framework\Plugin\Event;
use app\Framework\Plugin\Event\ImagePulledEvent;
use app\Framework\Plugin\Event\ImagePullEvent;
use app\Framework\Plugin\Event\ImagePullingEvent;
use app\Framework\Plugin\Event\InstanceListedEvent;
use app\Framework\Plugin\Event\InstanceStatusUpdateEvent;
use app\Framework\Util\Config;
use app\plugins\FileManager\FileSystemHandler;
use app\plugins\InstanceInstaller\Exception\InstallSkippedException;
use app\plugins\InstanceInstaller\Exception\InstallStatusConflict;
use app\plugins\InstanceListener\StdioHandler;
use CurlHandle;

use function Co\go;

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

    public function getBindings()
    {
        $bindings = [];
        /** @var Allocation $allocation */
        foreach ($this->allocations as $allocation) {
            $bindings[$allocation->port . '/tcp'] = $bindings[$allocation->port . '/udp'] = [$allocation->getBinding()];
        }
        return $bindings;
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
        if ($this->status !== self::STATUS_STOPPED)
            throw new InstanceStartException('实例未处于停止状态', 400);
        if ($this->isDiskExceed())
            throw new InstanceStartException('实例存储空间超限 无法启动', 400);
        if ($this->is_suspended)
            throw new InstanceStartException('实例已被暂停 无法启动', 400);

        $this->status = self::STATUS_STARTING;

        // 拉取镜像
        if (Event::Dispatch(
            new ImagePullEvent($this, $this->image)
        )) {
            $stream = new EventStream();
            $image = explode(':', $this->image, 2);
            $stream->handle('/images/create?fromImage=' . $image[0] . '&tag=' . ($image[1] ?? 'latest'), function (CurlHandle $ch, array $data) {
                // TODO 异常处理
                Event::Dispatch(new ImagePullingEvent($this, $this->image, $data));
            }, 'POST');

            Event::Dispatch(
                new ImagePulledEvent($this, $this->image)
            );
        }

        $client = new Docker();
        $client->post('/containers/create?name=' . $this->uuid, [
            'User' => 'root',   // TODO 更改 Docker 运行用户
            'Tty' => true,
            'AttachStdin' => true,
            'OpenStdin' => true,
            'Image' => $this->image,
            'Env' => $this->getEnv(),
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
                'PortBindings' => $this->getBindings()
            ]
        ] + ($this->app->startup ? ['Cmd' => $this->app->startup] : []));
        $client->post('/containers/' . $this->uuid . '/start', []);
        Event::Dispatch(
            new InstanceStatusUpdateEvent($this, $this->status)
        );
    }

    public function stop($wait = true)
    {
        StdioHandler::Write($this, $this->app->stop . PHP_EOL);

        // Stopping 事件
        $this->status = self::STATUS_STOPPING;
        Event::Dispatch(
            new InstanceStatusUpdateEvent($this, $this->status)
        );

        // Stopped 事件
        $next = function () {
            $this->wait();
            $this->status = self::STATUS_STOPPED;
            Event::Dispatch(
                new InstanceStatusUpdateEvent($this, $this->status)
            );
        };

        if ($wait)
            $next();
        else
            go($next);
    }

    public function restart()
    {
        $this->stop();
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

    public function reinstall($wait = true)
    {
        if ($this->app->skip_install)
            throw new InstallSkippedException();

        if ($this->status !== self::STATUS_STOPPED)
            throw new InstallStatusConflict();

        $script = Config::Get()['storage_path']['scripts'] . '/' . $this->uuid . '.sh';

        file_put_contents($script, $this->app->install_script);

        $binds = $this->getBinds();
        $binds[] = $script . ':/install.sh';

        $client = new Docker();
        $client->post('/containers/create?name=' . $this->uuid, [
            'User' => 'root',   // TODO 更改 Docker 运行用户
            'Tty' => true,
            'Image' => $this->app->install_image,
            'Env' => $this->getEnv(),
            'WorkingDir' => $this->app->working_path,
            'Labels' => [
                'Service' => 'PowerPanel'
            ],
            'HostConfig' => [
                'AutoRemove' => true,
                'Binds' => $binds,
                'Memory' => 1 * 1024 * 1024 * 1024,
                'MemorySwap' => 1 * 1024 * 1024 * 1024,
                'CpuPeriod' => self::$CPUPeriod,
                'CpuQuota' => self::$CPUPeriod,
                'Dns' => Config::Get()['docker']['dns'],
            ],
            'Cmd' => ['sh', '/install.sh']
        ]);
        $client->post('/containers/' . $this->uuid . '/start', []);

        $this->status = self::STATUS_INSTALLING;
        Event::Dispatch(
            new InstanceStatusUpdateEvent($this, $this->status)
        );

        $next = function () {
            $this->wait();
            $this->status = self::STATUS_STOPPED;
            Event::Dispatch(
                new InstanceStatusUpdateEvent($this, $this->status)
            );
        };

        if ($wait)
            $next();
        else
            go($next);
    }

    public function getFileSystemHandler()
    {
        return new FileSystemHandler($this);
    }

    public function getEnv()
    {
        return [
            'TZ=Asia/Shanghai',
            'SERVER_MEMORY=' . $this->memory,
            'SERVER_IP=' . $this->allocation->ip,
            'SERVER_PORT=' . $this->allocation->port,
            'SERVER_VERSION=' . $this->version->version
        ];
        // TODO 获取实例变量
    }

    public function isDiskExceed()
    {
        return $this->stats->disk > $this->disk * 1024 * 1024;
    }

    static public function GetBasePath()
    {
        return Config::Get()['storage_path']['instance_data'];
    }

    static public function fromAttributes(array $attributes)
    {
        return new self(
            ...array_intersect_key($attributes, array_flip(self::$filter)),
            ...[
                'app' => new App(...array_intersect_key($attributes['app'], array_flip(App::$filter))),
                'version' => new Version(...array_intersect_key($attributes['version'], array_flip(Version::$filter))),
                'allocation' => new Allocation(...array_intersect_key($attributes['allocation'], array_flip(Allocation::$filter))),
                'allocations' => array_map(function ($allocation) {
                    return new Allocation(...array_intersect_key($allocation, array_flip(Allocation::$filter)));
                }, $attributes['allocations'])
            ]
        );
    }

    static public function Get(string $uuid, bool $refresh = true)
    {
        if ($refresh) {
            $current = self::$list[$uuid] ?? false;

            $client = new Panel();
            $attributes = $client->post('/api/node/ins/detail', [
                'attributes' => [
                    'uuid' => $uuid
                ]
            ])['attributes'];
            $latest = self::fromAttributes($attributes);

            if ($current) {
                // 存在旧对象 从旧对象合并状态参数
                $latest->status = $current->status;
                $latest->stats = $current->stats;
            }

            self::$list[$attributes['uuid']] = $latest;
        }
        return self::$list[$uuid];
    }

    static protected function InitList()
    {
        $client = new Panel();
        foreach ($client->get('/api/node/ins')['attributes']['list'] as $data) {
            self::$list[$data['uuid']] = self::fromAttributes($data);
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
