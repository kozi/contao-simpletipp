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
use Telegram\Bot\Actions;
use SimplePie;

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
        $this->commander = new TelegramCommander($this->simpletipp_telegram_bot_key);

        $this->text = $this->commander->getText();
        if ($this->text === null) {
            // Only handle text messages
            exit;
        }

        if (strpos($this->text, "/start") === 0) {
            // Handle start command
            $this->handleStart();
        }
        elseif ($this->commander->getChatMember() === null) {
            $this->commander->sendText('Chat not registered.');            
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
            case "z":
                $this->showZeigler();
                break; 
            case "c":
                $this->showZitat();
                break;               
            default:
                if(false) { // TODO Check if match_id is correct and "fresh"
                    $this->handleTipp();
                }
                else {
                    // Do something funny!
                    // Foto, Zitat, Sticker... 
                }
        }
        exit;
    }

    private function handleTipp($isInitial = false) {
        $this->commander->chatAction(Actions::TYPING);
        
        // Trage einen Tipp ein und zeige das nächste Spiel
    }

    private function showHighscore() {
        $this->commander->chatAction(Actions::TYPING);
        // Zeige den Highscore
        $highscore = $this->getHighscore();
        $result    = "<pre>";
        $padding   = ['index' => 0, 'username' => 0, 'points' => 0, 'sum_perfect' => 0, 'sum_difference' => 0, 'sum_tendency' => 0];
        $i         = 1;
        $list      = [];
        $arrIcon   = [
            1 => "\x31\xE2\x83\xA3",
        ];
                    
        foreach($highscore as $r) {
            $icon    = (array_key_exists($i, $arrIcon)) ? $arrIcon[$i] : " ";
            $list[]  = [
                $i++,
                $r->username,
                $r->points,
                $r->sum_perfect,
                $r->sum_difference,
                $r->sum_tendency
            ];
            $padding['username'] = (strlen($r->username."") > $padding['username']) ? strlen($r->username."") : $padding['username'];
            $padding['points'] = (strlen($r->points."") > $padding['points']) ? strlen($r->points."") : $padding['points'];
            $padding['sum_perfect'] = (strlen($r->sum_perfect."") > $padding['sum_perfect']) ? strlen($r->sum_perfect."") : $padding['sum_perfect'];
            $padding['sum_difference'] = (strlen($r->sum_difference."") > $padding['sum_difference']) ? strlen($r->sum_difference."") : $padding['sum_difference'];
            $padding['sum_tendency'] = (strlen($r->sum_tendency."") > $padding['sum_tendency']) ? strlen($r->sum_tendency."") : $padding['sum_tendency'];
        }
        $padding['index'] = strlen($i."");        

        foreach($list as $r) {
            $result .= sprintf("%s. %s %s%s\n",
                str_pad($r[0], $padding['index'], '0', STR_PAD_LEFT),
                str_pad($r[1], $padding['username'], ' ', STR_PAD_RIGHT),
                str_pad($r[2], $padding['points'], ' ', STR_PAD_LEFT),
                /* [%s,%s,%s] ---  str_pad($r[3], $padding['sum_perfect'], ' ', STR_PAD_LEFT),
                str_pad($r[4], $padding['sum_difference'], ' ', STR_PAD_LEFT),
                str_pad($r[5], $padding['sum_tendency'], ' ', STR_PAD_LEFT)*/
                ($this->commander->getChatMember()->username == $r[1]) ? " \xF0\x9F\x99\x88" : ""
            );
        }
        $return = $this->commander->sendText($result."</pre>", "HTML");
        $this->commander->sendText(json_encode($return));
    }

    private function showSpiele() {
        $this->commander->chatAction(Actions::TYPING);

        
        // Zeige die Spiele des aktuellen Spieltags      
    }

    private function handleStart() {
        $this->commander->chatAction(Actions::TYPING);

        // Chat schon registriert?
        if($this->commander->getChatMember() !== null) {
            $objMember = $this->commander->getChatMember();
            $tmpl = 'Chat already registered for %s (%s).';
            $this->commander->sendText(sprintf($tmpl, $objMember->firstname.' '.$objMember->lastname, $objMember->username));
            return true;
        }

        // Verarbeite das Start-Kommando mit dem bot secret
        $botSecret = trim(str_replace("/start", "", $this->text));
        if (strlen($botSecret) === 0) {
            $this->commander->sendText("Missing secret key. Use link on settings page to start chat.");
            return false;
        }
        // Search for key in tl_member
        $objMember = MemberModel::findOneBy('simpletipp_bot_secret', $botSecret);
        if ($objMember === null) {
            $this->commander->sendText("Key not found.");
            return false;
        }
        $objMember->telegram_chat_id      = $this->commander->getChatId();
        $objMember->simpletipp_bot_secret = '';
        $objMember->save();

        $tmpl = 'Chat registered for %s (%s).';
        $this->commander->sendText(sprintf($tmpl, $objMember->firstname.' '.$objMember->lastname, $objMember->username));
        $this->commander->sendInfoMessage();
        return true;
    }
    

    private function showZeigler() {
        $this->commander->chatAction(Actions::TYPING);

        $feed = new SimplePie();
        $feed->set_cache_location(TL_ROOT.'/system/tmp');
        $feed->set_feed_url('http://www.radiobremen.de/podcast/zeigler/');
        $feed->init();

        $filename = null;
        if ($item = $feed->get_item()) {
            $filename = 'zeigler-'.$item->get_date('Y-m-d').'.mp3';
            if ($enclosure = $item->get_enclosure()) {
                if (!file_exists(TL_ROOT.'/system/tmp/'.$filename)) {
                    file_put_contents(TL_ROOT.'/system/tmp/'.$filename, fopen($enclosure->get_link(), 'r'));
                }
            }
        }
        if (file_exists('system/tmp/'.$filename)) {
            // TODO Save file_id
            $this->commander->sendAudio('system/tmp/'.$filename);
            return true;
        }
        return false;
    }

    private function showZitat() {
        $this->commander->chatAction(Actions::TYPING);

        $filename = 'files/tippspiel/zitate.txt';
        $fileArr  = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $index    = array_rand($fileArr);
        $message  = trim($fileArr[$index]);

        $arr = explode(';', $message);
        if (count($arr) == 2) {
            $message = "»".$arr[0]."« (".$arr[1].")\n";
        }
        $this->commander->sendText($message);
    }
}
