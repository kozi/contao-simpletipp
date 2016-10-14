<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2016 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2016 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Modules;

use Contao\Input;
use Contao\MemberModel;

use Telegram\Bot\Api;

use Simpletipp\SimpletippModule;
use Simpletipp\BotCommands\StartCommand;

/**
 * Class SimpletippTelegram
 *
 * @copyright  Martin Kozianka 2014-2016
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippTelegram extends SimpletippModule
{
    private $chatMember;
    private $telegram;

    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $this->Template = new \BackendTemplate('be_wildcard');
            $this->Template->wildcard = '### SimpletippTelegram ###';
            return $this->Template->parse();
        }

        if ($this->simpletipp_telegram_url_token !== Input::get('token'))
        {
            die('Missing token');
            exit;
        }
        $this->strTemplate = $this->simpletipp_template;
        return parent::generate();
	}

	protected function compile()
    {
        $this->telegram = new Api($this->simpletipp_telegram_bot_key); 
        $this->telegram->addCommand(new StartCommand());
        $this->telegram->commandsHandler(true);

        $update  = $this->telegram->getWebhookUpdates();
        $chat_id = $update->getMessage()->getChat()->getId();
        $this->chatMember = MemberModel::findOneBy('telegram_chat_id', $chat_id);
        if ($this->chatMember === null)
        {
            $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Chat not registered.']);            
            exit;
        }
        
        $text = $update->getMessage()->getText();

        if ("t" === strtolower($text))
        {
            $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Geht noch nicht!']);
            exit;
        }

        $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Schicke ein T um Spiele zu tippen!']);

        file_put_contents('telegram-log.txt', json_encode($update)."\n --- \n",  FILE_APPEND);
        exit;
    }
}
