<?php

namespace Simpletipp\Models;

class SimpletippMatchModel extends \Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_simpletipp_match';

    public static function findLastMatchgroup($simpletipp)
    {
        $arrWhere = ['leagueID = ?', 'deadline < ?'];
        $arrValues = [$simpletipp->leagueID, time()];
        $objGroup = self::findBy($arrWhere, $arrValues, ['order' => 'deadline DESC', 'limit' => '1']);

        if ($objGroup !== null) {
            $arrWhere = ['leagueID = ?', 'groupID = ?'];
            $arrValues = [$simpletipp->leagueID, $objGroup->groupID];
            return self::findBy($arrWhere, $arrValues, ['order' => 'deadline ASC']);
        }
        return null;
    }

    public static function findCurrent($simpletipp)
    {
        $arrWhere = ['leagueID = ?', 'deadline > ?'];
        $arrValues = [$simpletipp->leagueID, time()];
        $objGroup = self::findBy($arrWhere, $arrValues, ['order' => 'deadline ASC', 'limit' => '1']);

        if ($objGroup !== null) {
            $arrWhere = ['leagueID = ?', 'groupID = ?'];
            $arrValues = [$simpletipp->leagueID, $objGroup->groupID];
            return self::findBy($arrWhere, $arrValues, ['order' => 'deadline ASC']);
        }
        return null;
    }

    public static function findByTeamAttributeAliases($simpletipp, $strAliases, $teamAttributeName)
    {
        $arrAlias = explode('-', $strAliases);
        if (sizeof($arrAlias) !== 2) {
            return null;
        }
        $teamHome = SimpletippTeamModel::findBy($teamAttributeName, $arrAlias[0]);
        $teamAway = SimpletippTeamModel::findBy($teamAttributeName, $arrAlias[1]);

        $arrWhere = [
            'leagueID' => 'leagueID = ?',
            'team_h' => 'team_h = ?',
            'team_a' => 'team_a = ?',
        ];
        $arrValues = [$simpletipp->leagueID, $teamHome->id, $teamAway->id];

        return self::findOneBy($arrWhere, $arrValues);
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
