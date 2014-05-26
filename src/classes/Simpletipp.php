<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


/**
 * Class SimpletippCalendar
 *
 * @copyright  Martin Kozianka 2012-2014
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class Simpletipp extends System {
    public static $TIPP_DIVIDER       = ':';
    public static $SIMPLETIPP_USER_ID = 'SIMPLETIPP_USER_ID';
    public static $MATCH_LENGTH       = 6900;

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


    public static function teamShortener($teamName, $isThree = false) {
        if (array_key_exists($teamName, $GLOBALS['simpletipp']['teamShortener'])) {
            $index = ($isThree) ? 1 : 0;
            return $GLOBALS['simpletipp']['teamShortener'][$teamName][$index];
        }
        else {
            return $teamName;
        }
    }

    public static function groupMapper($arrMatch) {
        $leagueID = $arrMatch['leagueID'];
        $oneMio   = 1000000;
        $arrGroup = array(
            'id'    => $arrMatch['groupID'],
            'name'  => $arrMatch['groupName'],
            'short' => $arrMatch['groupName']
        );

        if (array_key_exists($leagueID, $GLOBALS['simpletipp']['groupNames'])) {
            $groupNames = $GLOBALS['simpletipp']['groupNames'][$leagueID];

            if ($arrGroup['name'] ===  static::GROUPNAME_VORRUNDE) {
                $i   = 1;

                foreach($groupNames as $strGroupName => $arrTeams) {
                    if (in_array($arrMatch['nameTeam1'], $arrTeams) || in_array($arrMatch['nameTeam2'], $arrTeams)){
                        $arrGroup['name'] = $strGroupName;
                        $arrGroup['id']   = $oneMio + $i;
                    }

                    $i++;
                }
            }
        }

        $arrGroup['short'] = $strName = trim(str_replace(
            array('Gruppe', '. Spieltag'),
            array('', ''),
            $arrGroup['name']));

        return $arrGroup;
    }



    public static function iconUrl($teamName, $prefix = '', $suffix = '.png') {
        $team = self::teamShortener($teamName);
        return $prefix.standardize($team).$suffix;
    }

    public static function getGroupMember($groupID, $order = 'tl_member.lastname ASC, tl_member.firstname ASC') {
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';
        $objMembers     = \MemberModel::findBy(
                                array('tl_member.groups LIKE ?'),
                                $participantStr,
                                array(
                                        'order' => $order
                                )
                          );
        return $objMembers;
    }

    public static function getGroupMemberIds($groupID) {
        $arrIds     = array();
        $objMembers = static::getGroupMember($groupID);
        foreach($objMembers as $objMember) {
            $arrIds[] = $objMember->id;
        }
        return $arrIds;
    }


    public static function getNextMatch($leagueID) {
        $objMatch = \MatchModel::findOneBy(
            array('leagueID = ?', 'deadline > ?'),
            array($leagueID, time()),
            array(
                'order' => 'deadline ASC, id ASC'
            )
        );
        return $objMatch;
    }


    public static function getNotTippedUser($groupID, $match_id) {
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';

        $result = \Database::getInstance()->prepare("SELECT tblu.*
             FROM tl_member as tblu
             LEFT JOIN tl_simpletipp_tipp AS tblt
             ON ( tblu.id = tblt.member_id AND tblt.match_id = ? AND tblu.groups LIKE ?)
             WHERE tblt.id IS NULL
             ORDER BY tblu.lastname")->execute($match_id, $participantStr);

        $arrUser = array();
        while ($result->next()) {
            $arrUser[] = $result->row();
        }
        return $arrUser;
    }


    public static function getLeagueGroups($leagueID) {
        $groups = array();
        $result = \Database::getInstance()->prepare("SELECT DISTINCT groupID, groupName
          FROM tl_simpletipp_match WHERE leagueID = ? ORDER BY groupID")->execute($leagueID);

        while($result->next()) {

            $short = intval($result->groupName);
            if ($short == 0) {
                $mg    = explode(". ", $result->groupName);
                $short = $mg[0];
            }

            $groups[$result->groupID] = (Object) array(
                'title' => $result->groupName,
                'short' => $short);
        }
        return $groups;
    }

    public static function getSimpletippMessages() {
        if (!is_array($_SESSION['TL_SIMPLETIPP_MESSAGE'])) {
            $_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
        }

        if (count($_SESSION['TL_SIMPLETIPP_MESSAGE']) == 0) {
            return '';
        }

        $messages = '';
        foreach($_SESSION['TL_SIMPLETIPP_MESSAGE'] AS $message) {
            $messages .= sprintf("	<div class=\"message\">%s</div>\n", $message);
        }
        // Reset
        $_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
        return sprintf("\n<div class=\"simpletipp_messages\">\n%s</div>\n", $message);
    }

    public static function addSimpletippMessage($message) {
        if (!is_array($_SESSION['TL_SIMPLETIPP_MESSAGE'])) {
            $_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
        }
        $_SESSION['TL_SIMPLETIPP_MESSAGE'][] = $message;
    }

    public static function cleanupTipp($tipp) {
        $t = preg_replace ('/[^0-9]/',' ', $tipp);
        $t = preg_replace ('/\s+/',self::$TIPP_DIVIDER, $t);

        if (strlen($t) < 3) {
            return '';
        }

        $tmp = explode(self::$TIPP_DIVIDER, $t);

        if(strlen($tmp[0]) < 1 && strlen($tmp[1]) < 1) {
            return '';
        }

        $h = intval($tmp[0], 10);
        $a = intval($tmp[1], 10);
        return $h.self::$TIPP_DIVIDER.$a;
    }


    public static function cleanItem(&$item) {
        if (is_object($item)) {
            unset($item->password);
            unset($item->session);
            unset($item->autologin);
            unset($item->activation);
            foreach($item as $property => $value)  {
                if (is_string($value) && strlen($value) == 0) {
                    unset($item->$property);
                }
            }
        }
        if (is_array($item)) {
            foreach($item as $key => $value)  {
                if (is_string($value) && strlen($value) == 0) {
                    unset($item[$key]);
                }
            }
        }
    }

    public static function convertIconLinks(&$match) {

        foreach(array('h','a') as $suffix) {
            $iconKey  = 'icon_'.$suffix;
            $aliasKey = 'alias_'.$suffix;
            $strAlias = $match->$aliasKey;
            $url      = $match->$iconKey;

            // Wikimedia hack TODO search for '/??px'
            $url      =  str_replace('20px', '512px', $url);

            // TODO Read path from module configuration
            $strFile  = 'files/simpletipp-icons/' .$strAlias.'.'.pathinfo($url, PATHINFO_EXTENSION);

            if (!file_exists(TL_ROOT . '/'.$strFile)) {
                $fileData = file_get_contents($url);
                $file     = new \File($strFile);
                $file->write($fileData);
                $file->close();
            }

            $match->$iconKey = $strFile;
        }

    }



} // END class Simpletipp

