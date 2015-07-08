<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2015 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2015 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Modules;


use \Simpletipp\SimpletippModule;

/**
 * Class SimpletippModulePokal
 *
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */


class SimpletippModulePokal extends SimpletippModule {
    protected $strTemplate = 'simpletipp_pokal_default';
    private $groups        = array();

	public function generate() {


        if (TL_MODE == 'BE') {
            $this->Template            = new \BackendTemplate('be_wildcard');
            $this->Template->wildcard  = '### SimpletippPokal ###';
            $this->Template->wildcard .= '<br/>'.$this->headline;
            return $this->Template->parse();
        }

        $this->import('\Simpletipp\SimpletippPokal', 'SimpletippPokal');
        $this->strTemplate  = $this->simpletipp_template;
        $this->groups       = $this->SimpletippPokal->getGroups($this->simpletipp);

        return parent::generate();
	}

	protected function compile() {

        foreach($this->groups as &$group) {
            $group->highscores = $this->getGroupHighscores($group);
        }
        $this->Template->groups       = $this->groups;
    }

    public function getGroupHighscores($group) {
        if ($group->pairings === null) {
            return null;
        }
        $highscores = array();

        if ($group->pairings === false) {
            return $highscores;
        }

        foreach ($group->pairings as $memberArr) {
            $highscores[] = $this->getHighscore($group->matchgroups, $memberArr);
        }
        return $highscores;
    }

} // END class SimpletippModulePokal

