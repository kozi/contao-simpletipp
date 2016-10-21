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
use Simpletipp\Telegram\StartCommand;
use Simpletipp\Telegram\HelpCommand;
use Simpletipp\Telegram\HighscoreCommand;
use Simpletipp\Telegram\MatchesCommand;
use Simpletipp\Telegram\ZitatCommand;
use Simpletipp\Telegram\ZeiglerCommand;
use Simpletipp\Telegram\TippCommand;
use Telegram\Bot\Api;

/**
 * Class SimpletippTelegram
 *
 * @copyright  Martin Kozianka 2014-2016
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

        if ($this->simpletipp_telegram_url_token !== Input::get('token'))
        {
            die('Missing token');
        }
        $this->strTemplate = $this->simpletipp_template;
        return parent::generate();
	}

    protected function compile()
    {
        $telegram   = new Api($this->simpletipp_telegram_bot_key);
        $update     = ($telegram !== null) ? $telegram->getWebhookUpdates() : null;
        $message    = ($update !== null) ? $update->getMessage() : null;
        $text       = ($message !== null) ? strtolower($message->getText()) : null; 
        $chat_id    = ($message !== null) ? $message->getChat()->getId() : null;
        $chatMember = ($chat_id !== null) ? MemberModel::findOneBy('telegram_chat_id', $chat_id) : null;
        
        if (is_string($text) && strpos($text, "/start") === 0) {
            // Handle start command
            $command = new StartCommand($telegram, $this, $message);
            $command->handle();
            exit;
        }
        elseif ($chatMember === null) {
            $telegram->sendMessage(['text' => 'Chat not registered.', 'chat_id' => $chat_id]);
            exit;
        }

        switch ($text) {
            case "/hilfe":
            case "hilfe":
            case "/help":
            case "help":
            case "?":
                $command = new HelpCommand($telegram, $this, $message, $chatMember);
            case "/h":
            case "h":
                $command = new HighscoreCommand($telegram, $this, $message, $chatMember);
                break;
            case "/hd":
            case "hd":
                $command = new HighscoreCommand($telegram, $this, $message, $chatMember);
                $command->enableDetails(true);
                break;
            case "/t":
            case "t":
                $command = new TippCommand($telegram, $this, $message, $chatMember);
                $command->isInitial(true);
                break;
            case "/s":                
            case "s":
                $command = new MatchesCommand($telegram, $this, $message, $chatMember);
                break;
            case "/z":
            case "z":            
                $command = new ZeiglerCommand($telegram, $this, $message, $chatMember);
                break;
            case "/c":                 
            case "c":
                $command = new ZitatCommand($telegram, $this, $message, $chatMember);
                break;               
            default:
                $command = new TippCommand($telegram, $this, $message, $chatMember);            
        }
                    
        $command->handleCommand();
        exit;
    }
}
