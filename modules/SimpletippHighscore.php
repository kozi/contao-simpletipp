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
 * Class SimpletippHighscore
 *
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */
 

// Idee: HallOfFame

class SimpletippHighscore extends SimpletippModule {
    private $participants  = null;
    private $matches       = null;
	private $filter        = null;

	protected $strTemplate = 'simpletipp_highscore_default';

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template            = new BackendTemplate('be_wildcard');
			$this->Template->wildcard  = '### SimpletippHighscore ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}
		
		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}
	
	protected function compile() {

        // PointFactor, Participants, Groups,
        $this->participants = $this->getGroupMember($this->simpletipp->participant_group);

        // Filter
        $this->show = (Input::get('show')) ? Input::get('show') : 'all';
        $this->generateFilter();
        $this->Template->filter = $this->filter;


        if ($this->show === 'bestof') {
            $this->Template->tableClass  = 'highscore_bestof';
            $this->Template->table = $this->bestOfTable();
            return;
        }

        $matchgroupName = null;
        if ($this->show === 'current') {
            $this->Template->tableClass  = 'highscore_current';

            // get current matchgroup
            $result = $this->Database->prepare("SELECT groupName FROM tl_simpletipp_match
               WHERE leagueID = ? AND result != '' ORDER BY deadline")->limit(1)
                ->execute($this->simpletipp->leagueID);
            if ($result->numRows == 0) {
                $result = $this->Database->prepare("SELECT groupName FROM tl_simpletipp_match
                   WHERE leagueID = ? ORDER BY deadline")->limit(1)
                   ->execute($this->simpletipp->leagueID);
            }

            if ($result->numRows == 1) {
                $matchgroupName = $result->groupName;
            }
        }
        elseif($this->show !== 'all') {
            $this->Template->tableClass  = 'highscore_complete';
            // show is matchgroupName
            $matchgroupName = $this->show;
        }


        $this->matches = Simpletipp::getMatches($this->simpletipp->leagueID, $matchgroupName);

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
            ." FROM tl_simpletipp_tipp AS tblTipp, tl_member"
            ." WHERE tblTipp.member_id = tl_member.id"
            ." AND tblTipp.match_id in (".implode(',', $this->matches).")"
            ." GROUP BY tl_member.id"
            ." ORDER BY points DESC, sum_perfect DESC, sum_difference DESC");
		
		$table = array();

		while($result->next()) {
            $table[$result->member_id] = $this->getRow($result->row());
		}

        // Jetzt noch die member, die noch nichts getippt haben hinzufügen
		$result = $this->Database->execute("SELECT *, tl_member.id AS member_id FROM tl_member"
			." WHERE tl_member.id in (".implode(',', $this->participants).")");
		while($result->next()) {
			if (!array_key_exists($result->member_id, $table)) {
				$table[$result->member_id] = $this->getRow($result->row());
			}
		}

        $this->Template->filter      = $this->filter;
        $this->Template->table       = $table;

	}
	
	private function generateFilter() {
		$this->filter = new stdClass;

		$this->filter->options   = array(
            array(
                'title'    => $GLOBALS['TL_LANG']['simpletipp']['highscore_current'][0],
                'desc'     => $GLOBALS['TL_LANG']['simpletipp']['highscore_current'][1],
                'href'     => $this->addToUrl('show=current'),
                'cssClass' => ($this->show == 'current') ? ' class="current active"': ' class="current"'
            ),
            array(
                'title'    => $GLOBALS['TL_LANG']['simpletipp']['highscore_all'][0],
                'desc'     => $GLOBALS['TL_LANG']['simpletipp']['highscore_all'][1],
                'href'     => $this->addToUrl('show='),
                'cssClass' => (!$this->show) ? ' class="all active"': ' class="all"'
            ),
            array(
                'title'    => $GLOBALS['TL_LANG']['simpletipp']['highscore_bestof'][0],
                'desc'     => $GLOBALS['TL_LANG']['simpletipp']['highscore_bestof'][1],
                'href'     => $this->addToUrl('show=bestof'),
                'cssClass' => ($this->show == 'bestof') ? ' class="bestof active"': ' class="bestof"'
            )
        );
        $this->filter->group_options = array();
		foreach($this->simpletippGroups as $mg) {
            $this->filter->group_options[] = array(
                    'title'    => $mg->short,
					'desc'     => $mg->title,
					'href'     => $this->addToUrl('show='.$mg->title),
					'cssClass' => ($this->show == $mg->title) ? ' class="matchgroup active"': ' class="matchgroup"');
		}
	}

	private function getRow($memberRow, $params = '') {
        $row           = (Object) $memberRow;
        $row->avatar   = ($row->avatar != '') ? $row->avatar : $GLOBALS['TL_CONFIG']['uploadPath'].'/avatars/default128.png';
        $row->cssClass = (($this->i++ % 2 === 0 ) ? 'odd':'even') . ' pos'.$this->i;

        $pageModel = PageModel::findByPk($this->simpletipp_matches_page);
        if ($pageModel !== null) {
            $row->memberLink = self::generateFrontendUrl($pageModel->row(), '/user/'.$row->username.$params);
        }
		return $row;
	}

    private function bestOfTable() {

        $bestOf = array();

        // Alle bisher gespielten Gruppen holen
        $mgResult = $this->Database->prepare("SELECT DISTINCT groupName FROM tl_simpletipp_match WHERE
            deadline < ? ORDER BY LENGTH(groupName) ASC, groupName ASC")->execute($this->now);

        while($mgResult->next()) {
            $matchgroupName = $mgResult->groupName;
            $matches        = Simpletipp::getMatches($this->simpletipp->leagueID, $matchgroupName);

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
                ." FROM tl_simpletipp_tipp AS tblTipp, tl_member"
                ." WHERE tblTipp.member_id = tl_member.id"
                ." AND tblTipp.match_id in (".implode(',', $matches).")"
                ." GROUP BY tl_member.id"
                ." ORDER BY points DESC, sum_perfect DESC, sum_difference DESC");

            while($result->next()) {
                $currentRow = $bestOf[$result->member_id];
                if ($currentRow == null || (intval($currentRow->points) < intval($result->points))) {
                    $newRow                     = $this->getRow($result->row());
                    $newRow->groupName          = $matchgroupName;
                    $bestOf[$result->member_id] = $newRow;
                }
            }
        }
        // TODO sortieren

        // TODO Ergebnis cachen?!?
        return $bestOf;
	}
	
} // END class SimpletippHighscore

