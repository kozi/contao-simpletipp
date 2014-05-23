<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


/**
 * Class SimpletippNotTipped
 *
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippNotTipped extends SimpletippModule {
	protected $strTemplate = 'simpletipp_nottipped_default';

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippNotTipped ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}
		
		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}

	protected function compile() {
        $userArr = array();

        $match = Simpletipp::getNextMatch($this->simpletipp->leagueID);
        if ($match == null) {
            // no next match
            return;
        }

        foreach(Simpletipp::getNotTippedUser($this->simpletipp->participant_group, $match->id) as $u) {
            $userArr[] =  $u['firstname'].' '.$u['lastname'];
        }

        $this->Template->match   = $match;
        $this->Template->userArr = $userArr;
	}

} // END class SimpletippQuestions


