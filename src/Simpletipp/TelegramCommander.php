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

namespace Simpletipp;

use Contao\MemberModel;
use Telegram\Bot\Api;

class TelegramCommander
{
    private $telegram = null;
    private $update = null; 
    private $chat_id = null;
    private $chatMember = null;

    public function __construct($botKey)
    {
        $this->telegram   = new Api($botKey);
        $this->update     = ($this->telegram !== null) ? $this->telegram->getWebhookUpdates() : null;
        $this->chat_id    = ($this->update !== null && $this->update->getMessage() !== null) ? $this->update->getMessage()->getChat()->getId() : null;
        $this->chatMember = ($this->chat_id !== null) ? MemberModel::findOneBy('telegram_chat_id', $this->chat_id) : null;
        if ($this->update !== null) {
            
            file_put_contents('telegram-log-'.$botKey.'.txt', json_encode($this->update)."\n --- \n",  FILE_APPEND);
        } 
	}

    public function getChatId() {
        return $this->chat_id;
    }

    public function getText() {
        $text = null;
        if ($this->chat_id) {
            $text= $this->update->getMessage()->getText();
        }
        return $text;
    }

    public function getChatMember() {
        return $this->chatMember;         
    }

    public function sendInfoMessage() {
        $this->sendText("TODO Erklärung der Kommandos"); // TODO Erklärung der Kommandos
    }

    public function sendText($text) {
        $this->telegram->sendMessage(['text' => $text, 'chat_id' => $this->chat_id]);
    }

    public function chatAction($action) {
        $this->telegram->sendChatAction(['action' => $action, 'chat_id' => $this->chat_id]); 
    }

}
