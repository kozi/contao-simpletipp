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

namespace Simpletipp\Modules;

use Contao\Input;
use Contao\MemberModel;
use Simpletipp\SimpletippModule;
use Simpletipp\Models\SimpletippTeamModel;
use Simpletipp\Models\SimpletippPoints;
use Simpletipp\TelegramCommander;
use Telegram\Bot\Actions;
use SimplePie;

/**
 * Class SimpletippTelegram
 *
 * @copyright  Martin Kozianka 2014-2016
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippTelegram extends SimpletippModule
{
    private $chatMember;
    private $telegram;

    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $this->Template = new \BackendTemplate('be_wildcard');
            $this->Template->wildcard = '### SimpletippTelegram ###';
            return $this->Template->parse();
        }

        if ($this->simpletipp_telegram_url_token !== Input::get('token'))
        {
            die('Missing token');
            exit;
        }
        $this->strTemplate = $this->simpletipp_template;
        return parent::generate();
	}

    protected function compile()
    {
        $this->commander = new TelegramCommander($this->simpletipp_telegram_bot_key);
        $this->chatMember = $this->commander->getChatMember();

        $this->text = $this->commander->getText();
        if ($this->text === null) {
            // Only handle text messages
            exit;
        }

        if (strpos($this->text, "/start") === 0) {
            // Handle start command
            $this->handleStart();
        }
        elseif ($this->chatMember === null) {
            $this->commander->sendText('Chat not registered.');            
            exit;
        }

        $t = strtolower($this->text);
        switch ($t) {
            case "/h":
            case "h":
                $this->showHighscore();
                break;
            case "/hd":
            case "hd":
                $this->showHighscore(true);
                break;
            case "/t":
            case "t":
                $this->handleTipp(true);
                break;
            case "/s":                
            case "s":
                $this->showSpiele();
                break;
            case "/z":
            case "z":            
                $this->showZeigler();
                break;
            case "/c":                 
            case "c":
                $this->showZitat();
                break;               
            default:
                if(!$this->handleTipp()) {
                    $this->showHelp();
                }
        }
        exit;
    }

    private function handleTipp($isInitial = false) {
        $this->commander->chatAction(Actions::TYPING);
        $newMatchQuestion = false;
        $stackFile = TL_ROOT."/system/tmp/TELEGRAM_".$this->simpletipp_telegram_url_token.$this->chatMember->id.".spc";
        $stack     = null;
        // Den Stack holen
        if (!$isInitial && file_exists($stackFile)) {
            $stack = unserialize(file_get_contents($stackFile));
        }
        if ($isInitial) {
            $this->commander->sendText("Format: `[HEIM]:[AUSWÄRTS]`\nBeispiele: `2:0`, `1:1`, `5:0`\nSchicke einen `-` zum Überspringen oder einen `.` zum Beenden.");
            $stack = (object) [
                "lastAccess" => time(),
                "tipps"      => []
            ];
        }
        if ($stack === null || ($stack->lastAccess + 120) < time()) {
            return false;
        }

        // Tippen beenden --> stackFile löschen
        if ("." === $this->text) {
            unlink($stackFile);
            $this->commander->sendText("Tippen beendet!");
            return true;
        }

        $excludeIds = array_keys($stack->tipps);

        // Infos zur aktuellesten ID auf dem stack holen und an den Client schicken!
		$sql  = "SELECT id,title,team_h,team_a,deadline FROM tl_simpletipp_match WHERE leagueID = ? AND deadline > ?";
        $sql .= (is_array($excludeIds) && count($excludeIds) > 0) ? " AND id NOT IN (".implode($excludeIds, ",").")" : "";
        $sql .= " ORDER BY deadline ASC";

        $result = $this->Database->prepare($sql)->limit(1)->execute($this->simpletipp->leagueID, $this->now);
        if ($result->numRows == 1) {
            $match = (Object) $result->row();
            $match->teamHome = SimpletippTeamModel::findByPk($match->team_h);
            $match->teamAway = SimpletippTeamModel::findByPk($match->team_a);

		    $sql = "SELECT * FROM tl_simpletipp_tipp WHERE member_id = ? AND match_id = ?";  
            $result = $this->Database->prepare($sql)->limit(1)->execute($this->chatMember->id, $match->id);
            if ($result->numRows == 1) {
                $match->tipp = $result->tipp;
            }
            
            $stack->tipps[$match->id] = $match;

            $newMatchQuestion = sprintf("*%s*\n%s",
                $match->teamHome->short." - ".$match->teamAway->short,
                ($match->tipp) ? "Bisheriger Tipp: `".$match->tipp."`" : "" 
            );
        }

        // Tipp eintragen
        if (!$isInitial && is_array($stack->tipps) && count($stack->tipps) > 0 && $this->text !== "-") {
            // - Bedeutet aktuellen Tipp beibehalten bzw. das Spiel nicht tippen
            // TODO $this->text auswerten!
            $this->commander->sendText("`TIPP: ".$this->text."`");
            // TODO Wenn es nicht klappt die letzte id vom stack holen
            // Tippformatbeispiel: *2:1* (*.* zum Beenden. *-* zum Überspringen)
            // Die letzte ID vom stack holen damit das Spiel nochmal getippt werden kann
        }


        if ($newMatchQuestion !== false) {
            $this->commander->sendText($newMatchQuestion);
        }

        // Den stack speichern
        $stack->lastAccess = time();
        file_put_contents($stackFile, serialize($stack));

        return true;
    }

    private function showHighscore($details = false) {
        $this->commander->chatAction(Actions::TYPING);
        // Zeige den Highscore
        $highscore = $this->getHighscore();
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
            if ($details) {
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
        $return = $this->commander->sendText($result); // , "HTML"
        $this->commander->sendText(json_encode($return));
    }

    private function showSpiele() {

        // TODO Titel anzeigen!
        // TODO Spiele von anderen Benutzern anzeigen

        $this->commander->chatAction(Actions::TYPING);
        $ball     = "\xE2\x9A\xBD";
        $groupIds = [];
        $result   = $this->Database->prepare("SELECT groupID FROM tl_simpletipp_match
             WHERE leagueID = ? AND deadline < ? ORDER BY deadline DESC")
             ->limit(1)->execute($this->simpletipp->leagueID, $this->now);
        if ($result->numRows == 1) {
            $groupIds[] = $result->groupID;
        }

        $result = $this->Database->prepare("SELECT groupID FROM tl_simpletipp_match
             WHERE leagueID = ? AND deadline > ? ORDER BY deadline ASC")
             ->limit(1)->execute($this->simpletipp->leagueID, $this->now);
        if ($result->numRows == 1 && count($groupIds) === 0) {
            $groupIds[] = $result->groupID;
        }

		$sql = "SELECT
				matches.*,
				tipps.perfect AS perfect,
				tipps.difference AS difference,
				tipps.tendency AS tendency,
				tipps.tipp AS tipp
			FROM tl_simpletipp_match AS matches
		 	LEFT JOIN tl_simpletipp_tipp AS tipps ON (matches.id = tipps.match_id AND tipps.member_id = ?)
		 	WHERE matches.leagueID = ?
		 	AND (tipps.member_id = ? OR tipps.member_id IS NULL)
            AND matches.groupID IN (".implode($groupIds,",").") ORDER BY deadline ASC";
        
        $content = "";
        $result  = $this->Database->prepare($sql)->execute($this->chatMember->id, $this->simpletipp->leagueID, $this->chatMember->id);

        while ($result->next()) {
            $match = (Object) $result->row();
            $match->teamHome = SimpletippTeamModel::findByPk($match->team_h);
            $match->teamAway = SimpletippTeamModel::findByPk($match->team_a);
            $pointObj      = new SimpletippPoints($this->pointFactors, $match->perfect, $match->difference, $match->tendency);
			$match->points = $pointObj->points;

            

            //$match->teamHome->short,
            //$match->teamAway->short,

            $content .= sprintf("`%s - %s`\n`%s [%s]` %s /%s\_%s\n",
                $match->teamHome->short,
                $match->teamAway->short,
                (strlen($match->tipp) > 2) ? $match->tipp : "?:?",
                (strlen($match->result) > 2) ? $match->result : "?:?",
                str_repeat($ball, $match->points).str_repeat("\xE2\x9A\xAA", 3-$match->points),
                strtolower($match->teamHome->three),
                strtolower($match->teamAway->three)               
            );
        }

        $return = $this->commander->sendText($content);
    }

    private function handleStart() {
        $this->commander->chatAction(Actions::TYPING);

        // Chat schon registriert?
        if($this->chatMember !== null) {
            $tmpl = 'Chat already registered for %s (%s).';
            $this->commander->sendText(sprintf($tmpl, $this->chatMember->firstname.' '.$this->chatMember->lastname, $this->chatMember->username));
            return true;
        }

        // Verarbeite das Start-Kommando mit dem bot secret
        $botSecret = trim(str_replace("/start", "", $this->text));
        if (strlen($botSecret) === 0) {
            $this->commander->sendText("Missing secret key. Use link on settings page to start chat.");
            return false;
        }
        // Search for key in tl_member
        $objMember = MemberModel::findOneBy('simpletipp_bot_secret', $botSecret);
        if ($objMember === null) {
            $this->commander->sendText("Key not found.");
            return false;
        }
        $objMember->telegram_chat_id      = $this->commander->getChatId();
        $objMember->simpletipp_bot_secret = '';
        $objMember->save();

        $tmpl = 'Chat registered for %s (%s).';
        $this->commander->sendText(sprintf($tmpl, $objMember->firstname.' '.$objMember->lastname, $objMember->username));
        $this->showHelp();
        return true;
    }
    

    private function showZeigler() {
        $this->commander->chatAction(Actions::TYPING);

        $feed = new SimplePie();
        $feed->set_cache_location(TL_ROOT.'/system/tmp');
        $feed->set_feed_url('http://www.radiobremen.de/podcast/zeigler/');
        $feed->init();

        $filename = null;
        if ($item = $feed->get_item()) {
            $filename = 'zeigler-'.$item->get_date('Y-m-d').'.mp3';
            if ($enclosure = $item->get_enclosure()) {
                if (!file_exists(TL_ROOT.'/system/tmp/'.$filename)) {
                    file_put_contents(TL_ROOT.'/system/tmp/'.$filename, fopen($enclosure->get_link(), 'r'));
                }
            }
        }
        if (file_exists('system/tmp/'.$filename)) {
            // TODO Save file_id
            $this->commander->sendAudio('system/tmp/'.$filename);
            return true;
        }
        return false;
    }
    
    private function showHelp() {
        $this->commander->sendText("Hilfe anzeigen!");
        return true;
    }

    private function showZitat() {
        $this->commander->chatAction(Actions::TYPING);

        $filename = 'files/tippspiel/zitate.txt';
        $fileArr  = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $index    = array_rand($fileArr);
        $message  = trim($fileArr[$index]);

        $arr = explode(';', $message);
        if (count($arr) == 2) {
            $message = "»".$arr[0]."« (".$arr[1].")\n";
        }
        $this->commander->sendText($message);
    }
}
