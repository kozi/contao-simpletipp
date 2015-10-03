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

use Simpletipp\Models\SimpletippTippModel;
use Simpletipp\Simpletipp;
use Simpletipp\SimpletippModule;

/**
 * Class SimpletippNotTipped
 *
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippNotTipped extends SimpletippModule
{
	protected $strTemplate = 'simpletipp_nottipped_default';

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template = new \BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippNotTipped ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}
		
		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}

	protected function compile() {

		$match = Simpletipp::getNextMatch($this->simpletipp->leagueID);
        if ($match == null)
		{
            // no next match
            return;
        }

		$tippCount = SimpletippTippModel::countBy('match_id', $match->id);
		$arrUser   = $this->cache(static::$cache_key_notTipped.$tippCount);

		if ($arrUser === null)
		{
			$arrUser = [];
			$arr     = Simpletipp::getNotTippedUser($this->simpletipp->participant_group, $match->id);
			foreach($arr as $u)
			{
				$key           = $u['username'];
				$arrUser[$key] = $u['firstname'].' '.$u['lastname'];
			}
			$this->cache(static::$cache_key_notTipped.$tippCount, $arrUser);
		}

		$this->Template->match   = $match;
        $this->Template->user    = $this->User;
        $this->Template->userArr = $arrUser;
	}

} // END class SimpletippQuestions
