<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


namespace Simpletipp;


/**
 * Class SimpletippMatchUpdater
 *
 * Provide methods to import matches
 * @copyright  Martin Kozianka 2011-2014
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippMatchUpdater extends \Backend {
    private static $lookupLockSeconds     = 180;
    private static $resultTypeEndergebnis = 2;
    private static $resultTypeHalbzeit    = 1;


    public function updateMatches() {
        $id     = (\Input::get('id') !== null) ? intval(\Input::get('id')) : 0;



        $objSimpletippCollection = ($id === 0) ? \SimpletippModel::findAll() : \SimpletippModel::findBy('id', $id);

        foreach($objSimpletippCollection as $objSimpletipp) {
            $message       = $this->updateSimpletippMatches($objSimpletipp);
            if ('update' === \Input::get('key')) {
                \Message::add($message, TL_INFO);
                $this->redirect(\Environment::get('script').'?do=simpletipp_groups');
            }
            else {
                \System::log(strip_tags($message), 'SimpletippCallbacks updateMatches()', TL_INFO);
            }
        }
    }


    public function updateSimpletippMatches(&$simpletippObj) {
        $leagueInfos = unserialize($simpletippObj->leagueInfos);

        if ($this->lastLookupOnlySecondsAgo($simpletippObj)) {
            $message = sprintf(
                'Letzte Aktualisierung für <strong>%s</strong> erst vor %s Sekunden.',
                $leagueInfos['name'],
                (time() - $simpletippObj->lastLookup));
            return $message;
        }


        $this->oldb = \OpenLigaDB::getInstance();
        $this->oldb->setLeague($leagueInfos);

        $openligaLastChanged    = strtotime($this->oldb->getLastLeagueChange());
        $simpletippLastChanged  = intval($simpletippObj->lastChanged);


        // TODO TEMP TEMP TEMP
        if (true || $simpletippLastChanged != $openligaLastChanged || \MatchModel::countBy('leagueID', $simpletippObj->leagueID) === 0) {
            $matchIDs = $this->updateLeagueMatches($leagueInfos);
            $this->updateTipps($matchIDs);
            $message = sprintf('Liga <strong>%s</strong> aktualisiert! ', $leagueInfos['name']);
        }
        else {
            $message = sprintf('Keine Änderungen seit der letzen Aktualisierung in Liga <strong>%s</strong>. ', $leagueInfos['name']);
        }

        $simpletippObj->lastChanged = $openligaLastChanged;
        $simpletippObj->save();

        return $message;
	}


    public function calculateTipps() {
        $id = intval(\Input::get('id'));
        if ($id == 0) {
            // no id given
            return true;
        }
        $result = $this->Database->prepare(
            "SELECT tl_simpletipp_match.*, tl_simpletipp.leagueInfos
            FROM tl_simpletipp, tl_simpletipp_match
            WHERE tl_simpletipp.id = ? AND tl_simpletipp_match.isFinished = ?
            AND tl_simpletipp_match.leagueID = tl_simpletipp.leagueID"
        )->execute($id, '1');

        $leagueInfos = null;
        $match_ids = array();

        while ($result->next()) {

            if ($leagueInfos == null) {
                $leagueInfos = unserialize($result->leagueInfos);
            }
            $match_ids[] = $result->id;
        }
        $this->updateTipps($match_ids);

        $message = sprintf('Tipps für die Liga <strong>%s</strong> aktualisiert! ', $leagueInfos['name']);
        \Message::add($message, 'TL_INFO');
        $this->redirect(\Environment::get('script').'?do=simpletipp_groups');

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
            $points = \Simpletipp::getPoints($match_results[$result->match_id], $result->tipp);

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
            || ($now - $match->deadline) < (\Simpletipp::$MATCH_LENGTH + 900)) {

            $this->oldb = \OpenLigaDB::getInstance();
            $leagueInfos  = unserialize($simpletipp->leagueInfos);
            $this->oldb->setLeague($leagueInfos);
            $openligaLastChanged   = strtotime($this->oldb->getLastLeagueChange());

            if ($match->goalData->lastUpdate < $openligaLastChanged) {
                // Update goalData
                $match->goalData             = new \stdClass();
                $match->goalData->lastUpdate = $openligaLastChanged;
                $match->goalData->data       = $this->convertGoalData($this->oldb->getMatchGoals($match->id));
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

    private function lastLookupOnlySecondsAgo(&$objSimpletipp) {

        return false;

        $now = time();
        if (($now - $objSimpletipp->lastLookup) < static::$lookupLockSeconds) {
            return true;
        }
        $objSimpletipp->lastLookup = $now;
        $objSimpletipp->save();
        return false;
    }


    private function updateLeagueMatches($leagueInfos) {
        $this->oldb = \OpenLigaDB::getInstance();
        $this->oldb->setLeague($leagueInfos);

        $matches = $this->oldb->getMatches();
        if ($matches === false) {
            return false;
        }

        $matchIDs   = array();
        $newMatches = array();

        foreach($matches as $match) {
            $tmp          = get_object_vars($match);
            $matchIDs[]   = $tmp['matchID'];

            // GroupName
            $arrGroup    = \Simpletipp::groupMapper($tmp);

            $results      = self::parseResults($tmp['matchResults']);
            $newMatch     = array(
                'id'              => $tmp['matchID'],
                'leagueID'        => $tmp['leagueID'],
                'groupID'         => $arrGroup['id'],
                'deadline'        => strtotime($tmp['matchDateTimeUTC']),
                'title'           => sprintf("%s - %s", $tmp['nameTeam1'], $tmp['nameTeam2']),
                'title_short'     => sprintf("%s - %s", \Simpletipp::teamShortener($tmp['nameTeam1']), \Simpletipp::teamShortener($tmp['nameTeam2'])),

                'groupName'       => $arrGroup['name'],
                'groupName_short' => $arrGroup['short'],

                'team_h'          => \Simpletipp::teamShortener($tmp['nameTeam1']),
                'team_a'          => \Simpletipp::teamShortener($tmp['nameTeam2']),
                'team_h_three'    => \Simpletipp::teamShortener($tmp['nameTeam1'], true),
                'team_a_three'    => \Simpletipp::teamShortener($tmp['nameTeam2'], true),
                'icon_h'          => $tmp['iconUrlTeam1'],
                'icon_a'          => $tmp['iconUrlTeam2'],

                'isFinished'      => $tmp['matchIsFinished'],
                'lastUpdate'      => strtotime($tmp['lastUpdate']),
                'resultFirst'     => $results[0],
                'result'          => $results[1],
            );
            $newMatches[] = $newMatch;
        }

        $this->Database->execute("DELETE FROM tl_simpletipp_match WHERE id IN ('"
            .implode("', '", $matchIDs)."')");

        foreach($newMatches as $arrMatch) {
            $objMatch = new \MatchModel();
            $objMatch->setRow($arrMatch);
            $objMatch->save();
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
