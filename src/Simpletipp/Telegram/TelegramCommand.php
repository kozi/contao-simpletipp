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
use Telegram\Bot\Actions;

abstract class TelegramCommand
{
    private $resetTippStack = true;
    private $filenameTippStack = null;
    private $telegram = null;

    
    protected $module     = null;
    protected $chatMember = null;
    protected $text       = null;
    protected $chat_id    = null;
    protected $now        = null;

    public function __construct($telegram, $module, $message, $chatMember = null)
    {
        $this->module     = $module;
        $this->telegram   = $telegram;
        $this->text       = ($message !== null) ? $message->getText() : null;
        $this->chat_id    = ($message !== null) ? $message->getChat()->getId() : null;
        $this->chatMember = $chatMember;
        $this->now        = time();

        $fnPrefix = "TELEGRAM-".preg_replace('/[^a-zA-Z0-9-_.]/', '', $telegram->getAccessToken());

        if ($this->chatMember->id !== null) {
            $this->filenameTippStack = TL_ROOT."/system/tmp/".$fnPrefix.$this->chatMember->id.".spc";
        }
        if ($message !== null) {
            $this->chatAction(Actions::TYPING);
            file_put_contents("system/logs/".$fnPrefix.".log", json_encode($message)."\n --- \n",  FILE_APPEND);
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

    protected function getTippStack($initial = false) {
        $stack = null;
        if ($initial === true) {
            $stack = (object) ["lastAccess" => time(), "tipps" => []];
        }
        elseif ($this->filenameTippStack !== null && file_exists($this->filenameTippStack)) {
            $stack = unserialize(file_get_contents($this->filenameTippStack));
        }
        return $stack;
    }
   
    protected function saveTippStack($stack = null) {
        if ($this->filenameTippStack === null || $stack === null) {
            return false;
        }
        // Den stack speichern
        $stack->lastAccess = time();
        file_put_contents($this->filenameTippStack, serialize($stack));
        return true;
    }

    protected function deleteTippStack() {
        if ($this->filenameTippStack === null) {
            return false;
        }
        unlink($this->filenameTippStack);
        return true;
    }

    abstract protected function handle();

    public function handleCommand() {
        $this->handle();
        // Reset (delete) tipp stack if other commands are called
        if ($this->resetTippStack) {
            $this->deleteTippStack();
        }
    }

    protected function preserveTippStack() {
        $this->resetTippStack = false;
    } 

    
}
