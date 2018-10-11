<?php

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
        $pointFactors->perfect = intval($factor[0]);
        $pointFactors->difference = intval($factor[1]);
        $pointFactors->tendency = intval($factor[2]);

        return $pointFactors;
    }

    public function getGroupMember($chatMember = false)
    {
        $groupId = $this->participant_group;

        $where = ['tl_member.groups LIKE ?'];
        $params = ['%s:' . strlen($groupId) . ':"' . $groupId . '"%'];

        if ($chatMember === true) {
            $where[] = 'tl_member.telegram_chat_id <> ?';
            $params[] = '';
        }
        $objMembers = \MemberModel::findBy($where, $params, ['order' => 'tl_member.lastname ASC, tl_member.firstname ASC']);
        return $objMembers;
    }

    public function getGroupMemberIds()
    {
        $arrIds = [];
        $objMembers = $this->getGroupMember();
        if ($objMembers !== null) {
            foreach ($objMembers as $objMember) {
                $arrIds[] = $objMember->id;
            }
        }
        return $arrIds;
    }

    public static function shortenLeagueName($name)
    {
        $name = preg_replace('/(\d\d)(\d\d)\/(\d\d)(\d\d)/i', '$2/$4', $name);

        $replaceFrom = [];
        $replaceTo = [];

        $replaceFrom[] = 'Bundesliga';
        $replaceTo[] = 'Buli';

        return trim(str_replace($replaceFrom, $replaceTo, $name));
    }

    public static function getLeagueGroups($leagueID)
    {
        $groups = [];
        $result = \Database::getInstance()->prepare("SELECT DISTINCT groupID, groupName, groupOrderID
          FROM tl_simpletipp_match WHERE leagueID = ? ORDER BY groupOrderID")->execute($leagueID);

        while ($result->next()) {
            $short = intval($result->groupName);
            if ($short == 0) {
                $mg = explode(". ", $result->groupName);
                $short = $mg[0];
            }

            $groups[$result->groupID] = (Object) [
                'title' => $result->groupName,
                'short' => $short,
            ];
        }
        return $groups;
    }

}
