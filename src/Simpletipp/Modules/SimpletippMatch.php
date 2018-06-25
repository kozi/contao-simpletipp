<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2016 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2016 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Modules;

use Simpletipp\SimpletippModule;
use Simpletipp\Models\SimpletippPoints;
use Simpletipp\Models\SimpletippMatchModel;

/**
 * Class SimpletippMatch
 *
 * @copyright  Martin Kozianka 2014-2016
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */
 
class SimpletippMatch extends SimpletippModule
{
    /**
     * @var SimpletippMatchModel
     */
	private $match;

	protected $strTemplate = 'simpletipp_match_default';

	public function generate()
    {
		if (TL_MODE == 'BE')
        {
			$this->Template = new \BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippMatch ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}
		$this->strTemplate = $this->simpletipp_template;
		return parent::generate();
	}
	
	protected function compile()
    {
        $matchAlias = \Input::get('match');

        if (is_numeric($matchAlias))
        {
            $this->match = SimpletippMatchModel::findByPk($matchAlias);
        }
        else
        {
            // get matchId from team short names
            $this->match = SimpletippMatchModel::findByTeamAttributeAliases($this->simpletipp, strtoupper($matchAlias), 'three');
        }

        if ($this->match == null)
        {
            $this->Template->match   = null;
            $this->Template->message = 'No match found.';
            return;
        }

        $this->isStarted = (time() > $this->match->deadline);

        // GoalData
        $this->match->refreshGoalData($this->simpletipp);

        $this->match->goalData = unserialize($this->match->goalData);

        $this->match->teamHome = $this->match->getRelated('team_h');
        $this->match->teamAway = $this->match->getRelated('team_a');


		$result = $this->Database->prepare(
				"SELECT *, tl_member.id AS memberId FROM tl_simpletipp_tipp, tl_simpletipp_match, tl_member"
				." WHERE match_id = ? AND match_id = tl_simpletipp_match.id"
				." AND member_id = tl_member.id ORDER BY tl_member.lastname")
				->execute($this->match->id);

        $objPage       = \PageModel::findByPk($this->simpletipp_matches_page);
        $pageRow       = ($objPage !== null) ? $objPage->row() : null;


        $count = (Object) [
            'home' => (Object) ['abs' => 0, 'percent' => 0],
            'draw' => (Object) ['abs' => 0, 'percent' => 0],
            'away' => (Object) ['abs' => 0, 'percent' => 0],
        ];
        $i     = 0;
        $tipps = [];
		while ($result->next()) {

            $tipp         = (Object) $result->row();

            $pointObj     = new SimpletippPoints($this->pointFactors, $tipp->perfect, $tipp->difference, $tipp->tendency);
			$tipp->points = $pointObj->points;

            $tipp->cssClass    = ($i++ % 2 === 0 ) ? 'odd':'even';
            $tipp->cssClass    .= ($result->numRows == $i) ? ' last':'';
            $tipp->pointsClass = $pointObj->getPointsClass();

            $tmp = array_map('intval', explode(':', $tipp->tipp));
            $count->home->abs = ($tmp[0] > $tmp[1])  ? ++$count->home->abs : $count->home->abs;
            $count->draw->abs = ($tmp[0] == $tmp[1]) ? ++$count->draw->abs : $count->draw->abs;
            $count->away->abs = ($tmp[0] < $tmp[1])  ? ++$count->away->abs : $count->away->abs;

            $this->updateSummary($pointObj);

            if ($pageRow != null)
            {
                $tipp->link = $this->generateFrontendUrl($pageRow,
                    '/group/'.$tipp->groupName.'/user/'.$tipp->username);
            }

			// Alle Tipps bis auf den eigenen vor Spielstart ausblenden
            if (!$this->isStarted && $tipp->member_id != $this->User->id)
            {
		$tipp->tipp = "?:?";
	    }
	    $tipps[]      = $tipp;
	}

        $summe = count($tipps);
        if ($summe > 0)
        {
            $count->home->percent = floor(($count->home->abs / $summe) * 10000) / 100;
            $count->draw->percent = floor(($count->draw->abs / $summe) * 10000) / 100;
            $count->away->percent = floor(($count->away->abs / $summe) * 10000) / 100;
        }
        
        $this->Template->match        = $this->match;
        $this->Template->isStarted    = $this->isStarted;
        $this->Template->count        = $count;
		$this->Template->tipps        = $tipps;
		$this->Template->summary      = $this->pointSummary;
	}


} // END class SimpletippMatch

