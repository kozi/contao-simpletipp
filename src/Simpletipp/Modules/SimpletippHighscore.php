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

namespace Simpletipp\Modules;


use \Simpletipp\Simpletipp;
use \Simpletipp\SimpletippModule;

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
            'current' => array(
                'href'     => $this->addToUrl('show=current'),
                'active'   => ($this->show == 'current')
            ),
            'all' => array(
                'href'     => $this->addToUrl('show='),
                'active'   => (!$this->show || $this->show == 'all')
            ),
            'bestof' => array(
                'href'     => $this->addToUrl('show=bestof'),
                'active'   => ($this->show == 'bestof')
            )
        );

        $i     = 0;
        $count = count($special_options);
        foreach($special_options as $key => &$entry) {
            $entry['title'] = $GLOBALS['TL_LANG']['simpletipp']['highscore_'.$key][0];
            $entry['desc']  = $GLOBALS['TL_LANG']['simpletipp']['highscore_'.$key][1];

            $cssClasses = $key.' count'.$count.' pos'.$i;
            $cssClasses .= ($i == 0) ? ' first' : '';
            $cssClasses .= ($count === $i+1) ? ' last' : '';
            $entry['selected'] = '';

            if ($entry['active']) {
                $cssClasses       .= ' active';
                $entry['selected'] = ' selected="selected"';
            }
            $entry['cssClass'] = ' class="'.$cssClasses.'"';
            $i++;
        }

        $group_options = array();
        $i           = 0;
        $count       = count($this->simpletippGroups);
        $prefix      = ' class="matchgroup count'.$count;
		foreach($this->simpletippGroups as $mg) {
            $act       = ($this->show == $mg->title);
            $cssClass  = $prefix.(($i == 0) ? ' first' : '');
            $cssClass .= ($i+1 == $count) ? ' last %s"' : ' %s"';

            $group_options[] = array(
                    'title'    => $mg->short,
					'desc'     => $mg->title,
					'href'     => $this->addToUrl('show='.$mg->title),
					'cssClass' => ($act) ? sprintf($cssClass, 'pos'.$i++.' active') : sprintf($cssClass, 'pos'.$i++),
                    'selected' => ($act) ? ' selected="selected"':''
            );
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

