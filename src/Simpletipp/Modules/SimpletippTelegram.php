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
            $commander->sendText('Chat not registered.');            
            exit;
        }
        
        $this->text = $commander->getText();
        if ($this->text === null) {
            // Only handle text messages
            exit;
        }
        
        $t = strtolower($this->text);
        switch ($t) {
            case "h":
                $this->showHighscore();
                break;
            case "t":
                $this->handleTipp(true);
                break;
            case "s":
                $this->showSpiele();
                break;
            default:
                if ("/start" === $commander->messagePrefix()) {
                    // Handle start command
                    $this->handleStart();
                }
                elseif(true) { // TODO Check if match_id is correct and "fresh"
                    $this->handleTipp();
                }
        }
        exit;
    }

    private function handleStart() {
        // Verarbeite das Start-Kommando mit dem bot secret 
    }

    private function handleTipp() {
        // Trage einen Tipp ein und zeige das nächste Spiel
    }

    private function showHighscore() {
        // Zeige den Highscore
    }

    private function showSpiele() {
        // Zeige die Spiele des aktuellen Spieltags      
    }
    
}
