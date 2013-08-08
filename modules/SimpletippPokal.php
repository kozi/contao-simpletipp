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
 * Class SimpletippPokal
 *
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */


class SimpletippPokal extends SimpletippModule {
	protected $strTemplate = 'simpletipp_pokal_default';

	public function generate() {
        if (TL_MODE == 'BE') {
            $this->Template            = new BackendTemplate('be_wildcard');
            $this->Template->wildcard  = '### SimpletippPokal ###';
            $this->Template->wildcard .= '<br/>'.$this->headline;
            return $this->Template->parse();
        }
        $this->loadLanguageFile('tl_simpletipp');
        $this->strTemplate = $this->simpletipp_template;

        return parent::generate();
	}

	protected function compile() {
        $groups       = array();
        $groupIdArray = array('pokal_group', 'pokal_16', 'pokal_8', 'pokal_4', 'pokal_2', 'pokal_finale');
        foreach ($groupIdArray as $id) {
            $groups[$id] = $this->getGroupObject($GLOBALS['TL_LANG']['tl_simpletipp'][$id][0], $id, $this->simpletipp->$id);
        }

        $this->Template->groups = $groups;
    }

    private function getGroupObject($name, $alias, $serializedArray) {
        $group = new stdClass();
        $group->name        = $name;
        $group->alias       = $alias;
        $group->matchgroups = deserialize($serializedArray);

        if (is_array($group->matchgroups) && count($group->matchgroups) > 0) {
            $group->first       = $group->matchgroups[0];
            $group->last        = $group->matchgroups[count($group->matchgroups)-1];
        }
        return $group;
    }
} // END class SimpletippPokal