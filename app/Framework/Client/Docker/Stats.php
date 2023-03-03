<?php

namespace app\Framework\Client\Docker;

use app\Framework\Util\Config;
use CurlHandle;

class Stats
{
    public function handle(string $url, callable $callback)
    {
        [$scheme, $endpoint] = explode('://', Config::Get()['docker']['socket']);

        $ch = curl_init('http://localhost' . $url);
        if ($scheme == 'unix') curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function (CurlHandle $ch, string $data) use ($callback) {
            $callback($ch, json_decode($data, true));
            return strlen($data);
        });
        curl_exec($ch);
        curl_close($ch);
    }
}
