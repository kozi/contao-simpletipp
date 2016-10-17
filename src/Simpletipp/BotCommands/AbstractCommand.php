<?php

namespace Simpletipp\TelegramCommand;

use Contao\MemberModel;
use Simpletipp\SimpletippModule;
use Simpletipp\TelegramCommander;

abstract class AbstractCommand
{
    /**
     * @var TelegramCommander
     */
    protected $commander = null;
    /**
     * @var SimpletippModule
     */
    private $simpletippModule = null;

    /**
     * BasicCommand constructor.
     * @param $simpletippModule
     * @param $commander     
     */
    public function __construct($simpletippModule, $commander)
    {
        $this->simpletippModule = $simpletippModule;
        $this->commander = $commander;
    }

}
