<?php

namespace Simpletipp\BotCommands;

use Simpletipp\Simpletipp;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class HighscoreCommand extends BasicCommand
{
    /**
     * @var string Command Name
     */
    protected $name = "highscore";

    /**
     * @var string Command Description
     */
    protected $description = "Tabelle anzeigen";

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

        $highscore = $this->simpletippModule->getHighscore();
        $result    = '';
        $i         = 1;

        foreach($highscore as $r)
        {
            $result .=
                str_pad($i++, 2, '0', STR_PAD_LEFT).'. '
                .$r->firstname.' '.$r->lastname.' '
                .$r->points.' ['.$r->sum_perfect.', '.$r->sum_difference.', '.$r->sum_tendency.']'
                ."\n";
        }

        $this->replyWithMessage($result);
    }
}
