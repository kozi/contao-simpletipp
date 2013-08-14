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
 * Class SimpletippStatistics
 *
 * Front end content element "SimpletippStatistics".
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    simpletipp
 */

class ContentSimpletippStatistics extends \SimpletippModule {
    protected $strTemplate = 'ce_simpletipp_statistics';
    public static $types = array(
        'statBestMatches'       => 'Die 10 punktereichsten Spiele',
        'statBestTeams'         => 'Die 10 punktereichsten Mannschaften',
        'statPoints'            => 'Punkte pro Spieltag',
        'statHighscoreTimeline' => 'Tabellenplatzverlauf',
        'statSpecialMember'     => 'Tippanalyse',
    );

    public function generate() {

        if (!method_exists($this, $this->simpletipp_statistics_type)) {
            return sprintf('%s method does not exist!', $this->simpletipp_statistics_type);
        }

        return parent::generate();
    }

    protected function compile() {
        $microtime                  = microtime(true);
        $stats_type                 = $this->simpletipp_statistics_type;
        $this->Template->stats_type = $stats_type;
        $this->Template->title      = static::$types[$stats_type];

        $this->statsTemplate        = new FrontendTemplate('simpletipp_'.$stats_type);
        $this->statsTemplate->type  = $stats_type;
        $this->$stats_type();
        $this->Template->content    = $this->statsTemplate->parse();
        $this->Template->duration   = microtime(true) - $microtime;
    }


