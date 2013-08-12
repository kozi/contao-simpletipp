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
        global $objPage;

        // Filter
        $this->show = (Input::get('show')) ? Input::get('show') : 'all';

        $this->Template->filter   = $this->generateFilter();
        $this->Template->isMobile = $objPage->isMobile;

        if ($this->show === 'bestof') {
            $this->Template->avatarActive = $this->avatarActive;
            $this->Template->tableClass   = 'highscore_bestof';
            $this->Template->table        = $this->bestOfTable();
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
            $this->Template->tableClass  = 'highscore_matchgroup';
            // show is matchgroupName
            $matchgroupName = $this->show;
        }
        else {
            $this->Template->tableClass  = 'highscore_complete';
        }

		$table  = $this->getHighscore($matchgroupName);

        $this->Template->avatarActive = $this->avatarActive;
        $this->Template->table        = $table;

	}
	
	private function generateFilter() {


        $special_options   = array(
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
                'cssClass' => (!$this->show || $this->show == 'all') ? ' class="all active"': ' class="all"'
            ),
            array(
                'title'    => $GLOBALS['TL_LANG']['simpletipp']['highscore_bestof'][0],
                'desc'     => $GLOBALS['TL_LANG']['simpletipp']['highscore_bestof'][1],
                'href'     => $this->addToUrl('show=bestof'),
                'cssClass' => ($this->show == 'bestof') ? ' class="bestof active"': ' class="bestof"'
            )
        );
        $group_options = array();
		foreach($this->simpletippGroups as $mg) {
            $group_options[] = array(
                    'title'    => $mg->short,
					'desc'     => $mg->title,
					'href'     => $this->addToUrl('show='.$mg->title),
					'cssClass' => ($this->show == $mg->title) ? ' class="matchgroup active"': ' class="matchgroup"');
		}

        $tmplStr = ($this->isMobile) ? 'simpletipp_filter_mobile' : 'simpletipp_filter';
        $tmpl                 = new FrontendTemplate($tmplStr);
        $tmpl->special_filter = $special_options;
        $tmpl->group_filter   = $group_options;

        return $tmpl->parse();
	}

    private function bestOfTable() {

        $bestOf = array();

        // Alle bisher gespielten Gruppen holen
        $mgResult = $this->Database->prepare("SELECT DISTINCT groupName FROM tl_simpletipp_match WHERE
            deadline < ? ORDER BY LENGTH(groupName) ASC, groupName ASC")->execute($this->now);

        while($mgResult->next()) {
            $matchgroupName = $mgResult->groupName;

            $matchGroupTable = $this->getHighscore($matchgroupName);

            foreach($matchGroupTable as $row) {
                $currentRow = $bestOf[$row->member_id];
                if ($currentRow == null || (intval($currentRow->points) < intval($row->points))) {
                    $newRow                     = $row;
                    $newRow->groupName          = $matchgroupName;
                    $bestOf[$row->member_id]    = $newRow;
                }
            }
        }

        // TODO sortieren
        // TODO Ergebnis cachen?!?
        return $bestOf;
	}
	
} // END class SimpletippHighscore

