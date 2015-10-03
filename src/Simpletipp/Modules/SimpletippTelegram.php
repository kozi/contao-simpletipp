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
use Telegram\Bot\Commands\HelpCommand;

use Simpletipp\SimpletippModule;
use Simpletipp\BotCommands\HighscoreCommand;
use Simpletipp\BotCommands\TippCommand;

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

        $telegram->addCommands([
            HighscoreCommand::class,
            TippCommand::class,
            HelpCommand::class
        ]);

        $telegram->commandsHandler(true);

        $update  = $telegram->getWebhookUpdates();
        file_put_contents('log.txt', json_encode($update)."\n --- \n",  FILE_APPEND);


        exit;
    }
}
