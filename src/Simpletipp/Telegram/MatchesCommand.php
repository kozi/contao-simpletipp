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
use Simpletipp\Models\SimpletippPoints;

class MatchesCommand extends TelegramCommand
{
    protected function handle() {
        $leagueID = $this->module->getLeagueID();
        $db = $this->module->Database;
        
        // TODO Titel anzeigen!
        // TODO Spiele von anderen Benutzern anzeigen
        $ball     = "\xE2\x9A\xBD";
        $groupIds = [];
        $result   = $db->prepare("SELECT groupID FROM tl_simpletipp_match
             WHERE leagueID = ? AND deadline < ? ORDER BY deadline DESC")
             ->limit(1)->execute($simpletipp->leagueID, $this->now);
        if ($result->numRows == 1) {
            $groupIds[] = $result->groupID;
        }

        $result = $db->prepare("SELECT groupID FROM tl_simpletipp_match
             WHERE leagueID = ? AND deadline > ? ORDER BY deadline ASC")
             ->limit(1)->execute($simpletipp->leagueID, $this->now);
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
        $result  = $db->prepare($sql)->execute($this->chatMember->id, $leagueID, $this->chatMember->id);

        while ($result->next()) {
            $match = (Object) $result->row();
            $match->teamHome = SimpletippTeamModel::findByPk($match->team_h);
            $match->teamAway = SimpletippTeamModel::findByPk($match->team_a);
            $pointObj      = new SimpletippPoints($this->module->getPointFactors(), $match->perfect, $match->difference, $match->tendency);
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

        $return = $this->sendText($content);

    }
    
}
