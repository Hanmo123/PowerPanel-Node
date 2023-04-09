<?php

namespace app\Framework;

use app\Framework\Command\SetupCommand;

class Command
{
    static public function Init()
    {
        global $argv;

        if (!isset($argv[1])) return;

        if ($argv[1] == 'setup') (new SetupCommand())->execute();
        else return;

        return true;
    }
}
