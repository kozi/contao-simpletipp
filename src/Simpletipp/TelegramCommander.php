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
	}
    
    public function getMessage() {
        $message = $this->update->getMessage();
        return json_encode($message);
    }

    public function getChatMember() {
        return $this->chatMember;         
    }

    public function sendText($text) {
        if ($this->chat_id !== null) {
            $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text]);
        }
    }
}
