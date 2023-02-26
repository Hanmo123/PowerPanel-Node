<?php

namespace app\Framework\Wrapper;

use Swoole\Coroutine\Channel;
use Swoole\Http\Response as SwooleResponse;

use function Co\go;

class Response
{
    /**
     * 消息队列 解决协程同时发送报错问题
     *
     * @var Channel
     */
    protected Channel $channel;

    protected array $attributes = [];

    public function __construct(
        public SwooleResponse $response
    ) {
        $this->channel = new Channel(16);

        // 处理消息队列 发送消息
        go(function () {
            while (true) {
                $data = $this->channel->pop();
                $this->response->push($data);
            }
        });
    }

    public function send(array $data)
    {
        $this->channel->push(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value)
    {
        $this->attributes[$key] = $value;
    }
}
