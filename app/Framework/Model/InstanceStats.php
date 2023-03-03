<?php

namespace app\Framework\Model;

class InstanceStats
{
    /**
     * CPU 用量百分比
     *
     * @var float
     */
    public float $cpu = 0;

    /**
     * 内存用量 以字节为单位
     *
     * @var integer
     */
    public int $memory = 0;

    /**
     * 硬盘用量 以字节为单位
     *
     * @var integer
     */
    public int $disk = 0;

    public function update(array $chunk)
    {
        if (isset($chunk['precpu_stats']['system_cpu_usage'])) {
            // CPU 用量计算需要 1 个以上的 chunk
            $this->cpu = ($chunk['cpu_stats']['cpu_usage']['total_usage'] - $chunk['precpu_stats']['cpu_usage']['total_usage']) // cpu_delta 
                / ($chunk['cpu_stats']['system_cpu_usage']  - $chunk['precpu_stats']['system_cpu_usage'])                       // system_cpu_delta
                * $chunk['cpu_stats']['online_cpus']                                                                            // number_cpus
                * 100;
        }
        $this->memory = $chunk['memory_stats']['usage'] - ($chunk['memory_stats']['stats']['cache'] ?? 0);
    }

    public function toArray()
    {
        return [
            'cpu' => round($this->cpu, 1),
            'memory' => $this->memory,
            'disk' => $this->disk
        ];
    }
}
