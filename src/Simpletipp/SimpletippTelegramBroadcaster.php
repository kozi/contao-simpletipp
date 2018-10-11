<?php

namespace Simpletipp;

use Contao\Backend;
use Contao\ModuleModel;
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
        $moduleModel = ModuleModel::findOneBy(['tl_module.simpletipp_telegram_bot_key <> ?'], '');
        $matchModel = SimpletippMatchModel::getNextMatch();
        if ($moduleModel !== null && $matchModel !== null) {
            $this->telegram = new Api($moduleModel->simpletipp_telegram_bot_key);
            $this->match = $matchModel;
        }
        parent::__construct();
    }

    public function broadcastMessages()
    {
        if ($this->telegram === null || $this->match === null) {
            // No bot key found or no match found
            return;
        }

        $hours = 3;
        if (false && $this->match->deadline > (($hours * 3600) + time())) {
            // match starts in more than $hours
            return;
        }

        $simpletippEntries = SimpletippModel::findBy(['tl_simpletipp.leagueID = ?'], $this->match->leagueID);
        if ($simpletippEntries === null) {
            // match does not belong to a simpletipp entry
            return;
        }

        foreach ($simpletippEntries as $simpletipp) {
            $chatMember = $simpletipp->getGroupMember(true);
            foreach ($chatMember as $m) {
                $this->broadcastToUser($m);
            }
        }
    }

    private function broadcastToUser($member)
    {
        if ($member->username !== 'kozi') {
            return;
        }
        $tipp = SimpletippTippModel::findOneBy(['member_id = ?', 'match_id = ?'], [$member->id, $this->match->id]);
        if (false && $tipp !== null) {
            // already tipped
            return;
        }
        $chatId = $member->telegram_chat_id;
        $text = "Das Spiel " . $this->match->title . " startet bald! /t";
        $response = $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $text]);
        System::log("Broadcast to " . $member->username, 'SimpletippTelegramBroadcaster broadcastToUser()', 'TL_INFO');

    }
}
