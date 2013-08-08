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

        $result = $this->Database->prepare("SELECT * FROM tl_simpletipp_match
            WHERE leagueID = ? AND deadline > ?
            ORDER BY deadline ASC")->limit(1)->execute($this->simpletipp->leagueID, time());

        if ($result->numRows == 0) {
            // no next match
            return;
        }
        $match    = (Object) $result->row();
        $result   = $this->Database->prepare("SELECT tblu.firstname, tblu.lastname
             FROM tl_member as tblu
             LEFT JOIN tl_simpletipp_tipp AS tblt
             ON ( tblu.id = tblt.member_id AND tblt.match_id = ?)
             WHERE tblt.id IS NULL
             ORDER BY tblu.lastname")->execute($match->id);

        while($result->next()) {
            $userArr[] =  $result->firstname.' '.$result->lastname;
        }

        $this->Template->match   = $match;
        $this->Template->userArr = $userArr;
	}

} // END class SimpletippQuestions


