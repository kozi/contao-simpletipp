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

use Simpletipp\Models\SimpletippTeamModel;

class TippCommand extends TelegramCommand
{
    private $isInitial = false;

    protected function handle() {
        $this->commander->chatAction(Actions::TYPING);
        $db = $this->module->Database;
        $leagueID = $this->module->getLeagueID();
        $newMatchQuestion = false;

        // Den Stack holen
        $stack = $this->getTippStack($this->isInitial);

        if ($this->isInitial) {
            $this->commander->sendText("Format: `[HEIM]:[AUSWÄRTS]`\nBeispiele: `2:0`, `1:1`, `5:0`\nSchicke einen `-` zum Überspringen oder einen `.` zum Beenden.");
        }

        if ($stack === null || ($stack->lastAccess + 120) < time()) {
            return false;
        }

        // Tippen beenden --> stackFile löschen
        if ("." === $this->text) {
            $this->deleteTippStack();
            $this->commander->sendText("Tippen beendet!");
            return true;
        }

        $excludeIds = array_keys($stack->tipps);

        // Infos zur aktuellesten ID auf dem stack holen und an den Client schicken!
		$sql  = "SELECT id,title,team_h,team_a,deadline FROM tl_simpletipp_match WHERE leagueID = ? AND deadline > ?";
        $sql .= (is_array($excludeIds) && count($excludeIds) > 0) ? " AND id NOT IN (".implode($excludeIds, ",").")" : "";
        $sql .= " ORDER BY deadline ASC";

        $result = $db->prepare($sql)->limit(1)->execute($leagueID, $this->now);
        if ($result->numRows == 1) {
            $match = (Object) $result->row();
            $match->teamHome = SimpletippTeamModel::findByPk($match->team_h);
            $match->teamAway = SimpletippTeamModel::findByPk($match->team_a);

		    $sql = "SELECT * FROM tl_simpletipp_tipp WHERE member_id = ? AND match_id = ?";  
            $result = $db->prepare($sql)->limit(1)->execute($this->chatMember->id, $match->id);
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
        if (!$this->isInitial && is_array($stack->tipps) && count($stack->tipps) > 0 && $this->text !== "-") {
            // - Bedeutet aktuellen Tipp beibehalten bzw. das Spiel nicht tippen
            // TODO $this->text auswerten!
            $this->sendText("`TIPP: ".$this->text."`");
            // TODO Wenn es nicht klappt die letzte id vom stack holen
            // Tippformatbeispiel: *2:1* (*.* zum Beenden. *-* zum Überspringen)
            // Die letzte ID vom stack holen damit das Spiel nochmal getippt werden kann
        }

        if ($newMatchQuestion !== false) {
            $this->sendText($newMatchQuestion);
        }

        // Den stack speichern
        $this->saveTippStack($stack);

        return true;
    }

    public function isInitial($flag = false) {
        $this->isInitial = ($flag === true);
    }
}
