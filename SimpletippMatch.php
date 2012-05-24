<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright	Copyright Martin Kozianka 2011-2012
 * @author		Martin Kozianka
 * @package		simpletipp
 */

/**
 * Class SimpletippMatch
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */
 
class SimpletippMatch extends Simpletipp {
	private $matchId;
	protected $strTemplate = 'simpletipp_match_default';

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippMatch ###';
	
			$this->Template->wildcard .= '<br/>'.$this->headline;
	
			return $this->Template->parse();
		}
		
		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}
	
	protected function compile() {
		parent::compile();
		
		$this->matchId = intval($this->Input->get('match'));

		if (!$this->matchId) {
			return;
		}
		
		
		$result = $this->Database->prepare("SELECT * FROM tl_simpletipp_matches"
				." WHERE id = ?")->limit(1)->execute($this->matchId);
		if ($result->numRows) {
			$match = (Object) $result->row();
			$match->isStarted = (time() > $match->deadline);
		}	

		$result = $this->Database->prepare(
				"SELECT *, tl_member.id AS memberId FROM tl_simpletipp_tipps, tl_simpletipp_matches, tl_member"
				." WHERE match_id = ? AND match_id = tl_simpletipp_matches.id"
				." AND member_id = tl_member.id")
				->execute($this->matchId);

		$tipps = array();
		$sum = (Object) array('points' => 0, 'perfect' => 0,
			'difference' => 0, 'tendency' => 0);
		$i = 0;
		while ($result->next()) {
			$tipp = (Object) $result->row(); 

			$tipp->points = $tipp->perfect * $this->factorPerfect
					+ $tipp->difference * $this->factorDifference
					+ $tipp->tendency   * $this->factorTendency;
			 
			
			$tipp->cssClass = ($i++ % 2 === 0 ) ? 'odd':'even';
			
			$tipp->pointsClass = $this->getPointsClass(
					$tipp->perfect, $tipp->difference, $tipp->tendency);
				
			$this->updateSummary($tipp->points, $tipp->perfect,
					$tipp->difference, $tipp->tendency);

			$tipp->link = $this->frontendUrlById($this->simpletipp_matches_page,
					"/member/".$tipp->username);
			
			if (!$match->isStarted) {
				$tipp->tipp = "?:?";
			}
				
			$tipps[] = $tipp;
		}

		// Match
		$this->Template->match       = $match->title;
		$this->Template->result      = $match->result;
		$this->Template->competition = $match->competition;
		$this->Template->matchgroup  = $match->matchgroup;

		$this->Template->tipps   = $tipps;
		$this->Template->summary = $this->summary;
	}

} // END class SimpletippMatches


