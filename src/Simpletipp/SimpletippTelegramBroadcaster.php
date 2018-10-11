<?php

namespace Simpletipp;

use Contao\Backend;
use Contao\System;
use Simpletipp\Models\SimpletippMatchModel;
use Simpletipp\Models\SimpletippModel;

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
    private $botKey = null;
    private $match = null;

    public function __construct()
    {
        $module = SimpletippModel::findOneBy(['tl_module.simpletipp_telegram_bot_key <> ?'], '');
        if ($module !== null) {
            $this->botKey = $module->simpletipp_telegram_bot_key;
            $this->match = SimpletippMatchModel::getNextMatch();
        }
        parent::__construct();
    }

    public function broadcastMessages()
    {
        if ($this->botKey === null || $this->match === null) {
            // No botKey found or no match found
            return;
        }

        $simpletippEntries = SimpletippModel::findBy(['tl_simpletipp.leagueID = ?'], $match->leagueID);
        if ($simpletippEntries === null) {
            // match does not belong to a simpletipp entry
            return;
        }
        foreach ($simpletippEntries as $simpletipp) {
            $chatMember = $simpletipp->getGroupMember(true);
            foreach ($chatMember as $member) {
                broadcastToUser($member, $match);
            }
        }
    }

    private function broadcastToUser($member)
    {
        System::log(json_encode([$member->username, $this->match->row()]), 'SimpletippTelegramBroadcaster broadcastToUser()', 'TL_INFO');
    }
}
