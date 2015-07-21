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

namespace Simpletipp\Modules;

use Simpletipp\Models\SimpletippMatchModel;
use \Simpletipp\SimpletippModule;
/**
 * Class SimpletippRanking
 *
 * @copyright  Martin Kozianka 2011-2015
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

        $collectionMatches = SimpletippMatchModel::findBy('leagueID', $this->simpletipp->leagueID);

        $ranking = array();
        foreach($collectionMatches as $match)
        {
            $match->teamHome = $match->getRelated('team_h');
            $match->teamAway = $match->getRelated('team_a');

            if ($ranking[$match->teamHome->id] === null)
            {
                $ranking[$match->teamHome->id] = clone $match->teamHome;
            }
            if ($ranking[$match->teamAway->id] === null)
            {
                $ranking[$match->teamAway->id] = clone $match->teamAway;
            }
            $teamHome = &$ranking[$match->teamHome->id];
            $teamAway = &$ranking[$match->teamAway->id];
            $erg      = array_map('intval', explode(':',$match->result));

            $teamHome->addGoals($erg[0], $erg[1]);
            $teamAway->addGoals($erg[1], $erg[0]);

            if ($match->isFinished === '1')
            {
                if ($erg[0] === $erg[1])
                {
                    $teamHome->draws += 1;
                    $teamAway->draws += 1;
                }
                elseif ($erg[0] > $erg[1])
                {
                    $teamHome->wins   += 1;
                    $teamAway->losses += 1;
                }
                else
                {
                    $teamHome->losses += 1;
                    $teamAway->wins   += 1;
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

            // TODO :: Hier fehlen noch ein paar Regeln (siehe: http://www.dfb.de/?id=82917)
            // TODO :: Hier fehlt auch die Option noch andere Regeln hinzuzufügen
            // Direkter Vergleich
            // Torverhältnis
            // FIFA Koeffizient

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