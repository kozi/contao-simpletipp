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

        $this->replyWithChatAction(['action' => Actions::TYPING]);

        if (!$this->access())
        {
            return;
        }

        $feed = new SimplePie();
        $feed->set_cache_location(TL_ROOT.'/system/tmp');
        $feed->set_feed_url($url);
        $feed->init();

        $filename = null;
        if ($item = $feed->get_item())
        {
            $filename = 'zeigler-'.$item->get_date('Y-m-d').'.mp3';
            if ($enclosure = $item->get_enclosure())
            {
                if (!file_exists(TL_ROOT.'/system/tmp/'.$filename))
                {
                    file_put_contents(TL_ROOT.'/system/tmp/'.$filename, fopen($enclosure->get_link(), 'r'));
                }
            }
        }


        if (file_exists('system/tmp/'.$filename))
        {
            // TODO Save file_id
            $this->replyWithAudio(['audio' => 'system/tmp/'.$filename]);
        }

    }
}
