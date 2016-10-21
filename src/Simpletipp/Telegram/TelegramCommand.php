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

namespace Simpletipp\Telegram;

use Contao\MemberModel;

abstract class TelegramCommand
{
    private $telegram = null;

    protected $module     = null;
    protected $chatMember = null;
    protected $text       = null;
    protected $chat_id    = null;

    public function __construct($telegram, $module, $message, $chatMember = null)
    {
        $this->module     = $module;
        $this->telegram   = $telegram;
        $this->text       = ($message !== null) ? $message->getText() : null;
        $this->chat_id    = ($message !== null) ? $message->getChat()->getId() : null;
        $this->chatMember = $chatMember;

        if ($this->message !== null) {
            $fn = "system/logs/".preg_replace('/[^a-zA-Z0-9-_.]/', '', 'telegram-log-'.$telegram->getAccessToken().'.log');
            file_put_contents($fn, json_encode($message)."\n --- \n",  FILE_APPEND);
        } 
	}

    protected function sendInfoMessage() {
        return $this->sendText("TODO Erklärung der Kommandos"); // TODO Erklärung der Kommandos
    }
    
    protected function sendText($text, $parse_mode = 'Markdown') {
        return $this->telegram->sendMessage(['text' => $text, 'parse_mode' => $parse_mode, 'chat_id' => $this->chat_id]);
    }

    protected function chatAction($action) {
        return $this->telegram->sendChatAction(['action' => $action, 'chat_id' => $this->chat_id]); 
    }
    
    protected function sendAudio($audioFile) {
        return $this->telegram->sendAudio(['audio' => $audioFile, 'chat_id' => $this->chat_id]);
    }

}
