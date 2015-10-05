<?php

namespace Simpletipp\BotCommands;

use Simpletipp\SimpletippCallbacks;
use Telegram\Bot\Actions;

class ZitatCommand extends BasicCommand
{
    /**
     * @var string Command Name
     */
    protected $name = "zitat";

    /**
     * @var string Command Description
     */
    protected $description = "Zufälliges Zitat anzeigen";

    /**
     * @inheritdoc
     */
    public function handle($arguments)
    {
        $this->replyWithChatAction(Actions::TYPING);

        if (!$this->access())
        {
            $this->replyWithMessage('Chat not registered.');
        }

        $filename = 'files/zitate.txt';
        $fileArr  = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $index    = array_rand($fileArr);
        $str      = trim($fileArr[$index]);
        
        $this->replyWithMessage($str);
    }
}
