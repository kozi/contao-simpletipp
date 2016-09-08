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

namespace Simpletipp\Models;

use Simpletipp\OpenLigaDB;

class SimpletippMatchModel extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_simpletipp_match';

    public static function findLastMatchgroup($simpletipp)
    {
        $arrWhere  = ['leagueID = ?','deadline < ?'];
        $arrValues = [$simpletipp->leagueID, time()];
        $objGroup  = self::findBy($arrWhere, $arrValues, ['order'=>'deadline DESC', 'limit'=>'1']);

        if ($objGroup !== null)
        {
            $arrWhere  = ['leagueID = ?','groupID = ?'];
            $arrValues = [$simpletipp->leagueID, $objGroup->groupID];
            return self::findBy($arrWhere, $arrValues, ['order'=>'deadline ASC']);
        }
        return null;
    }

    public static function findCurrent($simpletipp)
    {
        $arrWhere  = ['leagueID = ?','deadline > ?'];
        $arrValues = [$simpletipp->leagueID, time()];
        $objGroup  = self::findBy($arrWhere, $arrValues, ['order'=>'deadline ASC', 'limit'=>'1']);

        if ($objGroup !== null)
        {
            $arrWhere  = ['leagueID = ?','groupID = ?'];
            $arrValues = [$simpletipp->leagueID, $objGroup->groupID];
            return self::findBy($arrWhere, $arrValues, ['order'=>'deadline ASC']);
        }
        return null;
    }

    public static function findByShortNames($simpletipp, $strAliases)
    {
        $arrAlias = explode('-', $strAliases);
        if (sizeof($arrAlias) !== 2)
        {
            return null;
        }
        $teamHome = SimpletippTeamModel::findBy('alias', $arrAlias[0]);
        $teamAway = SimpletippTeamModel::findBy('alias', $arrAlias[1]);

        $arrWhere  = [
            'leagueID' => 'leagueID = ?',
            'team_h'   => 'team_h = ?',
            'team_a'   => 'team_a = ?',
        ];
        $arrValues = [$simpletipp->leagueID, $teamHome->id, $teamAway->id];

        return self::findOneBy($arrWhere, $arrValues);
    }

    public function refreshGoalData($simpletipp)
    {
        $now = time();
        if ($now < $this->deadline)
        {
            return false;
        }

        $simpletippLastChanged = intval($simpletipp->lastChanged);

        if ($this->goalData == NULL
            || $this->goalData->lastUpdate < $simpletippLastChanged
            || ($now - $this->deadline) < ($simpletipp->matchLength))
        {

            $oldb         = OpenLigaDB::getInstance();
            $leagueInfos  = unserialize($simpletipp->leagueInfos);
            $oldb->setLeague($leagueInfos);
            $openligaLastChanged = strtotime($oldb->getLastLeagueChange());

            if ($this->goalData->lastUpdate < $openligaLastChanged)
            {
                // Update goalData
                $this->goalData = serialize((object) [
                    'lastUpdate' => $openligaLastChanged,
                    'data'       => $this->convertGoalData($oldb->getMatchGoals($this->id))
                ]);
                
                $this->save();
                return true;
            }
        }
        return false;
    }

    private function convertGoalData($data)
    {
        $goalData    = [];

        if (is_object($data))
        {
            $goalObjects = [$data];
        }
        elseif (is_array($data)) {
            $goalObjects = $data;
        }
        else {
            $goalObjects = [];
        }

        $previousHome = 0;
        foreach($goalObjects as $goalObj)
        {
            $goalData[] = (Object) [
                'name'     => $goalObj->goalGetterName,
                'minute'   => $goalObj->goalMatchMinute,
                'result'   => $goalObj->goalScoreTeam1.':'.$goalObj->goalScoreTeam2,
                'penalty'  => $goalObj->goalPenalty,
                'ownGoal'  => $goalObj->goalOwnGoal,
                'overtime' => $goalObj->goalOvertime,
                'home'     => ($previousHome !== $goalObj->goalScoreTeam1),
            ];
            $previousHome = $goalObj->goalScoreTeam1;
        }
        return $goalData;
    }


    public static function getNextMatch($leagueID)
    {
	    return self::findOneBy(
            ['leagueID = ?', 'deadline > ?'],
            [$leagueID, time()],
            ['order' => 'deadline ASC, id ASC']
        );
    }

}
