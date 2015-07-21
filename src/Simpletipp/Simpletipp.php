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

use \Simpletipp\Models\SimpletippMatchModel;

/**
 * Class SimpletippCalendar
 *
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class Simpletipp extends \System {
    public static $TIPP_DIVIDER       = ':';
    public static $SIMPLETIPP_USER_ID = 'SIMPLETIPP_USER_ID';

    const GROUPNAME_VORRUNDE          = 'Vorrunde';

    public static function getPoints($result, $tipp, $simpletippFactor = null) {

        $perfect    = 0;
        $difference = 0;
        $tendency   = 0;

        if (strlen($result) === 0 || strlen($tipp) === 0) {
            return new SimpletippPoints($simpletippFactor, 0, 0, 0);
        }
        $tmp = explode(self::$TIPP_DIVIDER, $result);
        $rh = intval($tmp[0], 10); $ra = intval($tmp[1], 10);

        $tmp = explode(self::$TIPP_DIVIDER, $tipp);
        $th = intval($tmp[0], 10); $ta = intval($tmp[1], 10);

        if ($rh === $th && $ra === $ta) {
            $perfect = 1;
        }
        elseif (($rh-$ra) === ($th-$ta)) {
            $difference = 1;
        }
        elseif (($rh < $ra && $th < $ta) || ($rh > $ra && $th > $ta)) {
            $tendency = 1;
        }

        return new SimpletippPoints($simpletippFactor, $perfect, $difference, $tendency);
    }

    public static function groupMapper($arrMatch)
    {
        $leagueID = $arrMatch['leagueID'];
        $oneMio   = 1000000;
        $arrGroup = [
            'id'    => $arrMatch['groupID'],
            'name'  => $arrMatch['groupName'],
            'short' => $arrMatch['groupName']
        ];

        if (is_array($GLOBALS['simpletipp']['groupNames']) && array_key_exists($leagueID, $GLOBALS['simpletipp']['groupNames']))
        {
            $groupNames = $GLOBALS['simpletipp']['groupNames'][$leagueID];

            if ($arrGroup['name'] ===  static::GROUPNAME_VORRUNDE)
            {
                $i   = 1;

                foreach($groupNames as $strGroupName => $arrTeams) {
                    if (in_array($arrMatch['nameTeam1'], $arrTeams) || in_array($arrMatch['nameTeam2'], $arrTeams))
                    {
                        $arrGroup['name'] = $strGroupName;
                        $arrGroup['id']   = $oneMio + $i;
                    }
                    $i++;
                }
            }
        }

        $arrGroup['short'] = $strName = trim(str_replace(
            ['Gruppe', '. Spieltag'],
            ['', ''],
            $arrGroup['name']));

        return $arrGroup;
    }

    public static function getGroupMember($groupID, $order = 'tl_member.lastname ASC, tl_member.firstname ASC')
    {
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';
        $objMembers     = \MemberModel::findBy(
                                ['tl_member.groups LIKE ?'],
                                $participantStr,
                                ['order' => $order]
                          );
        return $objMembers;
    }

    public static function getGroupMemberIds($groupID)
    {
        $arrIds     = [];
        $objMembers = static::getGroupMember($groupID);
        if ($objMembers!== null)
        {
            foreach($objMembers as $objMember)
            {
                $arrIds[] = $objMember->id;
            }
        }
        return $arrIds;
    }

    public static function getNextMatch($leagueID)
    {
        $objMatch = SimpletippMatchModel::findOneBy(
            ['leagueID = ?', 'deadline > ?'],
            [$leagueID, time()],
            ['order' => 'deadline ASC, id ASC']
        );
        return $objMatch;
    }

    public static function getNotTippedUser($groupID, $match_id)
    {
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';

        $result = \Database::getInstance()->prepare("SELECT tblu.*
             FROM tl_member as tblu
             LEFT JOIN tl_simpletipp_tipp AS tblt
             ON ( tblu.id = tblt.member_id AND tblt.match_id = ?)
             WHERE tblt.id IS NULL
             AND CONVERT(tblu.groups USING utf8) LIKE ?
             ORDER BY tblu.lastname")->execute($match_id, $participantStr);

        $arrUser = [];
        while ($result->next())
        {
            $arrUser[] = $result->row();
        }
        return $arrUser;
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

    public static function getSimpletippMessages()
    {
        if (!is_array($_SESSION['TL_SIMPLETIPP_MESSAGE']))
        {
            $_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
        }

        if (count($_SESSION['TL_SIMPLETIPP_MESSAGE']) == 0)
        {
            return '';
        }

        $messages = '';
        foreach($_SESSION['TL_SIMPLETIPP_MESSAGE'] AS $message)
        {
            $messages .= sprintf("	<div class=\"message\">%s</div>\n", $message);
        }
        // Reset
        $_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
        return sprintf("\n<div class=\"simpletipp_messages\">\n%s</div>\n", $message);
    }

    public static function addSimpletippMessage($message)
    {
        if (!is_array($_SESSION['TL_SIMPLETIPP_MESSAGE']))
        {
            $_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
        }
        $_SESSION['TL_SIMPLETIPP_MESSAGE'][] = $message;
    }

    public static function cleanupTipp($tipp)
    {
        $t = preg_replace ('/[^0-9]/',' ', $tipp);
        $t = preg_replace ('/\s+/',self::$TIPP_DIVIDER, $t);

        if (strlen($t) < 3)
        {
            return '';
        }

        $tmp = explode(self::$TIPP_DIVIDER, $t);

        if(strlen($tmp[0]) < 1 && strlen($tmp[1]) < 1)
        {
            return '';
        }

        $h = intval($tmp[0], 10);
        $a = intval($tmp[1], 10);
        return $h.self::$TIPP_DIVIDER.$a;
    }


    public static function cleanItem(&$item)
    {
        if (is_object($item))
        {
            unset($item->password);
            unset($item->session);
            unset($item->autologin);
            unset($item->activation);
            foreach($item as $property => $value)
            {
                if (is_string($value) && strlen($value) == 0)
                {
                    unset($item->$property);
                }
            }
        }
        if (is_array($item))
        {
            foreach($item as $key => $value)
            {
                if (is_string($value) && strlen($value) == 0)
                {
                    unset($item[$key]);
                }
            }
        }
    }

} // END class Simpletipp
