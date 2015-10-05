<?php

namespace Simpletipp\BotCommands;

use Telegram\Bot\Actions;

class TippCommand extends BasicCommand
{
    /**
     * @var string Command Name
     */
    protected $name = "tipp";

    /**
     * @var string Command Description
     */
    protected $description = "Tipps abgeben";

    /**
     * @inheritdoc
     */
    public function handle($arguments)
    {
        // This will update the chat status to typing...
        $this->replyWithChatAction(Actions::TYPING);

        if (!$this->access())
        {
            $this->replyWithMessage('Chat not registered.');
        }


        // This will send a message using `sendMessage` method behind the scenes to
        // the user/chat id who triggered this command.
        // `replyWith<Message|Photo|Audio|Video|Voice|Document|Sticker|Location|ChatAction>()` all the available methods are dynamically
        // handled when you replace `send<Method>` with `replyWith` and use all their parameters except chat_id.
        // $this->replyWithPhoto('kunstrasen.jpg');


        $arrRows = ['Bayern München - Borussia Dortmund',
                    'Bisheriger Tipp: 1:2'];

        $keyboard = [
            ['0:0', '1:1', '2:2', '3:3', '4:4', '5:5'],
            ['1:0', '2:1', '2:0', '3:0', '3:1', '3:2'],
            ['0:1', '1:2', '0:2', '0:3', '1:3', '2:3'],
        ];
        $reply_markup = $this->telegram->replyKeyboardMarkup($keyboard, true, true);


        $this->replyWithMessage(implode(PHP_EOL, $arrRows), false, null, $reply_markup);

    }
}
