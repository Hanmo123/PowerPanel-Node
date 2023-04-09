<?php

namespace app\Framework\Command;

use app\Framework\Client\Panel;

class SetupCommand extends CommandBase
{
    public function execute()
    {
        global $argv;

        $client = new Panel($argv[2], $argv[3]);

        $config = $client->get('/api/node/config')['attributes'];
        $config['panel_endpoint'] = $argv[2];
        $config['panel_token'] = $argv[3];

        ksort($config);

        file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        echo 'Done.' . PHP_EOL;
    }
}
