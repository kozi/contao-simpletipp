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

        // TODO find chat_id in tl_member to restrict access

        $highscore = $this->simpletippModule->getHighscore();
        ob_start();
        var_dump($highscore);
        $result = ob_get_clean();
        $this->replyWithMessage($result);
        
    }
}
