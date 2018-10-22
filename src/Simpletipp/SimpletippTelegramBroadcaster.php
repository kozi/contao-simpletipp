<?php

namespace Simpletipp;

use Contao\Backend;
use Contao\System;
use Simpletipp\Models\SimpletippMatchModel;
use Simpletipp\Models\SimpletippModel;
use Simpletipp\Models\SimpletippTippModel;
use Telegram\Bot\Api;

/**
 * Class SimpletippTelegramBroadcaster
 *
 * Broadcast messages to registered telegram user
 * @copyright  Martin Kozianka 2014-2018
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippTelegramBroadcaster extends Backend
{
    private $telegram = null;
    private $match = null;

    public function __construct()
    {
        $simpletippModel = SimpletippModel::findBy(['tl_simpletipp.telegram_bot_key <> ?'], '');
        $matchModel = ($simpletippModel != null) ? SimpletippMatchModel::getNextMatch($simpletippModel->leagueID) : null;
        if ($matchModel !== null) {
            $this->telegram = new Api($simpletippModel->telegram_bot_key);
            $this->match = $matchModel;
        }
        parent::__construct();
    }

    public function broadcastMessages()
    {
        if ($this->telegram === null || $this->match === null) {
            // No bot key found or no match found
            System::log('No bot key found or no match found', 'SimpletippTelegramBroadcaster broadcastToUser()', 'TL_ERROR');
            return;
        }

        $hours = 3;
        if ($this->match->deadline > (($hours * 3600) + time())) {
            // match starts in more than $hours
            System::log("Match '" . $this->match->title . "' starts in more than $hours hours", 'SimpletippTelegramBroadcaster broadcastToUser()', 'TL_ERROR');
            return;
        }

        foreach ($simpletippEntries as $simpletipp) {
            $chatMember = $simpletipp->getGroupMember(true);
            if ($chatMember != null) {
                $broadcastResult = [];
                foreach ($chatMember as $m) {
                    $result = $this->broadcastToUser($m);
                    $broadcastResult[] = $m->username . (($result === true) ? "[1]" : "[0]");
                }
                System::log("Broadcast result: " . implode(", ", $broadcastResult), 'SimpletippTelegramBroadcaster broadcastToUser()', 'TL_INFO');
            }
        }
    }

    private function broadcastToUser($mem)
    {
        $tipp = SimpletippTippModel::findOneBy(['member_id = ?', 'match_id = ?'], [$mem->id, $this->match->id]);
        if ($tipp !== null) {
            // already tipped
            return false;
        }
        $text = "Das Spiel " . $this->match->title . " startet bald! /t";
        $this->telegram->sendMessage(['chat_id' => $mem->telegram_chat_id, 'text' => $text]);
        System::log("Broadcast to " . $mem->username . ' [' . $text . ']', 'SimpletippTelegramBroadcaster broadcastToUser()', 'TL_INFO');
        return true;
    }
}
