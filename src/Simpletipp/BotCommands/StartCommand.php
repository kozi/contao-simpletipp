<?php

namespace Simpletipp\BotCommands;

use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class StartCommand extends BasicCommand
{
    /**
     * @var string Command Name
     */
    protected $name = "start";

    /**
     * @var string Command Description
     */
    protected $description = "Chat mit dem Tippspiel starten";

    /**
     * @inheritdoc
     */
    public function handle($arguments)
    {

        // This will update the chat status to typing...
        $this->replyWithChatAction(Actions::TYPING);

        $commands = $this->getTelegram()->getCommands();

        // Build the list
        $response = '';
        foreach ($commands as $name => $command) {
            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        // Reply with the commands list
        $this->replyWithMessage($response);
    }
}
