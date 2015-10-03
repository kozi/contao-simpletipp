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

use \Simpletipp\SimpletippModule;
use \Telegram\Bot\Api;

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
        $telegram = new Api($key);

        // $response = $telegram->removeWebhook();
        // $response = $telegram->setWebhook($link);
        // var_dump($response);

        $telegram->addCommands([
            TippspielBot\Commands\HighscoreCommand::class,
            TippspielBot\Commands\TippCommand::class,
            Telegram\Bot\Commands\HelpCommand::class
        ]);

        $update = $telegram->getWebhookUpdates();

        $message = json_encode($update);
        file_put_contents('log.txt', $message."\n --- \n",  FILE_APPEND);

        $telegram->commandsHandler(true);

    }
}
