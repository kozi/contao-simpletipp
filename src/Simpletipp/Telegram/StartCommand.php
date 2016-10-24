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
use Simpletipp\Telegram\HelpCommand;

class StartCommand extends TelegramCommand
{
    protected function handle() {
        // Chat schon registriert?
        if($this->chatMember !== null) {
            $tmpl = 'Chat already registered for %s (%s).';
            $this->sendText(sprintf($tmpl, $this->chatMember->firstname.' '.$this->chatMember->lastname, $this->chatMember->username));
            $this->sendText(HelpCommand::helpMessage());
            return true;
        }

        // Verarbeite das Start-Kommando mit dem bot secret
        $botSecret = trim(str_replace("/start", "", $this->text));
        if (strlen($botSecret) === 0) {
            $this->sendText("Missing secret key. Use link on settings page to start chat.");
            return false;
        }
        // Search for key in tl_member
        $objMember = MemberModel::findOneBy('simpletipp_bot_secret', $botSecret);
        if ($objMember === null) {
            $this->sendText("Key not found.");
            return false;
        }
        $objMember->telegram_chat_id      = $this->chat_id;
        $objMember->simpletipp_bot_secret = '';
        $objMember->save();

        $tmpl = 'Chat registered for %s (%s).';
        $this->sendText(sprintf($tmpl, $objMember->firstname.' '.$objMember->lastname, $objMember->username));
        
        $this->sendText(HelpCommand::helpMessage());

        return true;

    }
    
}
