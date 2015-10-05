<?php

namespace Simpletipp\BotCommands;

use Contao\MemberModel;
use Simpletipp\SimpletippModule;
use Telegram\Bot\Commands\Command;

abstract class BasicCommand extends Command
{
    /**
     * @var MemberModel
     */
    protected $member = null;

    /**
     * @var int
     */
    protected $chat_id = null;
    /**
     * @var SimpletippModule
     */
    protected $simpletippModule = null;

    /**
     * BasicCommand constructor.
     * @param $simpletippModule
     *
     */
    public function __construct($simpletippModule)
    {
        $this->simpletippModule = $simpletippModule;
    }

    protected function access()
    {
        $this->chat_id = $this->update->getMessage()->getChat()->getId();

        // Search for key in tl_member
        $objMember = MemberModel::findOneBy('telegram_chat_id', $this->chat_id);

        if ($objMember === null)
        {
            return false;
        }

        $this->member = $objMember;
        return true;
    }

}
