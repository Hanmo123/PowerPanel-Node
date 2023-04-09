<?php

use app\Framework\Command;
use app\Framework\Logger;
use app\Framework\Model\Instance;
use app\Framework\Plugin;
use app\Framework\Server;

use function Co\run;

require_once __DIR__ . '/vendor/autoload.php';

run(function () {
    Logger::Init();
    Plugin::Load();
    if (Command::Init()) return;
    Instance::Init();
    Server::Boot();
});
