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

class TippCommand extends TelegramCommand
{
    private $isInitial = false;

    public function handle() {
        $this->commander->chatAction(Actions::TYPING);
        $newMatchQuestion = false;
        $stackFile = TL_ROOT."/system/tmp/TELEGRAM_".$this->simpletipp_telegram_url_token.$this->chatMember->id.".spc";
        $stack     = null;
        // Den Stack holen
        if (!$isInitial && file_exists($stackFile)) {
            $stack = unserialize(file_get_contents($stackFile));
        }
        if ($this->isInitial) {
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

    public function isInitial($flag = false) {
        $this->isInitial = ($flag === true);
    }
}
