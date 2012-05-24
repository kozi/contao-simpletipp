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
 * Class Simpletipp
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */
 
abstract class Simpletipp extends Module {
	protected $summary;
	protected $factorPerfect;
	protected $factorDifference;
	protected $factorTendency;
	protected $avatarSql;
	
	protected function compile() { 
		
		$this->loadLanguageFile('tl_simpletipp');
		
		$factor = explode(',' , $this->simpletipp_factor);
		$this->factorPerfect    = $factor[0];
		$this->factorDifference = $factor[1];
		$this->factorTendency   = $factor[2];
		
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

} // END class Simpletipp
