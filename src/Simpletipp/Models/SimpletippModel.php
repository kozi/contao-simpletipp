<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2018 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2018 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Models;


class SimpletippModel extends \Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_simpletipp';

    public function getPointFactors()
    {
        $factor = explode(',', $this->factor);
        $pointFactors = new \stdClass;
        $pointFactors->perfect    = intval($factor[0]);
        $pointFactors->difference = intval($factor[1]);
        $pointFactors->tendency   = intval($factor[2]);

        return $pointFactors;
    }

    /**
     * @param $groupID
     * @param string $order
     * @return \MemberModel|\Model\Collection
     */
    public function getGroupMember($order = 'tl_member.lastname ASC, tl_member.firstname ASC')
    {
        $groupId = $this->participant_group;        
        $participantStr = '%s:'.strlen($groupId).':"'.$groupId.'"%';
        $objMembers     = \MemberModel::findBy(
                                ['tl_member.groups LIKE ?'],
                                $participantStr,
                                ['order' => $order]
                          );
        return $objMembers;
    }

    public function getGroupMemberIds()
    {
        $arrIds     = [];
        $objMembers = $this->getGroupMember();
        if ($objMembers!== null) {
            foreach ($objMembers as $objMember) {
                $arrIds[] = $objMember->id;
            }
        }
        return $arrIds;
    }

    public static function shortenLeagueName($name) {
        $name = preg_replace('/(\d\d)(\d\d)\/(\d\d)(\d\d)/i', '$2/$4', $name);

        $replaceFrom = [];
        $replaceTo   = [];

        $replaceFrom[] = 'Bundesliga';     $replaceTo[] = 'Buli';

        return trim(str_replace($replaceFrom, $replaceTo, $name));
    }

    public static function getLeagueGroups($leagueID)
    {
        $groups = [];
        $result = \Database::getInstance()->prepare("SELECT DISTINCT groupID, groupName, groupOrderID 
          FROM tl_simpletipp_match WHERE leagueID = ? ORDER BY groupOrderID")->execute($leagueID);

        while($result->next())
        {
            $short = intval($result->groupName);
            if ($short == 0)
            {
                $mg    = explode(". ", $result->groupName);
                $short = $mg[0];
            }

            $groups[$result->groupID] = (Object) [
                'title' => $result->groupName,
                'short' => $short
            ];
        }
        return $groups;
    }
    

}
