<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2016 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2016 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Telegram;

class HelpCommand extends TelegramCommand
{
    protected function handle() {
        $this->sendText(self::helpMessage());
    }

    public static function helpMessage() {
        $message = "Die folgenden Kommandos stehen zur Verfügung
        
            *H* /h Zeige die Tabelle (*hd* /hd Details) 
            *S* /s Zeige die aktuellen Spiele (*sn* /sn Nächster)
            *T* /t Tipps abgeben
            *C* /c Zeige ein zufälliges Zitat
            *Z* /z Lade die aktuelle Zeigler-Folge 
        ";
        return $message; 
    }


    }
}
