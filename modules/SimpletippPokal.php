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


class SimpletippPokal extends SimpletippModule {
    private $groupAliases = array('pokal_group', 'pokal_16', 'pokal_8', 'pokal_4', 'pokal_2', 'pokal_finale');
    private $groups       = array();
    private $pokal_ranges = array();

    private $currentGroup = null;
    private $nextGroup    = null;

    protected $strTemplate = 'simpletipp_pokal_default';

	public function generate() {
        if (TL_MODE == 'BE') {
            $this->Template            = new BackendTemplate('be_wildcard');
            $this->Template->wildcard  = '### SimpletippPokal ###';
            $this->Template->wildcard .= '<br/>'.$this->headline;
            return $this->Template->parse();
        }

        $this->loadLanguageFile('tl_simpletipp');
        $this->strTemplate  = $this->simpletipp_template;


        $i    = 0;
        $pInt = 0;
        foreach(deserialize($this->simpletipp->pokal_ranges) as $item) {
            $cInt = intval($item);
            if (($cInt != $pInt+1) && $pInt != 0) {
                $i++;
            }
            $key  = $this->groupAliases[$i];
            $this->pokal_ranges[$key][] = $item;
            $pInt = intval($item);
        }

        $i = 0;
        foreach ($this->groupAliases as $alias) {
            $this->groups[$alias] = $this->getGroupObject($i++, $alias);
            // set current group
            if ($this->groups[$alias]->current) {
                $this->currentGroup = &$this->groups[$alias];
            }
            // set next group
            if ($this->groups[$alias]->next) {
                $this->nextGroup = &$this->groups[$alias];
            }

        }

        return parent::generate();
	}

	protected function compile() {

        if ($this->currentGroup == null && $this->nextGroup != null) {
                $calculatePairs = false;
                // Wurde schon ausgelost?
                if ($this->nextGroup->highscores === null) {
                    // Wurden schon alle Ergebnisse der letzten Phase eingetragen?
                    if ($this->nextGroup->index > 0) {
                        $group_alias = $this->groupAliases[$this->nextGroup->index - 1];
                        $last_group  = $this->groups[$group_alias];
                        $result = $this->Database->prepare("SELECT * FROM tl_simpletipp_match
                            WHERE groupName IN ('".implode("','", $last_group->matchgroups)."')
                            AND (result = ? OR isFinished = ?)")->execute('', 0);
                        $calculatePairs = ($result->numRows == 0);
                    } else {
                        $calculatePairs = true;
                    }
                }
                if ($calculatePairs) {

                    echo "<p>calculatePairs()";
                    $this->calculatePairs();
                }
        }

        $this->Template->groups       = $this->groups;
    }

    private function getGroupObject($index, $alias) {
        $group = new stdClass();
        $group->index       = $index;
        $group->name        = $GLOBALS['TL_LANG']['tl_simpletipp'][$alias][0];
        $group->alias       = $alias;
        $group->matchgroups = $this->pokal_ranges[$alias];
        $group->highscores  = $this->getGroupHighscores($group, deserialize($this->simpletipp->$alias));

        if (is_array($group->matchgroups) && count($group->matchgroups) > 0) {
            $group->first       = $group->matchgroups[0];
            $group->last        = $group->matchgroups[count($group->matchgroups)-1];
        }

        $result = $this->Database->prepare("SELECT deadline FROM tl_simpletipp_match
                WHERE groupName = ? ORDER BY deadline ASC")->limit(1)->execute($group->first);

        if ($result->numRows == 1) {
            $group->start = $result->deadline;
        }

        $result = $this->Database->prepare("SELECT deadline FROM tl_simpletipp_match
                WHERE groupName = ? ORDER BY deadline DESC")->limit(1)->execute($group->last);

        if ($result->numRows == 1) {
            $group->end = $result->deadline;
        }

        $group->current     = ($this->now > $group->start && $this->now < $group->end);
        $group->next        = ($this->nextGroup == null && $this->now < $group->start);

        $group->cssClass    = $group->alias;
        $group->cssClass   .= ($group->current) ? ' current':'';
        $group->cssClass   .= ($group->next) ? ' next':'';
        $group->cssClass   .= ($this->now < $group->start) ? ' upcoming':'';
        $group->cssClass   .= ($this->now > $group->end) ? ' finished':'';

        return $group;
    }

    private function getGroupHighscores($group, $pairings) {
        if ($pairings === null) {
            return null;
        }
        $highscores = array();
        foreach ($pairings as $memberArr) {
            $highscores[] = $this->getHighscore($group->matchgroups, $memberArr);
        }
        return $highscores;
    }

    private function calculatePairs() {
        $pairings = array();
        if ($this->nextGroup->alias == 'pokal_group') {
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
        else if ($this->nextGroup->alias == 'pokal_16') {
            // TODO Gruppen auswerten und auslosen

        }
        else {
            // TODO Paare auswerten und auslosen
        }

        $this->Database->prepare("UPDATE tl_simpletipp SET ".$this->nextGroup->alias." = ?
            WHERE id = ?")->execute(serialize($pairings), $this->simpletipp->id);
    }
} // END class SimpletippPokal