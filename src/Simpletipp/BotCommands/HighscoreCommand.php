<?php

namespace Simpletipp\BotCommands;

use Telegram\Bot\Actions;

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
        $this->replyWithChatAction(['action' => Actions::TYPING]);

        if (!$this->access())
        {
            return;
        }

        $highscore = $this->simpletippModule->getHighscore();
        $result    = '';
        $i         = 1;

        foreach($highscore as $r)
        {
            $isU     = ($this->member->id == $r->id);
            $result .=
                str_pad($i++, 2, '0', STR_PAD_LEFT).'. '
                .$r->firstname.' '.$r->lastname." → "
                .$r->points.' ['.$r->sum_perfect.', '.$r->sum_difference.', '.$r->sum_tendency.']'
                .(($isU) ? " ★\n" : "\n");
        }

        $this->replyWithMessage(['text' => $result]);
    }
}
