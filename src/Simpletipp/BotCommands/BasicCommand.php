<?php

namespace Simpletipp\BotCommands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

abstract class BasicCommand extends Command
{
    protected $simpletipp = null;

    /**
     * BasicCommand constructor.
     * @param $simpletipp
     *
     */
    public function __construct($simpletipp)
    {
        $this->simpletipp = $simpletipp;
    }
}
