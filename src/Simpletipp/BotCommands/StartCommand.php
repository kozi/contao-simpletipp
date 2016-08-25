<?php

namespace Simpletipp\BotCommands;

use Contao\MemberModel;
use Telegram\Bot\Actions;

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
        $botSecret = trim($arguments);

        // This will update the chat status to typing...
        $this->replyWithChatAction(['action'=> Actions::TYPING]);

        if (strlen($botSecret) === 0)
        {
            $this->replyWithMessage(['text' => 'Missing secret key. Use link on settings page to start chat.']);
            return false;
        }

        // Search for key in tl_member
        $objMember = MemberModel::findOneBy('simpletipp_bot_secret', $botSecret);

        if ($objMember === null)
        {
            $this->replyWithMessage(['text' => 'Key not found.']);
            return false;
        }

        $chat_id = $this->update->getMessage()->getChat()->getId();
        $objMember->telegram_chat_id      = $chat_id;
        $objMember->simpletipp_bot_secret = '';
        $objMember->save();

        $tmpl = 'Chat registered for %s (%s).';
        $this->replyWithMessage(['text' => sprintf($tmpl, $objMember->firstname.' '.$objMember->lastname, $objMember->username)]);

        // Build the list
        $commands = $this->getTelegram()->getCommands();
        $response = '';
        foreach ($commands as $name => $command) {
            $response .= sprintf('/%s - %s' . PHP_EOL, $name, $command->getDescription());
        }

        // Reply with the commands list
        $this->replyWithMessage(['text' => $response]);
    }
}
