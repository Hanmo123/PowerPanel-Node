<?php

namespace app\Framework;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class Logger
{
    static private $path = __DIR__ . '/../../runtime/node.log';

    static private MonologLogger $logger;

    static public function Init()
    {
        $logger = new MonologLogger('App');

        $formatter = new LineFormatter('[%datetime%] [%channel%] [%level_name%] %message%' . PHP_EOL, 'Y-m-d H:i:s');

        $handler = new RotatingFileHandler(self::$path, 7, Level::Debug);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        $handler = new StreamHandler(STDOUT, Level::Debug);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        self::$logger = $logger;
    }

    static public function Get($name = NULL): MonologLogger
    {
        return $name ? self::$logger->withName($name) : self::$logger;
    }
}
