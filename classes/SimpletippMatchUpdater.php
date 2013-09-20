<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2013 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012-2013 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


/**
 * Class SimpletippMatchUpdater
 *
 * Provide methods to import matches
 * @copyright  Martin Kozianka 2012-2013 
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippMatchUpdater extends Backend {
    private static $lookupLockSeconds     = 180;
    private static $resultTypeEndergebnis = 2;
    private static $resultTypeHalbzeit    = 1;


    public function updateMatches() {

        $id     = (Input::get('id') !== null) ? intval(Input::get('id')) : 0;
        $result = $this->Database
            ->prepare('SELECT * FROM tl_simpletipp'
            .(($id != 0) ? ' WHERE id = ?' : ''))
            ->execute($id);

        while($result->next()) {
            $simpletippObj = (Object) $result->row();
            $message       = $this->updateSimpletippMatches($simpletippObj);
            if ($id != 0) {
                Message::add($message, TL_INFO);
                $this->redirect(Environment::get('script').'?do=simpletipp_groups');
            }
            else {
                System::log(strip_tags($message), 'SimpletippCallbacks updateMatches()', TL_INFO);
            }
        }
    }


    public function updateSimpletippMatches($simpletippObj) {
        $leagueObj = unserialize($simpletippObj->leagueObject);

        if ($this->lastLookupOnlySecondsAgo($simpletippObj)) {
            $message = sprintf(
                'Letzte Aktualisierung für <strong>%s</strong> erst vor %s Sekunden.',
                $leagueObj->leagueName,
                (time() - $simpletippObj->lastLookup));
            return $message;
        }

        $this->import('OpenLigaDB');
        $this->OpenLigaDB->setLeague($leagueObj);

        $openligaLastChanged   = strtotime($this->OpenLigaDB->getLastLeagueChange());
        $simpletippLastChanged = intval($simpletippObj->lastChanged);

        if ($simpletippLastChanged != $openligaLastChanged) {
            $matchIDs = $this->updateLeagueMatches($leagueObj);
            $this->updateTipps($matchIDs);
            $message = sprintf('Liga <strong>%s</strong> aktualisiert! ', $leagueObj->leagueName);
        }
        else {
            $message = sprintf('Keine Änderungen seit der letzen Aktualisierung in Liga <strong>%s</strong>. ', $leagueObj->leagueName);
        }

        $this->Database->prepare("UPDATE tl_simpletipp SET lastChanged = ? WHERE id = ?")
            ->execute($openligaLastChanged, $simpletippObj->id);

        return $message;
	}


    public function calculateTipps() {
        $id = intval(Input::get('id'));
        if ($id == 0) {
            // no id given
            return true;
        }
        $result = $this->Database->prepare(
            "SELECT tl_simpletipp_match.*, tl_simpletipp.leagueObject
            FROM tl_simpletipp, tl_simpletipp_match
            WHERE tl_simpletipp.id = ? AND tl_simpletipp_match.isFinished = ?
            AND tl_simpletipp_match.leagueID = tl_simpletipp.leagueID"
        )->execute($id, '1');

        $leagueObj = null;
        $match_ids = array();

        while ($result->next()) {

            if ($leagueObj == null) {
                $leagueObj = unserialize($result->leagueObject);
            }
            $match_ids[] = $result->id;
        }
        $this->updateTipps($match_ids);

        $message = sprintf('Tipps für die Liga <strong>%s</strong> aktualisiert! ', $leagueObj->leagueName);
        Message::add($message, 'TL_INFO');
        $this->redirect(Environment::get('script').'?do=simpletipp_groups');

    }


	private function updateTipps($ids) {
		if (count($ids) === 0) {
			//  Nothing to do
			return true;
		}
	
		$result = $this->Database->execute(
				"SELECT id, result FROM tl_simpletipp_match"
				." WHERE id in (".implode(',', $ids).")");
		while($result->next()) {
			$match_results[$result->id] = $result->result;
		}

		$result = $this->Database->execute(
				"SELECT id, match_id, tipp FROM tl_simpletipp_tipp"
				." WHERE match_id in (".implode(',', $ids).")");
		while($result->next()) {
            $points = Simpletipp::getPoints($match_results[$result->match_id], $result->tipp);

			$this->Database->prepare("UPDATE tl_simpletipp_tipp"
                ." SET perfect = ?, difference = ?, tendency = ?, wrong = ? WHERE id = ?")
                ->execute($points->perfect, $points->difference, $points->tendency, $points->wrong, $result->id);
		}
	}	


    public function refreshGoalData($simpletipp, $match) {
        $now = time();

        if ($now < $this->match->deadline) {
            return $match;
        }

        $simpletippLastChanged = intval($simpletipp->lastChanged);

        if ($match->goalData == NULL
            || $match->goalData->lastUpdate < $simpletippLastChanged
            || ($now - $match->deadline) < (Simpletipp::$MATCH_LENGTH + 900)) {

            $this->import('OpenLigaDB');
            $leagueObj  = unserialize($simpletipp->leagueObject);
            $this->OpenLigaDB->setLeague($leagueObj);
            $openligaLastChanged   = strtotime($this->OpenLigaDB->getLastLeagueChange());

            if ($match->goalData->lastUpdate < $openligaLastChanged) {
                // Update goalData
                $match->goalData             = new stdClass();
                $match->goalData->lastUpdate = $openligaLastChanged;
                $match->goalData->data       = $this->convertGoalData($this->OpenLigaDB->getMatchGoals($match->id));
            }

            $this->Database->prepare("UPDATE tl_simpletipp_match SET goalData = ? WHERE id = ? ")
                ->execute(serialize($match->goalData), $match->id);
        }
        return $match;
    }

    private function convertGoalData($data) {
        $goalData    = array();

        if (is_object($data)) {
            $goalObjects = array($data);
        }
        elseif (is_array($data)) {
            $goalObjects = $data;
        }
        else {
            $goalObjects = array();
        }

        $previousHome = 0;
        foreach($goalObjects as $goalObj) {
            $goalData[] = (Object) array(
                'name'     => $goalObj->goalGetterName,
                'minute'   => $goalObj->goalMatchMinute,
                'result'   => $goalObj->goalScoreTeam1.':'.$goalObj->goalScoreTeam2,
                'penalty'  => $goalObj->goalPenalty,
                'ownGoal'  => $goalObj->goalOwnGoal,
                'overtime' => $goalObj->goalOvertime,
                'home'     => ($previousHome !== $goalObj->goalScoreTeam1),
            );
            $previousHome = $goalObj->goalScoreTeam1;
        }
        return $goalData;
    }

    private function lastLookupOnlySecondsAgo($simpletipp) {
        $now = time();
        if (($now - $simpletipp->lastLookup) < static::$lookupLockSeconds) {
            return true;
        }
        $this->Database->prepare("UPDATE tl_simpletipp SET lastLookup = ? WHERE id = ?")
            ->execute($now, $simpletipp->id);
        return false;
    }


    private function updateLeagueMatches($leagueObject) {
        $this->import('OpenLigaDB');
        $this->OpenLigaDB->setLeague($leagueObject);

        $matches = $this->OpenLigaDB->getMatches();
        if ($matches === false) {
            return false;
        }

        $matchIDs   = array();
        $newMatches = array();

        foreach($matches as $match) {
            $tmp          = get_object_vars($match);
            $matchIDs[]   = $tmp['matchID'];

            $results      = self::parseResults($tmp['matchResults']);
            $newMatches[] = array(
                'id'              => $tmp['matchID'],
                'leagueID'        => $tmp['leagueID'],
                'groupID'         => $tmp['groupID'],
                'groupName'       => $tmp['groupName'],
                'groupName_short' => trim(str_replace('. Spieltag', '', $tmp['groupName'])),
                'deadline'        => strtotime($tmp['matchDateTimeUTC']),
                'title'           => sprintf("%s - %s", $tmp['nameTeam1'], $tmp['nameTeam2']),
                'title_short'     => sprintf("%s - %s", Simpletipp::teamShortener($tmp['nameTeam1']), Simpletipp::teamShortener($tmp['nameTeam2'])),

                'team_h'          => Simpletipp::teamShortener($tmp['nameTeam1']),
                'team_a'          => Simpletipp::teamShortener($tmp['nameTeam2']),
                'team_h_three'    => Simpletipp::teamShortener($tmp['nameTeam1'], true),
                'team_a_three'    => Simpletipp::teamShortener($tmp['nameTeam2'], true),
                'icon_h'          => Simpletipp::iconUrl($tmp['nameTeam1'], '/files/vereinslogos/'),
                'icon_a'          => Simpletipp::iconUrl($tmp['nameTeam2'], '/files/vereinslogos/'),

                'isFinished'      => $tmp['matchIsFinished'],
                'lastUpdate'      => strtotime($tmp['lastUpdate']),
                'resultFirst'     => $results[0],
                'result'          => $results[1],
            );
        }

        $this->Database->execute("DELETE FROM tl_simpletipp_match WHERE id IN ('"
        .implode("', '", $matchIDs)."')");

        foreach($newMatches as $m) {
            $this->Database->prepare("INSERT INTO tl_simpletipp_match %s")->set($m)->execute();
        }
        return $matchIDs;
    }

    private static function parseResults($matchResults) {
        $rFirst = '';
        $rFinal = '';

        if ($matchResults->matchResult === null){
            return array($rFirst, $rFinal);
        }

        foreach ($matchResults->matchResult as $res) {
            if ($res->resultTypeId === self::$resultTypeHalbzeit) {
                $rFirst = $res->pointsTeam1.Simpletipp::$TIPP_DIVIDER.$res->pointsTeam2;
            }
            if ($res->resultTypeId === self::$resultTypeEndergebnis) {
                $rFinal = $res->pointsTeam1.Simpletipp::$TIPP_DIVIDER.$res->pointsTeam2;
            }
        }
        return array($rFirst, $rFinal);
    }


}
