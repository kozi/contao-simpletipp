<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2015 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2015 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


namespace Simpletipp;


use Simpletipp\Simpletipp;
use Simpletipp\Models\SimpletippModel;
use Simpletipp\Models\SimpletippMatchModel;
/**
 * Class SimpletippMatchUpdater
 *
 * Provide methods to import matches
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippMatchUpdater extends \Backend {
    private static $lookupLockSeconds     = 180;
    const RESULTTYPE_ENDERGEBNIS   = 'ENDERGEBNIS';
    const RESULTTYPE_HALBZEIT      = 'HALBZEIT';
    const RESULTTYPE_VERLAENGERUNG = 'VERLAENGERUNG';
    const RESULTTYPE_11METER       = '11METER';

    // TODO Typen auswählbar machen in der Tipprunde
    // TODO Prioritäten festlegen d.h. die resultTypes in eine Reihenfolge bringen
    public static $arrResultTypes = array(
        1 => self::RESULTTYPE_HALBZEIT,
        2 => self::RESULTTYPE_ENDERGEBNIS,
        3 => self::RESULTTYPE_VERLAENGERUNG,
        4 => self::RESULTTYPE_11METER,
    );

    public function updateMatches() {
        $id     = (\Input::get('id') !== null) ? intval(\Input::get('id')) : 0;

        if ($id === 0)
        {
            $objSimpletippCollection =  SimpletippModel::findAll();
            foreach($objSimpletippCollection as $objSimpletipp)
            {
                $message = $this->updateSimpletippMatches($objSimpletipp, false);
                \System::log(strip_tags($message), 'SimpletippCallbacks updateMatches()', TL_INFO);
            }
        }
        else
        {
            $objSimpletipp = SimpletippModel::findByPk($id);
            $message       = $this->updateSimpletippMatches($objSimpletipp, true);
            \Message::add($message, TL_INFO);
            $this->redirect(\Environment::get('script').'?do=simpletipp_group');
        }
    }

    public function updateSimpletippMatches(&$simpletippObj, $forceUpdate = false) {
        $leagueInfos = unserialize($simpletippObj->leagueInfos);

        if ($this->lastLookupOnlySecondsAgo($simpletippObj)) {
            $message = sprintf(
                'Letzte Aktualisierung für <strong>%s</strong> erst vor %s Sekunden.',
                $leagueInfos['name'],
                (time() - $simpletippObj->lastLookup));
            return $message;
        }

        $this->oldb = OpenLigaDB::getInstance();
        $this->oldb->setLeague($leagueInfos);

        $openligaLastChanged    = strtotime($this->oldb->getLastLeagueChange());
        $simpletippLastChanged  = intval($simpletippObj->lastChanged);

        if ($forceUpdate || $simpletippLastChanged != $openligaLastChanged || SimpletippMatchModel::countBy('leagueID', $simpletippObj->leagueID) === 0) {
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
        $this->redirect(\Environment::get('script').'?do=simpletipp_group');

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
            || ($now - $match->deadline) < ($simpletipp->matchLength)) {

            $this->oldb = OpenLigaDB::getInstance();
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
        $this->oldb = OpenLigaDB::getInstance();
        $this->oldb->setLeague($leagueInfos);

        $matches = $this->oldb->getMatches();
        if ($matches === false) {
            return false;
        }

        $newMatches = array();

        foreach($matches as $match) {
            $tmp          = get_object_vars($match);
            $matchId      = $tmp['matchID'];

            // GroupName
            $arrGroup     = Simpletipp::groupMapper($tmp);

            $results      = self::parseResults($tmp['matchResults']);

            $newMatch     = array(
                'leagueID'        => $tmp['leagueID'],
                'groupID'         => $arrGroup['id'],
                'deadline'        => strtotime($tmp['matchDateTimeUTC']),
                'title'           => sprintf("%s - %s", $tmp['nameTeam1'], $tmp['nameTeam2']),
                'title_short'     => sprintf("%s - %s", Simpletipp::teamShortener($tmp['nameTeam1']), Simpletipp::teamShortener($tmp['nameTeam2'])),

                'groupName'       => $arrGroup['name'],
                'groupName_short' => $arrGroup['short'],

                'team_h'          => Simpletipp::teamShortener($tmp['nameTeam1']),
                'team_a'          => Simpletipp::teamShortener($tmp['nameTeam2']),
                'team_h_three'    => Simpletipp::teamShortener($tmp['nameTeam1'], true),
                'team_a_three'    => Simpletipp::teamShortener($tmp['nameTeam2'], true),
                'icon_h'          => (Simpletipp::teamIcon($tmp['nameTeam1'])) ? Simpletipp::teamIcon($tmp['nameTeam1']) : $tmp['iconUrlTeam1'],
                'icon_a'          => (Simpletipp::teamIcon($tmp['nameTeam1'])) ? Simpletipp::teamIcon($tmp['nameTeam2']) : $tmp['iconUrlTeam2'],

                'isFinished'      => $tmp['matchIsFinished'],
                'lastUpdate'      => strtotime($tmp['lastUpdate']),
                'result'          => $results[self::RESULTTYPE_ENDERGEBNIS],
                'resultFirst'     => $results[self::RESULTTYPE_HALBZEIT],
            );
            $newMatches[$matchId] = $newMatch;
        }

        $arrMatchIds = array_keys($newMatches);
        // update existing matches
        foreach(SimpletippMatchModel::findMultipleByIds($arrMatchIds) as $objMatch) {
            $matchId = intval($objMatch->id);

            if (array_key_exists($objMatch->id, $newMatches)) {
                foreach($newMatches[$matchId] as $key => $value) {
                    $objMatch->$key = $value;
                }
                $objMatch->save();
                unset($newMatches[$matchId]);
            }
        }

        // add new matches
        foreach($newMatches as $matchId => $arrMatch) {
            $objMatch       = new SimpletippMatchModel();
            $arrMatch['id'] = $matchId;

            $objMatch->setRow($arrMatch);
            $objMatch->save();
        }

        return $arrMatchIds;
    }

    private static function parseResults($matchResults) {
        // Init result array
        $arrResults = array();
        foreach (static::$arrResultTypes as $type) {
            $arrResults[$type] = '';
        }

        // Fill result array
        if ($matchResults->matchResult !== null) {
            foreach ($matchResults->matchResult as $res) {
                $key = static::$arrResultTypes[$res->resultTypeId];
                $arrResults[$key] = $res->pointsTeam1.Simpletipp::$TIPP_DIVIDER.$res->pointsTeam2;
            }
        }

        return $arrResults;
    }


}
