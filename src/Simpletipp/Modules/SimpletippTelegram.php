<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2015 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2015 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Modules;

use Contao\Input;
use Telegram\Bot\Api;

use Simpletipp\SimpletippModule;

use Simpletipp\BotCommands\HighscoreCommand;
use Simpletipp\BotCommands\StartCommand;
use Simpletipp\BotCommands\TippCommand;
use Simpletipp\BotCommands\SpieleCommand;
use Simpletipp\BotCommands\ZitatCommand;

/**
 * Class SimpletippTelegram
 *
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippTelegram extends SimpletippModule
{
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $this->Template = new \BackendTemplate('be_wildcard');
            $this->Template->wildcard = '### SimpletippTelegram ###';
            return $this->Template->parse();
        }
        $this->strTemplate = $this->simpletipp_template;
        return parent::generate();
	}

	protected function compile()
    {
        if ($this->simpletipp_telegram_url_token !== Input::get('token'))
        {
            die('Missing token');
            exit;
        }

        $telegram = new Api($this->simpletipp_telegram_bot_key);

        $telegram->addCommand(new HighscoreCommand($this));
        $telegram->addCommand(new TippCommand($this));
        $telegram->addCommand(new StartCommand($this));
        $telegram->addCommand(new SpieleCommand($this));
        $telegram->addCommand(new ZitatCommand($this));

        $telegram->commandsHandler(true);

        $update  = $telegram->getWebhookUpdates();
        file_put_contents('telegram-log.txt', json_encode($update)."\n --- \n",  FILE_APPEND);


        exit;
    }
}
