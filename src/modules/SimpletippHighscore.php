<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp;

/**
 * Class SimpletippHighscore
 *
 * @copyright  Martin Kozianka 2011-2014
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */
 

// Idee: HallOfFame

class SimpletippHighscore extends SimpletippModule {
    private $filter        = null;

	protected $strTemplate = 'simpletipp_highscore_default';

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template            = new \BackendTemplate('be_wildcard');
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
        $this->show = (\Input::get('show') !== null) ? urldecode(\Input::get('show')) : 'all';

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
               WHERE leagueID = ? AND result != '' ORDER BY deadline DESC")->limit(1)
                ->execute($this->simpletipp->leagueID);

            if ($result->numRows == 0) {
                $result = $this->Database->prepare("SELECT groupName FROM tl_simpletipp_match
                   WHERE leagueID = ? ORDER BY deadline ASC")->limit(1)
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
                'cssClass' => ($this->show == 'current') ? ' class="current active"': ' class="current"',
                'selected' => ($this->show == 'current') ? ' selected="selected"':''
            ),
            array(
                'title'    => $GLOBALS['TL_LANG']['simpletipp']['highscore_all'][0],
                'desc'     => $GLOBALS['TL_LANG']['simpletipp']['highscore_all'][1],
                'href'     => $this->addToUrl('show='),
                'cssClass' => (!$this->show || $this->show == 'all') ? ' class="all active"': ' class="all"',
                'selected' => (!$this->show || $this->show == 'all') ? ' selected="selected"':''
            ),
            array(
                'title'    => $GLOBALS['TL_LANG']['simpletipp']['highscore_bestof'][0],
                'desc'     => $GLOBALS['TL_LANG']['simpletipp']['highscore_bestof'][1],
                'href'     => $this->addToUrl('show=bestof'),
                'cssClass' => ($this->show == 'bestof') ? ' class="bestof active"': ' class="bestof"',
                'selected' => ($this->show == 'bestof') ? ' selected="selected"':''
            )
        );
        $group_options = array();
		foreach($this->simpletippGroups as $mg) {
            $group_options[] = array(
                    'title'    => $mg->short,
					'desc'     => $mg->title,
					'href'     => $this->addToUrl('show='.$mg->title),
					'cssClass' => ($this->show == $mg->title) ? ' class="matchgroup active"': ' class="matchgroup"',
                    'selected' => ($this->show == $mg->title) ? ' selected="selected"':'');
		}

        $tmplStr = ($this->isMobile) ? 'simpletipp_filter_mobile' : 'simpletipp_filter';
        $tmpl                 = new \FrontendTemplate($tmplStr);
        $tmpl->special_filter = $special_options;
        $tmpl->group_filter   = $group_options;

        return $tmpl->parse();
	}

    private function bestOfTable() {
        $bestOf = $this->cachedResult(static::$cache_key_bestof);
        if ($bestOf != null) {
            return $bestOf;
        }
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
        // Sortieren
        usort($bestOf, function($a, $b) {
            if ($a->points > $b->points) return -1;
            if ($a->points < $b->points) return 1;

            if ($a->sum_perfect > $b->sum_perfect) return -1;
            if ($a->sum_perfect < $b->sum_perfect) return 1;

            if ($a->sum_difference > $b->sum_difference) return -1;
            if ($a->sum_difference < $b->sum_difference) return 1;

            return 0;
        });

        // CSS Klassen setzen
        $i = 1;
        foreach($bestOf as &$row) {
            $row->cssClass = 'pos'.$i++.' '.(($i %2 == 0) ? ' odd' : ' even');
        }

        // Ergebnis cachen
        $this->cachedResult(static::$cache_key_bestof, $bestOf);


        return $bestOf;
	}
	
} // END class SimpletippHighscore
