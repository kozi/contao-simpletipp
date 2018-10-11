<?php

namespace Simpletipp;

use Contao\Backend;
use Contao\MemberModel;
use Contao\System;

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
    public function broadcastMessages()
    {
        $names = [];
        $objMembers = MemberModel::findBy(['tl_member.telegram_chat_id <> ?'], '');
        if ($objMembers !== null) {
            foreach ($objMembers as $objMember) {
                $names[] = $objMember->username;
            }
        }
        System::log(json_encode($names), 'SimpletippTelegramBroadcaster broadcastMessages()', 'TL_INFO');
    }
}