    protected function statBestMatches() {
        $matches = array();
        $result  = $this->Database->prepare("SELECT * FROM tl_simpletipp_match
            WHERE leagueID = ? AND isFinished = ?")
            ->execute($this->simpletipp->leagueID, '1');

        while ($result->next()) {
            $match               = $result->row();
            $match['points']     = $this->getPointsForMatch($match);
            $matches[] = $match;
        }

        usort($matches, function($match_a, $match_b) {
            return ($match_b['points']->points - $match_a['points']->points);
        });
        $this->statsTemplate->matches = array_slice($matches, 0, 10);
    }

    protected function statBestTeams() {
        $teams   = array();
        $result  = $this->Database->prepare("SELECT * FROM tl_simpletipp_match
            WHERE leagueID = ? AND isFinished = ?")
            ->execute($this->simpletipp->leagueID, '1');

        while ($result->next()) {
            $match      = $result->row();
            $tippPoints = $this->getPointsForMatch($match);
            $team_h     = $match['team_h'];
            $team_a     = $match['team_a'];

            if (!array_key_exists($team_h, $teams)) {
                $teams[$team_h] = array(
                    'name'       => explode(' - ', $match['title'])[0],
                    'icon'       => $match['icon_h'],
                    'name_short' => $team_h,
                    'points'     => array(0, 0 , 0, 0));
            }
            if (!array_key_exists($team_a, $teams)) {
                $teams[$team_a] = array(
                    'name'       => explode(' - ', $match['title'])[1],
                    'icon'       => $match['icon_a'],
                    'name_short' => $team_a,
                    'points'     => array(0, 0 , 0, 0));
            }

            $teams[$team_h]['points'][0] += $tippPoints->points;
            $teams[$team_h]['points'][1] += $tippPoints->perfect;
            $teams[$team_h]['points'][2] += $tippPoints->difference;
            $teams[$team_h]['points'][3] += $tippPoints->tendency;

            $teams[$team_a]['points'][0] += $tippPoints->points;
            $teams[$team_a]['points'][1] += $tippPoints->perfect;
            $teams[$team_a]['points'][2] += $tippPoints->difference;
            $teams[$team_a]['points'][3] += $tippPoints->tendency;

        }

        usort($teams, function($team_a, $team_b) {
            return ($team_b['points'][0] - $team_a['points'][0]);
        });
        $this->statsTemplate->teams = array_slice($teams, 0, 10);

    }

    protected function statHighscoreTimeline() {

        $memberArray = $this->cachedResult(static::$cache_key_highscore);

        if ($memberArray != null) {
            $this->statsTemplate->table = $memberArray;
            return true;
        }

        $memberArray = Simpletipp::getGroupMember($this->simpletipp->participant_group, true);
        foreach($memberArray as &$member) {
            $member->highscorePositions = array(0);
        }
        $result = $this->Database->prepare("SELECT groupName FROM tl_simpletipp_match
        WHERE leagueID = ? AND isFinished = ? GROUP BY groupName ORDER BY deadline")
            ->execute($this->simpletipp->leagueID, '1');
        $groups = array();
        while($result->next()) {

            $groups[] = $result->groupName;
        }


        for ($i=1;$i <= count($groups);$i++) {
            $matchgroups = array_slice($groups, 0, $i);
            $pos = 1;
            foreach($this->getHighscore($matchgroups) as $tableEntry) {
                $member = &$memberArray[$tableEntry->member_id];
                // $member->highscorePositions[] = $pos++;
                $member->highscorePositions   = $this->getTestArray(34, 65);
            }
        }
        $this->cachedResult(static::$cache_key_highscore, $memberArray, true);
        $this->statsTemplate->maxPos = 65;
        $this->statsTemplate->table  = $memberArray;
    }

    protected function statPoints() {
        $memberArray = $this->cachedResult(static::$cache_key_points);
        if ($memberArray != null) {
            $this->statsTemplate->table = $memberArray;
            return true;
        }

        $memberArray = Simpletipp::getGroupMember($this->simpletipp->participant_group, true);
        foreach($memberArray as &$member) {
            $member->pointsArray = array();
        }

        $result = $this->Database->prepare("SELECT groupName FROM tl_simpletipp_match
        WHERE leagueID = ? AND isFinished = ? GROUP BY groupName ORDER BY deadline")
            ->execute($this->simpletipp->leagueID, '1');

        while($result->next()) {
            $matchgroup = $result->groupName;
            foreach($this->getHighscore($matchgroup) as $tableEntry) {
                $member = &$memberArray[$tableEntry->member_id];
                //$member->pointsArray[$result->groupName] = intval($tableEntry->points);

                $member->pointsArray = $this->getTestPointArray();
            }
        }
        usort($memberArray, function($a, $b) {
            return strcmp($a->lastname.$a->firstname, $b->lastname.$b->firstname);
        });
        $this->cachedResult(static::$cache_key_points, $memberArray, true);
        $this->statsTemplate->table = $memberArray;
    }

    protected function statSpecialMember() {

        $table = $this->cachedResult(static::$cache_key_special);
        if ($table != null) {
            $this->statsTemplate->table = $table;
            return true;
        }

        $table = array(
            'maxTore' => array('realValue' => 0, 'title' => 'Die meisten Tore'),
            'minTore' => array('realValue' => 0, 'title' => 'Die wenigsten Tore'),
            'home'    => array('realValue' => 0, 'title' => 'Die meisten Heimsiege'),
            'draw'    => array('realValue' => 0, 'title' => 'Die meisten Unentschieden'),
            'away'    => array('realValue' => 0, 'title' => 'Die meisten Auswärtssiege'),
            'two_one' => array('realValue' => 0, 'title' => 'Die meisten 2:1 Tipps')
        );

        $result = $this->Database->prepare("SELECT id,result FROM tl_simpletipp_match
            WHERE leagueID = ? AND isFinished = ?")
            ->execute($this->simpletipp->leagueID, '1');
        while ($result->next()) {
            $match_ids[] = $result->id;

            $rArr = array_map('intval', explode(':', $result->result));
            var_dump($rArr);
            $table['maxTore']['realValue'] = $table['maxTore']['realValue'] + $rArr[0] + $rArr[1];
            $table['minTore']['realValue'] = $table['maxTore']['realValue'];
            $table['two_one']['realValue'] = ('2:1' == $result->result) ? ++$table['two_one']['realValue'] : $table['two_one']['realValue'];
            $table['draw']['realValue']    = ($rArr[0] == $rArr[1]) ? ++$table['draw']['realValue'] : $table['draw']['realValue'];
            $table['home']['realValue']    = ($rArr[0] > $rArr[1])  ? ++$table['home']['realValue'] : $table['home']['realValue'];
            $table['away']['realValue']    = ($rArr[0] < $rArr[1])  ? ++$table['away']['realValue'] : $table['away']['realValue'];
            // TODO Den realen Wert einfügen!
        }
        $result = $this->Database->execute("SELECT tl_member.id AS member_id,
                    tl_member.firstname, tl_member.lastname,
                    tl_simpletipp_tipp.tipp FROM tl_simpletipp_tipp, tl_member WHERE
                    tl_member.id = tl_simpletipp_tipp.member_id
                    AND match_id IN (".implode(',', $match_ids).")");

        $memberArray = array();
        while ($result->next()) {
            if (!array_key_exists($result->member_id, $memberArray )) {
                $member       = (Object) $result->row();
                $member->tore = 0; $member->two_one = 0;
                $member->home = 0; $member->draw = 0; $member->away = 0;
                unset($member->tipp);
                $memberArray[$result->member_id] = $member;
            }

            $m          = &$memberArray[$result->member_id];
            $tArr       = array_map('intval', explode(':', $result->tipp));
            $m->tore    = $m->tore + $tArr[0] + $tArr[1];
            $m->two_one = ('2:1' == $result->tipp) ? ++$m->two_one : $m->two_one;
            $m->draw    = ($tArr[0] == $tArr[1]) ? ++$m->draw : $m->draw;
            $m->home    = ($tArr[0] > $tArr[1])  ? ++$m->home : $m->home;
            $m->away    = ($tArr[0] < $tArr[1])  ? ++$m->away : $m->away;
        }


        // Tore
        $count = count($memberArray);
        usort($memberArray, function($a, $b) {
            return ($b->tore - $a->tore);
        });
        $table['maxTore']['member'] = array_slice($memberArray, 0, 3);
        $table['minTore']['member'] = array_reverse(array_slice($memberArray, $count-3, 3));

        usort($memberArray, function($a, $b) { return ($b->home - $a->home); });
        $table['home']['member'] = array_slice($memberArray, 0, 3);

        usort($memberArray, function($a, $b) { return ($b->away - $a->away); });
        $table['away']['member'] = array_slice($memberArray, 0, 3);

        usort($memberArray, function($a, $b) { return ($b->draw - $a->draw); });
        $table['draw']['member'] = array_slice($memberArray, 0, 3);

        usort($memberArray, function($a, $b) { return ($b->two_one - $a->two_one); });
        $table['two_one']['member'] = array_slice($memberArray, 0, 3);

        // TODO Den realen Wert einfügen!
        $this->cachedResult(static::$cache_key_special, $table);
        $this->statsTemplate->table = $table;

    }

    private function getPointsForMatch($match) {
        $points     = new stdClass();
        $points->points     = 0;
        $points->perfect    = 0;
        $points->difference = 0;
        $points->tendency   = 0;
        $ergebnis   = $match['result'];

        $tippResult = $this->Database->prepare("SELECT tipp FROM tl_simpletipp_tipp
                            WHERE match_id = ?")->execute($match['id']);
        while ($tippResult->next()) {

            $tippPoints = Simpletipp::getPoints($ergebnis, $tippResult->tipp, $this->pointFactors);
            $points->points     += $tippPoints->points;
            $points->perfect    += $tippPoints->perfect;
            $points->difference += $tippPoints->difference;
            $points->tendency   += $tippPoints->tendency;
        }
        return $points;
    }

    private function getTestPointArray() {
        $arr = array();
        for ($i=1;$i<35;$i++) {
            $arr[$i.'. Spieltag'] = rand(0,22);
        }
        return $arr;
    }


    private function getTestArray($count, $rangeMax) {
        $arr = array();
        for ($i=1;$i<$count;$i++) {
            $arr[] = rand(0, $rangeMax);
        }
        return $arr;
    }

}
