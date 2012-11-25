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
 * Class Simpletipp
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */
 
abstract class Simpletipp extends Module {
	protected $group;
	protected $now;
	protected $summary;
	protected $pointFactors;
	protected $factorDifference;
	protected $factorTendency;
	protected $avatarSql;

	protected function initSimpletipp() {
		
		$this->loadLanguageFile('tl_simpletipp');
		
		$this->now = time();
		
		$factor = explode(',' , $this->simpletipp_factor);
		$this->pointFactors = new stdClass;
		$this->pointFactors->perfect    = $factor[0];
		$this->pointFactors->difference = $factor[1];
		$this->pointFactors->tendency   = $factor[2];
			
		$this->summary = (Object) array('points' => 0, 'perfect'  => 0,
				'difference' => 0, 'tendency' => 0);
		
		$this->Template->imgtag_soccerball = $this->generateImage($this->getImage('system/modules/simpletipp/html/soccer.png', 0, 0));
		
		if (in_array('avatar', $this->Config->getActiveModules())) {
			$this->Template->showAvatar = true;
			$this->avatarSql            = ' tl_member.avatar AS avatar,';
		}
		else {
			$this->Template->showAvatar = false;
			$this->avatarSql            = '';
		}
		
		if ($this->simpletipp_group) {
			// init group
			$result = $this->Database
			->prepare('SELECT * FROM tl_simpletipp WHERE id = ? AND published = ?')
			->execute($this->simpletipp_group, 1);
			if ($result->numRows > 0) {
				$this->group           = (Object) $result->row();
				$this->group->matches  = unserialize($this->group->matches);
		
				$result = $this->Database->execute("SELECT DISTINCT matchgroup"
						." FROM tl_simpletipp_matches WHERE id IN (".implode(',', $this->group->matches).")"
						." ORDER BY matchgroup");
		
				$this->group->matchgroups = array();
				while($result->next()) {

					// TODO better shortener
					$mg = explode('. ', $result->matchgroup);
					
					$this->group->matchgroups[] = (Object) array(
							'title' => $result->matchgroup,
							'short' => $mg[0]);
				}
			}
		}
	}
	
	protected function updateSummary($points, $perfect, $difference, $tendency) {
		$this->summary->points     += $points;
		$this->summary->perfect    += $perfect;
		$this->summary->difference += $difference;
		$this->summary->tendency   += $tendency;
	}

	protected function getPointsClass($p, $d, $t) {
		if ($p == 1) return "perfect";
		if ($d == 1) return "difference";
		if ($t == 1) return "tendency";
		return " wrong";
	}

	protected function frontendUrlById($id, $strParams = null) {
		$link = null;
		
		$objPage = $this->Database
			->prepare("SELECT id, alias FROM tl_page WHERE id = ?")
			->limit(1)->execute($id);

		if ($objPage->numRows) {
			$link = $this->generateFrontendUrl($objPage->row(), $strParams);
		}
				
		return $link;
	}
	
	
	protected function getSimpletippMessages() {
		if (!is_array($_SESSION['TL_SIMPLETIPP_MESSAGE'])) {
			$_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
		}
		$ret = "<div class=\"simpletipp_messages\">\n";
		foreach($_SESSION['TL_SIMPLETIPP_MESSAGE'] AS $message) {
			$ret .= sprintf("	<div class=\"message\">%s</div>\n", $message);
		}

		// Reset
		$_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
		
		return $ret."\n</div>";
	}
	
	protected function addSimpletippMessage($message) {
		if (!is_array($_SESSION['TL_SIMPLETIPP_MESSAGE'])) {
			$_SESSION['TL_SIMPLETIPP_MESSAGE'] = array();
		}
		$_SESSION['TL_SIMPLETIPP_MESSAGE'][] = $message;
	}

	
	public static function getPoints($result, $tipp) {
		$points = new stdClass;
		$points->perfect    = 0;
		$points->difference = 0;
		$points->tendency   = 0;
		$points->wrong      = 0;
		
		if (strlen($result) === 0 || strlen($tipp) === 0) {
			return $points;
		}
		$tmp = explode(":", $result);
		$rh = intval($tmp[0], 10); $ra = intval($tmp[1], 10);
	
		$tmp = explode(":", $tipp);
		$th = intval($tmp[0], 10); $ta = intval($tmp[1], 10);
	
		if ($rh === $th && $ra === $ta) {
			$points->perfect = 1;
			return $points;
		}
	
		if (($rh-$ra) === ($th-$ta)) {
			$points->difference = 1;
			return $points;
		}
	
		if (($rh < $ra && $th < $ta) || ($rh > $ra && $th > $ta)) {
			$points->tendency = 1;
			return $points;
		}
	
		$points->wrong = 1;
		return $points;
	}
	
	public static function getPointsString($m, $pointFactors) {
		$points = self::getPoints($m->result, $m->tipp);

		$points->summe = ($points->perfect    * $pointFactors->perfect)
					   + ($points->difference * $pointFactors->difference)
					   + ($points->tendency   * $pointFactors->tendency);

		$points->str .= "Tipp: ".$m->tipp." - " // TODO translation
			.$points->summe
			.(($points->summe === 1) ? ' Punkt' : ' Punkte'); // TODO translation

		return $points;
	}	
} // END class Simpletipp
