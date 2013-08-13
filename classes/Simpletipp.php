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
 * Class SimpletippCalendar
 *
 * @copyright  Martin Kozianka 2012-2013
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class Simpletipp extends System {
    public static $SIMPLETIPP_USER_ID = 'SIMPLETIPP_USER_ID';

    public static function getPoints($result, $tipp, $simpletippFactor = null) {
        $perfect    = 0;
        $difference = 0;
        $tendency   = 0;

        if (strlen($result) === 0 || strlen($tipp) === 0) {
            return new SimpletippPoints($simpletippFactor, 0, 0, 0);
        }
        $tmp = explode(":", $result);
        $rh = intval($tmp[0], 10); $ra = intval($tmp[1], 10);

        $tmp = explode(":", $tipp);
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

    public static function iconUrl($teamName, $prefix = '', $suffix = '.png') {
        $team = self::teamShortener($teamName);
        return $prefix.standardize($team).$suffix;
    }


    public static function getMatchesLeague($leagueID) {
        $matches = array();
        $result = \Database::getInstance()->prepare("SELECT id FROM tl_simpletipp_match
            WHERE leagueID = ?")->execute($leagueID);

        while($result->next()) {
            $matches[] = $result->id;
        }
        return $matches;
    }

    public static function getGroupMember($groupID, $complete = false, $order = '') {
        $member         = array();
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';
        $keys           = ($complete) ? '*' : 'id';

        $result = \Database::getInstance()->prepare("SELECT ".$keys." FROM tl_member WHERE groups LIKE ? ".$order)
            ->execute($participantStr);
        while($result->next()) {
            $member[$result->id] = ($complete) ? (Object) $result->row() : $result->id;
        }
        return $member;
    }

    public static function getNextMatch($leagueID) {

        $result = \Database::getInstance()->prepare("SELECT * FROM tl_simpletipp_match
            WHERE leagueID = ? AND deadline > ?
            ORDER BY deadline ASC, id ASC")->limit(1)->execute($leagueID, time());

        if ($result->numRows == 0) {
            // no next match
            return null;
        }
        return (Object) $result->row();
    }

    public static function getNotTippedUser($groupID, $match_id) {
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';

        $result = \Database::getInstance()->prepare("SELECT tblu.*
             FROM tl_member as tblu
             LEFT JOIN tl_simpletipp_tipp AS tblt
             ON ( tblu.id = tblt.member_id AND tblt.match_id = ? AND tblu.groups LIKE ?)
             WHERE tblt.id IS NULL
             ORDER BY tblu.lastname")->execute($match_id, $participantStr);

        $userArr = array();
        while ($result->next()) {
            $userArr[] = $result->row();
        }
        return $userArr;
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

    public static function cleanupTipp($tipp){
        $t = preg_replace ('/[^0-9]/',' ',$tipp);
        $t = preg_replace ('/\s+/',':',$t);

        if (strlen($t) < 3) {
            return '';
        }

        $tmp = explode(":", $t);

        if(strlen($tmp[0]) < 1 && strlen($tmp[1]) < 1) {
            return '';
        }

        $h = intval($tmp[0], 10);
        $a = intval($tmp[1], 10);
        return $h.':'.$a;
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

} // END class Simpletipp


