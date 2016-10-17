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
use Simpletipp\SimpletippModule;
use Simpletipp\TelegramCommander;

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
        $commander = new TelegramCommander($this->simpletipp_telegram_bot_key);

        if ($commander->getChatMember() === null) {
            $commander->sendMessage('Chat not registered.');            
            exit;
        }

        if ("t" === strtolower($text))
        {
            $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Geht noch nicht!']);
            exit;
        }
        
        $this->telegram->sendMessage($this->telegram->getMessage());

        $this->telegram->sendMessage(['chat_id' => $chat_id, 'text' => 'Schicke ein T um Spiele zu tippen!']);
        
        file_put_contents('telegram-log-'.$this->simpletipp_telegram_url_token.'.txt', json_encode($update)."\n --- \n",  FILE_APPEND);
        exit;
    }
}
