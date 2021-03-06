<?php

namespace Simpletipp\Telegram;

use SimplePie;

class ZeiglerCommand extends TelegramCommand
{
    protected function handle()
    {
        $feed = new SimplePie();
        $feed->set_cache_location(TL_ROOT . '/system/tmp');
        $feed->set_feed_url('http://www.radiobremen.de/podcast/zeigler/');
        $feed->init();

        $filename = null;
        if ($item = $feed->get_item()) {
            $filename = 'zeigler-' . $item->get_date('Y-m-d') . '.mp3';
            if ($enclosure = $item->get_enclosure()) {
                if (!file_exists(TL_ROOT . '/system/tmp/' . $filename)) {
                    file_put_contents(TL_ROOT . '/system/tmp/' . $filename, fopen($enclosure->get_link(), 'r'));
                }
            }
        }
        if (file_exists('system/tmp/' . $filename)) {
            // TODO Save file_id
            $this->sendAudio('system/tmp/' . $filename);
            return true;
        }
        return false;
    }
}
