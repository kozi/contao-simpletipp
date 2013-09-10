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
 * Class SimpletippPokal
 *
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippPokal extends Backend {
    private static $groupAliases = array('pokal_group', 'pokal_16', 'pokal_8', 'pokal_4', 'pokal_2', 'pokal_finale');
    private $groups              = array();
    private $nextGroup           = null;
    private $currentGroup        = null;
    private $finishedGroup       = null;

    public function __construct() {
        $this->import('Database');
        $this->loadLanguageFile('tl_simpletipp');
        $i = 0;
        foreach (static::$groupAliases as $alias) {
            $group = new stdClass();
            $group->index         = $i++;
            $group->alias         = $alias;
            $group->name          = $GLOBALS['TL_LANG']['tl_simpletipp'][$alias][0];
            $group->cssClass      = $alias;
            $group->matchgroups   = array();
            $this->groups[$alias] = $group;
        }
    }

    public function getGroups($simpletippObj) {

        // Deadlines holen
        $result = $this->Database->prepare("SELECT groupName, MIN(deadline) AS start, MAX(deadline) AS end
                FROM tl_simpletipp_match WHERE leagueID = ? GROUP BY groupName")
            ->execute($simpletippObj->leagueID);

        $deadlines = array();
        while($result->next()) {
            $deadlines[$result->groupName] = $result->row();
        }

        // Ranges parsen
        $ranges = array();
        $i      = 0;
        $pInt   = 0;
        foreach(deserialize($simpletippObj->pokal_ranges) as $item) {
            $cInt = intval($item);
            if (($cInt != $pInt+1) && $pInt != 0) {
                $i++;
            }
            $ranges[$i][] = $item;
            $pInt = intval($item);
        }
        if (count($ranges) != count(self::$groupAliases)) {
            return null;
        }

        // Gruppenobjekte befüllen
        $now          = time();

        foreach($this->groups as $group) {
            $group->matchgroups = $ranges[$group->index];

            $alias = $group->alias;
            $group->pairings = deserialize($simpletippObj->$alias);

            $group->first     = $group->matchgroups[0];
            $group->last      = $group->matchgroups[count($group->matchgroups)-1];
            $group->start     = $deadlines[$group->first]['start'];
            $group->end       = $deadlines[$group->last]['end'];

            $group->current   = ($now > $group->start && $now < $group->end);
            $group->next      = ($this->nextGroup == null && $now < $group->start);
            $group->finished  = ($now > $group->end);

            $group->cssClass .= ($group->current) ? ' current':'';
            $group->cssClass .= ($group->next) ? ' next':'';
            $group->cssClass .= ($group->finished) ? ' finished':'';
            $group->cssClass .= ($now < $group->start) ? ' upcoming':'';


            $this->nextGroup     = ($group->next) ? $group : $this->nextGroup;
            $this->currentGroup  = ($group->current) ? $group : $this->currentGroup;
            $this->finishedGroup = ($group->finished) ? $group : $this->finishedGroup;
        }
        return $this->groups;
    }


    public function calculate() {
        $this->simpletipp = SimpletippModel::findByPk(Input::get('id'));
        $this->getGroups($this->simpletipp);

        if ($this->currentGroup != null) {
            Message::add(sprintf('<strong>%s</strong> (%s-%s) läuft noch!', $this->currentGroup->name,
                $this->currentGroup->first, $this->currentGroup->last), 'TL_ERROR');
            $this->redirect(Environment::get('script').'?do=simpletipp_groups');
        }

        if ($this->nextGroup != null && $this->nextGroup->pairings != null) {
            Message::add(sprintf('<strong>%s</strong> (%s-%s) wurde schon ausgelost!', $this->nextGroup->name,
                $this->nextGroup->first, $this->nextGroup->last), 'TL_ERROR');
            $this->redirect(Environment::get('script').'?do=simpletipp_groups');
        }
        $result = $this->Database->prepare("SELECT * FROM tl_simpletipp_match
            WHERE groupName IN ('".implode("','", $this->finishedGroup->matchgroups)."')
                            AND (result = ? OR isFinished = ?)")->execute('', 0);
        if ($result->numRows == 0) {
            if (Input::get('confirm') == '1') {
                $this->calculatePairs();
            }
            else {
                Message::add(sprintf('<strong>%s</strong> (%s-%s) Wirklich auslosen? <button onclick="location.href=\'%s\'">Auslosen!</button>',
                    $this->nextGroup->name, $this->nextGroup->first, $this->nextGroup->last,
                    Environment::get('request').'&confirm=1'), 'TL_CONFIRM');
            }
        }
        else {
            Message::add(sprintf('<strong>%s</strong> (%s-%s): Es sind noch nicht alle Spiele eingetragen!', $this->finishedGroup->name,
                $this->finishedGroup->first, $this->finishedGroup->last), 'TL_ERROR');
        }
        $this->redirect(Environment::get('script').'?do=simpletipp_groups');
    }

    private function calculatePairs() {

        $pairings = array();
        if ($this->finishedGroup === null) {
            // 8 Gruppen auslosen
            $user = Simpletipp::getGroupMember($this->simpletipp->participant_group);
            shuffle($user);

            $total    = count($user);
            $minSize  = floor($total / 8);
            $rest     = $total % 8;
            $oneGroup = array();
            foreach($user as $userId) {
                $oneGroup[] = $userId;
                if((count($oneGroup) == ($minSize+1) && $rest > 0) || (count($oneGroup) == $minSize && $rest <= 0)) {
                    $rest--;
                    $pairings[] = $oneGroup;
                    $oneGroup   = array();
                }
            }
            if(count($oneGroup) > 0) {
                $pairings[] = $oneGroup;
            }
        }
        else {

            $winRanks = 1;
            if ($this->finishedGroup->alias == 'pokal_group') {
                // Die ersten 4 in jeder Tabelle gewinnen
                $winRanks = 4;
            }

            // Gruppen auswerten und auslosen
            $this->import('SimpletippModulePokal');
            $this->SimpletippModulePokal->setSimpletipp($this->simpletipp->id);
            $highscores = $this->SimpletippModulePokal->getGroupHighscores($this->finishedGroup);
            $user = array();
            foreach($highscores as $highscore) {
                // Nur die memberIDs speichern
                $highscore = array_map(function($row) { return $row->id; }, $highscore);

                $user      = array_merge($user, array_slice($highscore, 0, $winRanks));
            }
            shuffle($user);
            $i = 0;
            while($i < count($user)){
                $pairings[] = array($user[$i++], $user[$i++]);
            }
        }

        $this->Database->prepare("UPDATE tl_simpletipp SET ".$this->nextGroup->alias." = ?
            WHERE id = ?")->execute(serialize($pairings), $this->simpletipp->id);

        $message = sprintf('Paarungen für <strong>%s</strong> ausgelost!', $this->nextGroup->name);
        Message::add($message, 'TL_NEW');
        return true;
    }

} // END class SimpletippPokal

