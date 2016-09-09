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
        $groupId = $this->simpletipp->participant_group;        
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

    public static function getLeagueGroups($leagueID)
    {
        $groups = [];
        $result = \Database::getInstance()->prepare("SELECT DISTINCT groupID, groupName
          FROM tl_simpletipp_match WHERE leagueID = ? ORDER BY groupID")->execute($leagueID);

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
