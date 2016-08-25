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
        $this->replyWithChatAction(Actions::TYPING);

        if (!$this->access())
        {
            return;
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

        $this->replyWithMessage(['text' => "Spiele"]);
    }
}
