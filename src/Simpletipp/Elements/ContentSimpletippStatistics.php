<?php

namespace Simpletipp\Elements;

use Simpletipp\Models\SimpletippMatchModel;
use Simpletipp\Models\SimpletippTippModel;
use Simpletipp\SimpletippModule;

/**
 * Class SimpletippStatistics
 *
 * Front end content element "SimpletippStatistics".
 * @copyright  Martin Kozianka 2014-2018
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    simpletipp
 */
class ContentSimpletippStatistics extends SimpletippModule
{
    protected $strTemplate = 'ce_simpletipp_statistics';
    public static $types = [
        'statBestMatches' => 'Die 10 punktereichsten Spiele',
        'statBestTeams' => 'Die 10 punktereichsten Mannschaften',
        'statPoints' => 'Punkte pro Spieltag',
        'statHighscoreTimeline' => 'Tabellenplatzverlauf',
        'statSpecialMember' => 'Tippanalyse',
    ];

    public function generate()
    {
        if (!method_exists($this, $this->simpletipp_statistics_type)) {
            return sprintf('%s method does not exist!', $this->simpletipp_statistics_type);
        }

        if (TL_MODE == 'BE') {
            $this->Template = new \BackendTemplate('be_wildcard');
            $this->Template->wildcard = '### SimpletippStatistics ###';
            $this->Template->wildcard .= '<br/>' . $this->simpletipp_statistics_type;
            $this->Template->wildcard .= '<br/>' . static::$types[$this->simpletipp_statistics_type];
            return $this->Template->parse();
        }

        return parent::generate();
    }

    protected function compile()
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/simpletipp/assets/simpletipp-statistics.js';

        $microtime = microtime(true);
        $stats_type = $this->simpletipp_statistics_type;
        $this->Template->stats_type = $stats_type;
        $this->Template->title = static::$types[$stats_type];

        $this->statsTemplate = new \FrontendTemplate('simpletipp_' . $stats_type);

