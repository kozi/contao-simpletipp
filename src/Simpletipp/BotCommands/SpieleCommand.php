<?php

namespace Simpletipp\BotCommands;

use Telegram\Bot\Actions;

class SpieleCommand extends BasicCommand
{
    /**
     * @var string Command Name
     */
    protected $name = "spiele";

    /**
     * @var string Command Description
     */
    protected $description = "Spiele anzeigen";

    /**
     * @inheritdoc
     */
    public function handle($arguments)
    {
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        if (!$this->access())
        {
            return;
        }
        
        $this->replyWithMessage(['text' => "Spiele"]);
    }
}
