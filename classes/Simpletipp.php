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

        $points = new SimpletippPoints($simpletippFactor, 0, 0, 0);

        if (strlen($result) === 0 || strlen($tipp) === 0) {
            $points->wrong = 0;
            return $points;
        }
        $tmp = explode(":", $result);
        $rh = intval($tmp[0], 10); $ra = intval($tmp[1], 10);

        $tmp = explode(":", $tipp);
        $th = intval($tmp[0], 10); $ta = intval($tmp[1], 10);

        if ($rh === $th && $ra === $ta) {
            $points->wrong   = 0;
            $points->perfect = 1;
            return $points;
        }

        if (($rh-$ra) === ($th-$ta)) {
            $points->wrong      = 0;
            $points->difference = 1;
            return $points;
        }

        if (($rh < $ra && $th < $ta) || ($rh > $ra && $th > $ta)) {
            $points->wrong    = 0;
            $points->tendency = 1;
            return $points;
        }

        $points->wrong = 1;
        return $points;
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

    public static function getMatches($leagueID, $matchgroup = null) {
        $matches = array();
        $where   = ($matchgroup !== null) ? ' WHERE leagueID = ? AND groupName = ?' : ' WHERE leagueID = ?';
        $result  = \Database::getInstance()->prepare("SELECT id FROM tl_simpletipp_match".$where)
            ->execute($leagueID, $matchgroup);

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

} // END class Simpletipp


