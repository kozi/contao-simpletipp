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
use \Simpletipp\Models\SimpletippTippModel;

class TippCommand extends TelegramCommand
{
    private $isInitial = false;

    protected function handle() {
        // Prevent tipp stack reset
        $this->preserveTippStack();

        $db               = $this->module->Database;
        $leagueID         = $this->module->getLeagueID();
        $newMatchQuestion = null;
        $currentMatch     = null;

        // Den Stack holen
        $stack = $this->getTippStack($this->isInitial);

        if ($this->isInitial) {
            $this->sendText("Format: `[HEIM]:[AUSWÄRTS]`\nBeispiele: `2:0`, `1:1`, `5:0`\nSchicke einen `-` zum Überspringen oder einen `.` zum Beenden.");
        }

        if ($stack === null) {
            $this->sendText("Brauchen Sie Hilfe? /hilfe");
            return false;
        }
        if (($stack->lastAccess + 120) < time()) {
            $this->sendText("Timeout! (Starte den Tippvorgang mit T erneut.)");
            return fals;
        }

        // Tippen beenden --> stackFile löschen
        if ("." === $this->text) {
            $this->deleteTippStack();
            $this->sendText("Tippen beendet!");
            return true;
        }

        if (is_array($stack->tipps) && count($stack->tipps) > 0) {
            $currentMatch = end($stack->tipps);
            reset($stack->tipps); 
        }             

        // Infos zur aktuellesten ID aus dem stack holen und an den Client schicken!
        $excludeIds = array_keys($stack->tipps);

		$sql  = "SELECT id,title,team_h,team_a,deadline FROM tl_simpletipp_match WHERE leagueID = ? AND deadline > ?";
        $sql .= (is_array($excludeIds) && count($excludeIds) > 0) ? " AND id NOT IN (".implode($excludeIds, ",").")" : "";
        $sql .= " ORDER BY deadline ASC";
        
        $result = $db->prepare($sql)->limit(1)->execute($leagueID, $this->now);
        if ($result->numRows == 1) {
            $newMatch = (Object) $result->row();
            $newMatch->teamHome = (Object) SimpletippTeamModel::findByPk($newMatch->team_h)->row();
            $newMatch->teamAway = (Object) SimpletippTeamModel::findByPk($newMatch->team_a)->row();

		    $sql = "SELECT * FROM tl_simpletipp_tipp WHERE member_id = ? AND match_id = ?";  
            $result = $db->prepare($sql)->limit(1)->execute($this->chatMember->id, $newMatch->id);
            if ($result->numRows == 1) {
                $newMatch->tipp = $result->tipp;
            }
            
            $stack->tipps[$newMatch->id] = $newMatch;
            $newMatchQuestion = $this->getMatchText($newMatch);
        }

        // Tipp eintragen (- Bedeutet aktuellen Tipp beibehalten bzw. das Spiel nicht tippen)
        if (!$this->isInitial && $currentMatch != null && $this->text !== "-") {

            $tipp  = SimpletippTippModel::cleanupTipp($this->text);
            if (preg_match('/^(\d{1,4}):(\d{1,4})$/', $tipp) && $currentMatch->deadline > $this->now)
            {
                // Der Tipp ist in Ordnung und kan eingetragen werden
                $m = SimpletippTippModel::addTipp($this->chatMember->id, $currentMatch->id, $tipp);
                $r = $m->row();
                $this->sendText(print_r($r, true));
            }
            else {
                // Die letzte ID vom stack holen                
                unset($stack->tipps[$newMatch->id]);
                // Tippabfrage erneut senden                
                $newMatchQuestion = $this->getMatchText($currentMatch);
            }
        }

        if ($newMatchQuestion !== null) {
            $this->sendText($newMatchQuestion);
        }

        // Den stack speichern
        $this->saveTippStack($stack);
        return true;
    }

    public function getMatchText($match) {
        return sprintf("`%s`\n*%s*\n%s",
                date("d.m. H:i", $match->deadline),
                $match->teamHome->short." - ".$match->teamAway->short,
                ($match->tipp) ? "Bisheriger Tipp: `".$match->tipp."`" : "" 
        ); 
    }

    public function isInitial($flag = false) {
        $this->isInitial = ($flag === true);
    }
}