        $this->statsTemplate->user = $this->User;
        $this->statsTemplate->type = $stats_type;
        $this->$stats_type();
        $this->Template->content = $this->statsTemplate->parse();
        $this->Template->duration = microtime(true) - $microtime;
    }

    protected function statBestMatches()
    {
        // Cached result?
        $arrBestMatches = $this->cache(__METHOD__);
        if ($arrBestMatches != null) {
            $this->statsTemplate->matches = $arrBestMatches;
            return true;
        }

        $arrMatches = [];
        $objMatches = SimpletippMatchModel::findBy(
            ['leagueID = ?', 'isFinished = ?'],
            [$this->simpletipp->leagueID, '1']
        );

        if ($objMatches === null) {
            return true;
        }

        foreach ($objMatches as $objMatch) {
            $objMatch->teamHome = $objMatch->getRelated('team_h');
            $objMatch->teamAway = $objMatch->getRelated('team_a');
            $arrMatches[] = (object) [
                "title" => $match->title,
                "title_short" => $match->title_short,
                "homeLogo" => $match->teamHome->logoPath(),
                "homeThree" => $match->teamHome->three,
                "awayLogo" => $match->teamAway->logoPath(),
                "awayThree" => $match->teamAway->three,
                "objPoints" => $this->getPointsForMatch($objMatch),
            ];
        }

        usort($arrMatches, function ($match_a, $match_b) {
            return ($match_b->objPoints->points - $match_a->objPoints->points);
        });

        $arrBestMatches = array_slice($arrMatches, 0, 10);
        $this->cache(__METHOD__, $arrBestMatches, true);
        $this->statsTemplate->matches = $arrBestMatches;
    }

    protected function statBestTeams()
    {
        // Cached result?
        $arrBestTeams = $this->cache(__METHOD__);
        if ($arrBestTeams != null) {
            $this->statsTemplate->teams = $arrBestTeams;
            return true;
        }

        $arrTeams = [];
        $objMatches = SimpletippMatchModel::findBy(
            ['leagueID = ?', 'isFinished = ?'],
            [$this->simpletipp->leagueID, '1']
        );

        if ($objMatches === null) {
            return true;
        }

        foreach ($objMatches as $objMatch) {
            $tippPoints = $this->getPointsForMatch($objMatch);
            $teamHome = $objMatch->getRelated('team_h');
            $teamAway = $objMatch->getRelated('team_a');
            if (!array_key_exists($teamHome->id, $arrTeams)) {
                $arrTeams[$teamHome->id] = [
                    'name' => $teamHome->name,
                    'icon' => $teamHome->logoPath(),
                    'name_short' => $teamHome->short,
                    'points' => [0, 0, 0, 0],
                ];
            }
            if (!array_key_exists($teamAway->id, $arrTeams)) {
                $arrTeams[$teamAway->id] = [
                    'name' => $teamAway->name,
                    'icon' => $teamAway->logoPath(),
                    'name_short' => $teamAway->short,
                    'points' => [0, 0, 0, 0],
                ];
            }

            $arrTeams[$teamHome->id]['points'][0] += $tippPoints->points;
            $arrTeams[$teamHome->id]['points'][1] += $tippPoints->perfect;
            $arrTeams[$teamHome->id]['points'][2] += $tippPoints->difference;
            $arrTeams[$teamHome->id]['points'][3] += $tippPoints->tendency;

            $arrTeams[$teamAway->id]['points'][0] += $tippPoints->points;
            $arrTeams[$teamAway->id]['points'][1] += $tippPoints->perfect;
            $arrTeams[$teamAway->id]['points'][2] += $tippPoints->difference;
            $arrTeams[$teamAway->id]['points'][3] += $tippPoints->tendency;

        }

        usort($arrTeams, function ($team_a, $team_b) {
            return ($team_b['points'][0] - $team_a['points'][0]);
        });

        $arrBestTeams = array_slice($arrTeams, 0, 10);
        $this->cache(__METHOD__, $arrBestTeams, true);
        $this->statsTemplate->teams = $arrBestTeams;

    }

    protected function statHighscoreTimeline()
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/simpletipp/assets/chartjs/Chart.bundle.min.js|static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/simpletipp/assets/chartjs/chartjs-plugin-datalabels.min.js|static';

        // Cached result?
        $dataObject = $this->cache(__METHOD__);
        if ($dataObject != null) {
            $this->statsTemplate->data = $dataObject;
            return true;
        }

        $objMembers = $this->simpletipp->getGroupMember();
        if ($objMembers !== null) {
            foreach ($objMembers as $objMember) {
                $memberArray[$objMember->username] = (object) [
                    "username" => $objMember->username,
                    "firstname" => $objMember->firstname,
                    "lastname" => $objMember->lastname,
                    "highscorePositions" => [],
                ];
            }
        }

        $result = $this->Database->prepare("SELECT groupName FROM tl_simpletipp_match
        WHERE leagueID = ? AND isFinished = ? GROUP BY groupName ORDER BY deadline")
            ->execute($this->simpletipp->leagueID, '1');
        $groups = [];
        while ($result->next()) {
            $groups[] = $result->groupName;
        }

        $dataObject = new \stdClass();
        $dataObject->labels = ["0."];

        for ($i = 1; $i <= count($groups); $i++) {
            $dataObject->labels[] = $i . ".";

            $pos = 1;
            $matchgroups = array_slice($groups, 0, $i);
            $highscoreTable = $this->getHighscore($matchgroups);
            foreach ($highscoreTable as $tableEntry) {
                $highscorePos = intval($pos++) * (-1);
                $highscoreHistory = $memberArray[$tableEntry->username]->highscorePositions;
                if (count($highscoreHistory) === 0) {
                    // Do insertion twice to get the same entry for the matchday 0 and 1
                    $highscoreHistory[] = $highscorePos;
                }
                $highscoreHistory[] = $highscorePos;
                $memberArray[$tableEntry->username]->highscorePositions = $highscoreHistory;
            }
        }

        uasort($memberArray, function ($a, $b) {
            return strcmp($a->lastname . $a->firstname, $b->lastname . $b->firstname);
        });

        $max = count($memberArray);
        $dataObject->table = $memberArray;
        $dataObject->roundedMaximum = (ceil($max) % 5 === 0) ? ceil($max) : round(($max + 5 / 2) / 5) * 5;

        $this->cache(__METHOD__, $dataObject, true);
        $this->statsTemplate->data = $dataObject;
    }

    protected function statPoints()
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/simpletipp/assets/chartjs/Chart.bundle.min.js|static';
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/simpletipp/assets/chartjs/chartjs-plugin-datalabels.min.js|static';

        $data = $this->cache(__METHOD__);

        if ($data != null) {
            $this->statsTemplate->data = $data;
            return true;
        }

        $objMembers = $this->simpletipp->getGroupMember();
        $memberArray = [];

        if ($objMembers !== null) {
            foreach ($objMembers as $objMember) {
                $memberArray[$objMember->username] = (object) [
                    "id" => $objMember->id,
                    "firstname" => $objMember->firstname,
                    "lastname" => $objMember->lastname,
                    "username" => $objMember->username,
                    "pointsArray" => [[], [], []],
                    "punkte" => [],
                ];
            }
        }

        $data = new \stdClass();
        $data->maxPoints = 0;
        $data->labels = [];

        $result = $this->Database->prepare("SELECT groupName FROM tl_simpletipp_match
        WHERE leagueID = ? AND isFinished = ? GROUP BY groupName ORDER BY deadline")
            ->execute($this->simpletipp->leagueID, '1');

        while ($result->next()) {
            $mgShort = intval($result->groupName) . '.';
            $data->labels[] = $mgShort;
            $highscore = $this->getHighscore($result->groupName);
            foreach ($highscore as $e) {
                $intPoints = intval($e->points);
                $data->maxPoints = ($intPoints > $data->maxPoints) ? $intPoints : $data->maxPoints;

                $mem = $memberArray[$e->username];
                $mem->punkte[] = $intPoints;
                $mem->pointsArray[0][] = intval($e->sum_perfect) * $this->pointFactors->perfect;
                $mem->pointsArray[1][] = intval($e->sum_difference) * $this->pointFactors->difference;
                $mem->pointsArray[2][] = intval($e->sum_tendency) * $this->pointFactors->tendency;
            }
        }
        uasort($memberArray, function ($a, $b) {
            return strcmp($a->lastname . $a->firstname, $b->lastname . $b->firstname);
        });

        $data->table = $memberArray;
        $this->cache(__METHOD__, $data, true);

        $this->statsTemplate->data = $data;
    }

    protected function statSpecialMember()
    {
        $table = $this->cache(__METHOD__);
        if ($table != null) {
            $this->statsTemplate->table = $table;
            return true;
        }

        $table = [
            'maxTore' => ['realValue' => 0, 'title' => 'Die meisten Tore'],
            'minTore' => ['realValue' => 0, 'title' => 'Die wenigsten Tore'],
            'home' => ['realValue' => 0, 'title' => 'Die meisten Heimsiege'],
            'draw' => ['realValue' => 0, 'title' => 'Die meisten Unentschieden'],
            'away' => ['realValue' => 0, 'title' => 'Die meisten Auswärtssiege'],
            'two_one' => ['realValue' => 0, 'title' => 'Die meisten 2:1 Tipps'],
        ];

        $result = $this->Database->prepare("SELECT id,result FROM tl_simpletipp_match
            WHERE leagueID = ? AND isFinished = ?")
            ->execute($this->simpletipp->leagueID, '1');
        $arrMatchIds = [];

        while ($result->next()) {
            $arrMatchIds[] = $result->id;

            $rArr = array_map('intval', explode(':', $result->result));
            $table['maxTore']['realValue'] = $table['maxTore']['realValue'] + $rArr[0] + $rArr[1];
            $table['minTore']['realValue'] = $table['maxTore']['realValue'];
            $table['two_one']['realValue'] = ('2:1' == $result->result) ? ++$table['two_one']['realValue'] : $table['two_one']['realValue'];
            $table['draw']['realValue'] = ($rArr[0] == $rArr[1]) ? ++$table['draw']['realValue'] : $table['draw']['realValue'];
            $table['home']['realValue'] = ($rArr[0] > $rArr[1]) ? ++$table['home']['realValue'] : $table['home']['realValue'];
            $table['away']['realValue'] = ($rArr[0] < $rArr[1]) ? ++$table['away']['realValue'] : $table['away']['realValue'];
        }

        if (sizeof($arrMatchIds) === 0) {
            return;
        }

        $result = $this->Database->execute("SELECT tl_member.id AS member_id,
                    tl_member.firstname, tl_member.lastname,
                    tl_simpletipp_tipp.tipp FROM tl_simpletipp_tipp, tl_member WHERE
                    tl_member.id = tl_simpletipp_tipp.member_id
                    AND match_id IN (" . implode(',', $arrMatchIds) . ")");

        $memberArray = [];
        $maxCount = sizeof($arrMatchIds);
        while ($result->next()) {
            if (!array_key_exists($result->member_id, $memberArray)) {
                $member = (Object) $result->row();
                $member->tore = 0;
                $member->two_one = 0;
                $member->home = 0;
                $member->draw = 0;
                $member->away = 0;
                $member->tippCount = 0;
                $member->maxCount = $maxCount;
                unset($member->tipp);
                $memberArray[$result->member_id] = $member;
            }

            $m = &$memberArray[$result->member_id];
            $tArr = array_map('intval', explode(':', $result->tipp));
            $m->tore = $m->tore + $tArr[0] + $tArr[1];
            $m->two_one = ('2:1' == $result->tipp) ? ++$m->two_one : $m->two_one;
            $m->draw = ($tArr[0] == $tArr[1]) ? ++$m->draw : $m->draw;
            $m->home = ($tArr[0] > $tArr[1]) ? ++$m->home : $m->home;
            $m->away = ($tArr[0] < $tArr[1]) ? ++$m->away : $m->away;
            $m->tippCount = ++$m->tippCount;
        }

        // Filter "dead "user who missed too much matches 6% (tippCount / maxCount > 0,94)
        $memberArray = array_filter($memberArray, function ($member) {
            return (($member->tippCount / $member->maxCount) > 0.94);
        });

        // TODO Den link zu dem Benutzer

        usort($memberArray, function ($a, $b) {return ($b->tore - $a->tore);});
        $table['maxTore']['member'] = array_slice($memberArray, 0, 3);
        $table['minTore']['member'] = array_reverse(array_slice($memberArray, count($memberArray) - 3, 3));

        usort($memberArray, function ($a, $b) {return ($b->home - $a->home);});
        $table['home']['member'] = array_slice($memberArray, 0, 3);

        usort($memberArray, function ($a, $b) {return ($b->away - $a->away);});
        $table['away']['member'] = array_slice($memberArray, 0, 3);

        usort($memberArray, function ($a, $b) {return ($b->draw - $a->draw);});
        $table['draw']['member'] = array_slice($memberArray, 0, 3);

        usort($memberArray, function ($a, $b) {return ($b->two_one - $a->two_one);});
        $table['two_one']['member'] = array_slice($memberArray, 0, 3);

        $this->cache(__METHOD__, $table);
        $this->statsTemplate->table = $table;

    }

    private function getPointsForMatch($match)
    {
        $points = new \stdClass();
        $points->points = 0;
        $points->perfect = 0;
        $points->difference = 0;
        $points->tendency = 0;
        $ergebnis = $match->result;

        $tippResult = $this->Database->prepare("SELECT tipp FROM tl_simpletipp_tipp
                            WHERE match_id = ?")->execute($match->id);
        while ($tippResult->next()) {

            $tippPoints = SimpletippTippModel::getPoints($ergebnis, $tippResult->tipp, $this->pointFactors);
            $points->points += $tippPoints->points;
            $points->perfect += $tippPoints->perfect;
            $points->difference += $tippPoints->difference;
            $points->tendency += $tippPoints->tendency;
        }
        return $points;
    }

    private function getTestArray($count, $rangeMax, $factor = -1)
    {
        $arr = [];
        $value = 0;
        $arr[] = $value;
        for ($i = 1; $i < $count; $i++) {
            $min = max($value - 10, 0);
            $max = min($value + 10, $rangeMax);
            $value = rand($min, $max);
            $arr[] = $value * $factor;
        }
        return $arr;
    }

}
