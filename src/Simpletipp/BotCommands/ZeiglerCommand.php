<?php

namespace Simpletipp\BotCommands;

use Telegram\Bot\Actions;
use SimplePie;

class ZeiglerCommand extends BasicCommand
{
    /**
     * @var string Command Name
     */
    protected $name = "zeigler";

    /**
     * @var string Command Description
     */
    protected $description = "Aktuelle Zeigler Ausgabe abrufen";

    /**
     * @inheritdoc
     */
    public function handle($arguments)
    {
        $cache = TL_ROOT.'/system/tmp';
        $url   = 'http://www.radiobremen.de/podcast/zeigler/';

        $this->replyWithChatAction(Actions::TYPING);

        if (!$this->access())
        {
            $this->replyWithMessage('Chat not registered.');
        }

        $this->replyWithMessage($cache);
        $this->replyWithMessage($url);

        $feed = new SimplePie();
        $feed->set_cache_location(TL_ROOT.'/system/tmp');
        $feed->set_feed_url($url);
        $feed->init();

        $item = $feed->get_item();

    }
}
