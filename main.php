<?php

use app\Framework\Server;

use function Co\run;

require_once __DIR__ . '/vendor/autoload.php';

run(function () {
    Server::Boot();
});
