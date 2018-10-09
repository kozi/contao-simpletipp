<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2018 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2018 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Telegram;

class ZitatCommand extends TelegramCommand
{
    protected function handle()
    {
        $filename = 'files/tippspiel/zitate.txt';
        $fileArr = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $index = array_rand($fileArr);
        $message = trim($fileArr[$index]);

        $arr = explode(';', $message);
        if (count($arr) == 2) {
            $message = "»" . $arr[0] . "« (" . $arr[1] . ")\n";
        }
        $this->sendText($message);
        return true;
    }
}
