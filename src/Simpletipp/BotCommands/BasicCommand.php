<?php

namespace Simpletipp\BotCommands;

use Simpletipp\SimpletippModule;
use Telegram\Bot\Commands\Command;

abstract class BasicCommand extends Command
{

    /**
     * @var SimpletippModule
     */
    protected $simpletippModule = null;

    /**
     * BasicCommand constructor.
     * @param $simpletippModule
     *
     */
    public function __construct($simpletippModule)
    {
        $this->simpletippModule = $simpletippModule;
    }
}
