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
 * Class SimpletippRanking
 *
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippRanking extends SimpletippModule {
	protected $strTemplate = 'simpletipp_ranking_default';

	public function generate() {
		$this->strTemplate = $this->simpletipp_template;
		return parent::generate();
	}

	protected function compile() {
        $start   = microtime(true);
        $result  = $this->Database->prepare(
            "SELECT title, title_short, icon_h, icon_a, team_h, team_a, result, isFinished
            FROM tl_simpletipp_match WHERE leagueID = ?")
            ->execute($this->simpletipp->leagueID);

        $ranking = array();
        $iconUrl = Environment::get('url').'/files/vereinslogos/';
        while($result->next()) {

            if ($ranking[$result->team_h] === null) {
                $arr   = explode('-', $result->title);
                $name  = trim($arr[0]);
                $arr   = explode('-', $result->title_short);
                $short = trim($arr[0]);
                $icon  = str_replace('http://www.openligadb.de/images/teamicons/', $iconUrl, $result->icon_h);
                $ranking[$result->team_h] = new Team($name, $short, $result->team_h, $icon);
            }

            if ($ranking[$result->team_a] === null) {
                $arr   = explode('-', $result->title);
                $name  = trim($arr[1]);
                $arr   = explode('-', $result->title_short);
                $short = trim($arr[1]);
                $icon  = str_replace('http://www.openligadb.de/images/teamicons/', $iconUrl, $result->icon_a);
                $ranking[$result->team_a] = new Team($name, $short, $result->team_a, $icon);
            }
            $team_h = &$ranking[$result->team_h];
            $team_a = &$ranking[$result->team_a];

            $erg    = array_map('intval', explode(':',$result->result));
            $team_h->addGoals($erg[0], $erg[1]);
            $team_a->addGoals($erg[1], $erg[0]);

            if ($result->isFinished === '1') {
                if ($erg[0] === $erg[1]) {
                    $team_h->draws += 1;
                    $team_a->draws += 1;
                }
                elseif ($erg[0] > $erg[1]) {
                    $team_h->wins   += 1;
                    $team_a->losses += 1;
                }
                else {
                    $team_h->losses += 1;
                    $team_a->wins   += 1;
                }
            }


        }
        // Sortieren
        usort($ranking, function($team_a, $team_b) {
            $a = $team_a->getPoints(); $b = $team_b->getPoints();
            if ($a > $b) return -1;
            if ($a < $b) return 1;

            $a = $team_a->goalDiff();  $b = $team_b->goalDiff();
            if ($a > $b) return -1;
            if ($a < $b) return 1;

            $a = $team_a->goalsPlus;  $b = $team_b->goalsPlus;
            if ($a > $b) return -1;
            if ($a < $b) return 1;

            // Hier fehlen noch ein paar Regeln (siehe: http://www.dfb.de/?id=82917)
            return 0;
        });

        $this->Template->ranking = $ranking;
    }
} // END class SimpletippRanking



class Team {
    public $alias;
    public $icon;
    public $matches    = 0;
    public $wins       = 0;
    public $draws      = 0;
    public $losses     = 0;
    public $goalsPlus  = 0;
    public $goalsMinus = 0;

    public function __construct($name, $short, $alias, $icon) {
        $this->name  = $name;
        $this->short = $short;
        $this->icon  = $icon;
        $this->alias = standardize($alias);
    }

    public function addGoals($plus, $minus) {
        $this->goalsPlus  += $plus;
        $this->goalsMinus += $minus;
    }

    public function getPoints() {
        return (($this->wins * 3) + $this->draws);
    }

    public function goalDiff() {
        return ($this->goalsPlus - $this->goalsMinus);
    }

    public function __toString() {
        $diff    = ($this->goalDiff() > 0) ? '+'.$this->goalDiff(): $this->goalDiff();
        $attribs = array($this->getPoints(), $this->wins, $this->draws, $this->losses, $diff);
        return $this->name.' ['.$this->short.', '.$this->alias.'] ('.implode(', ', $attribs).')';
    }

}