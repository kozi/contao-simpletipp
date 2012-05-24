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
 * Class SimpletippHighscore
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */
 
class SimpletippHighscore extends Simpletipp {
	private $i = 0;
	private $groups; 
	protected $strTemplate = 'simpletipp_highscore_default';

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippHighscore ###';

			$this->Template->wildcard .= '<br/>'.$this->headline;

			return $this->Template->parse();
		}
		
		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}
	
	protected function compile() {
		parent::compile();

		$this->groups = array_map('intval', unserialize($this->simpletipp_groups));

		if (in_array(0, $this->groups)){
			// 0 means all groups
			$sql_where = "";
		}
		else {
			$sql_where = " AND id in (".implode(',', $this->groups).")";
		}
		
		$result = $this->Database->execute("SELECT matches, participants FROM tl_simpletipp"
					." WHERE published = '1'".$sql_where);
		
		$matches      = array();
		$participants = array();
		while($result->next()) {
			$matches = array_merge($matches, unserialize($result->matches));
			$participants = array_merge($participants, unserialize($result->participants));
		}
		$matches      = array_unique($matches);
		$participants = array_unique($participants);

		$result = $this->Database->execute("SELECT *, tl_member.id AS member_id,"
		.$this->avatarSql
		." SUM(tendency) AS sum_tendency,"
		." SUM(difference) AS sum_difference,"
		." SUM(perfect) AS sum_perfect,"
		." SUM(wrong) AS sum_wrong,"
		." SUM(perfect*".$this->factorPerfect
				." + difference*".$this->factorDifference
				." + tendency*".$this->factorTendency
				.") AS points"
		." FROM tl_simpletipp_tipps AS tipps, tl_member"
		." WHERE tipps.member_id = tl_member.id"
		." AND tipps.match_id in (".implode(',', $matches).")"
		." GROUP BY tl_member.id"
		." ORDER BY points DESC, sum_perfect DESC, sum_difference DESC");
		
		$table = array();

		while($result->next()) {
			$row = (Object) $result->row();
			$table[$row->member_id] = $this->getRow($row);
		}

		// Jetzt noch die member, die noch nichts getippt haben hinzufügen
		$result = $this->Database->execute("SELECT *, tl_member.id AS member_id"
			." FROM tl_member"
			." WHERE tl_member.id in (".implode(',', $participants).")");
		while($result->next()) {
			$row = (Object) $result->row();
			if (!array_key_exists($row->member_id, $table)) {
				$table[$row->member_id] = $this->getRow($row);
			}
		}

		$this->Template->summary    = $this->summary;
		$this->Template->table      = $table;
		 
	}

	
	private function getRow($r) {
		$r->memberLink = $this->frontendUrlById($this->simpletipp_matches_page,
				'/member/'.$r->username);
		
		$r->cssClass = (($this->i++ % 2 === 0 ) ? 'odd':'even') . ' pos'.$this->i;
			
		$this->updateSummary($r->points, $r->sum_perfect,
				$r->sum_difference, $r->sum_tendency);
		
		return $r;
	}
} // END class SimpletippHighscore

