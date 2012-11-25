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
 * Class SimpletippHighscore
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */
 

// HallOfFame

class SimpletippHighscore extends Simpletipp {
	private $participants  = null;
	private $filter        = null;
	private $i             = 0;
	
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
		$this->initSimpletipp();
		
		$result = $this->Database->prepare("SELECT * FROM tl_simpletipp"
			." WHERE id = ? AND published = ?")->execute($this->simpletipp_group, '1');

		if ($result->numRows == 0) {
			return;
		}

		$this->getParticipants();
		$this->getFilter();
		
		$result = $this->Database->execute("SELECT *, tl_member.id AS member_id,"
		.$this->avatarSql
		." SUM(tendency) AS sum_tendency,"
		." SUM(difference) AS sum_difference,"
		." SUM(perfect) AS sum_perfect,"
		." SUM(wrong) AS sum_wrong,"
		." SUM(perfect*".$this->pointFactors->perfect
				." + difference*".$this->pointFactors->difference
				." + tendency*".$this->pointFactors->tendency
				.") AS points"
		." FROM tl_simpletipp_tipps AS tipps, tl_member"
		." WHERE tipps.member_id = tl_member.id"
		." AND tipps.match_id in (".implode(',', $this->group->matches).")"
		." GROUP BY tl_member.id"
		." ORDER BY points DESC, sum_perfect DESC, sum_difference DESC");
		
		$table = array();

		while($result->next()) {
			$row = (Object) $result->row();
			$row->avatar = ($row->avatar != '') ? $row->avatar : $GLOBALS['TL_CONFIG']['uploadPath'].'/avatars/default128.png';
			$table[$row->member_id] = $this->getRow($row);
		}

		// Jetzt noch die member, die noch nichts getippt haben hinzufügen
		$result = $this->Database->execute("SELECT *, tl_member.id AS member_id FROM tl_member"
			." WHERE tl_member.id in (".implode(',', $this->participants).")");
		while($result->next()) {
			$row = (Object) $result->row();
			$row->avatar = ($row->avatar != '') ? $row->avatar : $GLOBALS['TL_CONFIG']['uploadPath'].'/avatars/default128.png';
			if (!array_key_exists($row->member_id, $table)) {
				$table[$row->member_id] = $this->getRow($row);
			}
		}

		$this->Template->summary    = $this->summary;
		$this->Template->filter     = $this->filter;
		$this->Template->table      = $table;
		 
	}
	
	private function getFilter() {
		$this->filter = new stdClass;
		$this->type   = null;
		
		$show = $this->Input->get('show');

		$this->filter->options = array();
		$this->filter->options[] = array('title' => $GLOBALS['TL_LANG']['simpletipp']['highscore_all'][0],
				'desc' => $GLOBALS['TL_LANG']['simpletipp']['highscore_all'][1],
				'href' => $this->addToUrl('show='),
				'cssClass' => (!$show) ? ' class="all active"': ' class="all"');
		
		$this->filter->options[] = array('title' => $GLOBALS['TL_LANG']['simpletipp']['highscore_bestof'][0],
				'desc' => $GLOBALS['TL_LANG']['simpletipp']['highscore_bestof'][1],
				'href' => $this->addToUrl('show=bestof'),
				'cssClass' => ($show == 'bestof') ? ' class="bestof active"': ' class="bestof"');
		
		$this->filter->options[] = array('title' => $GLOBALS['TL_LANG']['simpletipp']['highscore_current'][0],
				'desc' => $GLOBALS['TL_LANG']['simpletipp']['highscore_current'][1],
				'href' => $this->addToUrl('show=current'),
				'cssClass' => ($show == 'current') ? ' class="current active"': ' class="current"');

		foreach($this->group->matchgroups as $mg) {
			$this->filter->options[] = array('title' => $mg->short,
					'desc' => $mg->title,
					'href' => $this->addToUrl('show='.$mg->title),
					'cssClass' => ($show == $mg->title) ? ' class="matchgroup active"': ' class="matchgroup"');
		}
		
		
		
		
		if ('bestof' == $show) {
			$this->type     = 'bestof';
			return;
		} elseif ('current' == $show) {
			$this->type     = 'current';
		
			// set $show to current matchgroup
			$result = $this->Database->prepare("SELECT matchgroup FROM tl_simpletipp_matches"
					." WHERE id IN (".implode(',', $this->group->matches).")"
					." AND result != ''"
					." ORDER BY deadline")->limit(1)->execute();
			
			if ($result->numRows == 0) {
				$result = $this->Database->prepare("SELECT matchgroup FROM tl_simpletipp_matches"
						." WHERE id IN (".implode(',', $this->group->matches).")"
						." ORDER BY deadline")->limit(1)->execute();
			}

			if ($result->numRows == 1) {
				$show = $result->matchgroup;
			}
			
		
		}			

		if ($show) {
			$this->type = ($this->type == null) ? $show : $this->type;
			$result = $this->Database->prepare("SELECT id FROM tl_simpletipp_matches"
					." WHERE id IN (".implode(',', $this->group->matches).")"
					." AND matchgroup = ?")->execute($show);
				
			$this->group->matches = array();
			while($result->next()) {
				$this->group->matches[] = $result->id;
			}
		}

		
	}
	
	private function getParticipants() {
		$participants = array();
		$result = $this->Database->execute("SELECT id, groups FROM tl_member");

		while($result->next()) {
			$groups = unserialize($result->groups);
			if (is_array($groups) && in_array($this->group->participant_group, $groups)) {
				$participants[] = $result->id;
			}
		}
		$this->participants = $participants; 
	}
	
	private function getRow($r) {
		$r->memberLink = $this->frontendUrlById($this->simpletipp_matches_page,
				'/member/'.$r->username);
		
		$r->cssClass = (($this->i++ % 2 === 0 ) ? 'odd':'even') . ' pos'.$this->i;
			
		$this->updateSummary($r->points, $r->sum_perfect,
				$r->sum_difference, $r->sum_tendency);
		
		return $r;
	}
	
	
	private function getBestOf() {
	
	}
	
} // END class SimpletippHighscore

