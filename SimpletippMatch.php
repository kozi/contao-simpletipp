<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012 <http://kozianka-online.de/>
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
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
		$this->initSimpletipp();
		
		$this->matchId = intval($this->Input->get('match'));

		if (!$this->matchId) {
			return;
		}
		
		
		$result = $this->Database->prepare("SELECT * FROM tl_simpletipp_matches"
				." WHERE id = ?")->limit(1)->execute($this->matchId);
		if ($result->numRows) {
			$match = (Object) $result->row();
			$match->isStarted = (time() > $match->deadline);

			$teams = explode("-", $match->title_short);
			$match->team_h = standardize($teams[0]);
			$match->team_a = standardize($teams[1]);
				
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

			$tipp->points = $tipp->perfect * $this->pointFactors->perfect
					+ $tipp->difference * $this->pointFactors->difference
					+ $tipp->tendency   * $this->pointFactors->tendency;
			 
			
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
			
			$tipp->avatar = ($tipp->avatar != '') ? $tipp->avatar : $GLOBALS['TL_CONFIG']['uploadPath'].'/avatars/default128.png';
			
			$tipps[] = $tipp;
		}
		
		// Match
		$this->Template->match       = $match->title;
		
		$this->Template->team_h       = $match->team_h;
		$this->Template->team_a       = $match->team_a;
		
		$this->Template->result      = $match->result;
		$this->Template->competition = $match->competition;
		$this->Template->matchgroup  = $match->matchgroup;

		$this->Template->tipps   = $tipps;
		$this->Template->summary = $this->summary;
	}

} // END class SimpletippMatches


