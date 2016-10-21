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

class HighscoreCommand extends TelegramCommand
{
    private $detailsEnabled = false;

    public function handle() {
        // Zeige den Highscore
        $highscore = $this->module->getHighscore();
        $result    = "";
        $padding   = ['index' => 0, 'points' => 0, 'sum_perfect' => 0, 'sum_difference' => 0, 'sum_tendency' => 0];
        $i         = 1;
        $list      = [];
        $arrIcon   = [
            1 => "\x31\xE2\x83\xA3",
        ];
                    
        foreach($highscore as $r) {
            $icon    = (array_key_exists($i, $arrIcon)) ? $arrIcon[$i] : " ";
            $name    = $r->firstname." ".substr($r->lastname,0,1).".";
            $list[]  = (Object) [
                "index" => $i++,
                "username" => $r->username,
                "name" =>  $name,
                "points" => $r->points,
                "sum_perfect" => $r->sum_perfect,
                "sum_difference" => $r->sum_difference,
                "sum_tendency" => $r->sum_tendency
            ];
            $padding['points'] = (strlen($r->points."") > $padding['points']) ? strlen($r->points."") : $padding['points'];
            $padding['sum_perfect'] = (strlen($r->sum_perfect."") > $padding['sum_perfect']) ? strlen($r->sum_perfect."") : $padding['sum_perfect'];
            $padding['sum_difference'] = (strlen($r->sum_difference."") > $padding['sum_difference']) ? strlen($r->sum_difference."") : $padding['sum_difference'];
            $padding['sum_tendency'] = (strlen($r->sum_tendency."") > $padding['sum_tendency']) ? strlen($r->sum_tendency."") : $padding['sum_tendency'];
        }
        $padding['index'] = strlen($i."");        

        foreach($list as $r) {
            $userCommand = "/".str_replace(".", "\_", $r->username);
            if ($this->detailsEnabled) {
                $result .= sprintf("`%s. %s[%s,%s,%s]` %s\n",
                    str_pad($r->index, $padding['index'], '0', STR_PAD_LEFT),
                    str_pad($r->points, $padding['points'], ' ', STR_PAD_LEFT),
                    str_pad($r->sum_perfect, $padding['sum_perfect'], ' ', STR_PAD_LEFT),
                    str_pad($r->sum_difference, $padding['sum_difference'], ' ', STR_PAD_LEFT),
                    str_pad($r->sum_tendency, $padding['sum_tendency'], ' ', STR_PAD_LEFT),
                    ($this->chatMember->username == $r->username) ? "*".$userCommand."*" : $userCommand
                    // ($this->commander->getChatMember()->username == $r->username) ? "\xF0\x9F\x99\x88" : "\xF0\x9F\x99\x88"
                );
            } else {
                $result .= sprintf("`%s.[%s]%s` %s %s\n",
                    str_pad($r->index, $padding['index'], '0', STR_PAD_LEFT),
                    str_pad($r->points, $padding['points'], ' ', STR_PAD_LEFT),
                    ($this->chatMember->username == $r->username) ? "*" : "",
                    ($this->chatMember->username == $r->username) ? "*".$r->name."*" : $r->name,
                    $userCommand 
                    // ($this->chatMember->username == $r->username) ? "\xF0\x9F\x99\x88" : "\xF0\x9F\x99\x88"
                );
            }
        }
        $return = $this->sendText($result);
        return true;
    }

    public function enableDetails($flag = false) {
        $this->detailsEnabled = ($flag === true);
    }
}
