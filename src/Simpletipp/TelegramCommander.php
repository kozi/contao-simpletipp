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
    private $telegram;
    private $update;    
    private $chat_id;
    private $chatMember = null;

    public function __construct($botKey)
    {
        $this->telegram = new Api($botKey);
        $this->update  = $this->telegram->getWebhookUpdates();
        $this->chat_id = $update->getMessage()->getChat()->getId();
        $this->chatMember = MemberModel::findOneBy('telegram_chat_id', $this->chat_id);
	}
    
    public function getMessage() {
        $message = $update->getMessage();
        return json_encode($message);
    }

    public function getChatMember() {
        return $this->chatMember;         
    }

    public function sendMessage($text) {
        $this->telegram->sendMessage(['chat_id' => $this->chat_id, 'text' => $text]);        
    }
}
