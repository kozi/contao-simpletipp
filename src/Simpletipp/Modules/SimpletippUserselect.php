<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2016 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2016 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Modules;

use \Simpletipp\SimpletippModule;

/**
 * Class SimpletippUserselect
 *
 * @copyright  Martin Kozianka 2014-2016
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippUserselect extends SimpletippModule
{
	protected $strTemplate = 'simpletipp_userselect';
    private $participants  = null;

	public function generate()
    {
		if (TL_MODE == 'BE')
        {
			$this->Template = new \BackendTemplate('be_wildcard');
			$this->Template->wildcard  = '### SimpletippUserselect ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}
		return parent::generate();
	}
	
	protected function compile()
    {
        $participants = [];

        $objMembers = $this->simpletipp->getGroupMember();
        if ($objMembers != null)
        {
            foreach ($objMembers as $objMember)
            {
                $objMember->link              = $this->addToUrl('user='.$objMember->username);
                $participants[$objMember->id] = $objMember;
            }
        }

        $this->Template->action             = $this->addToUrl('user=');
        $this->Template->participants       = $participants;
        $this->Template->simpletippUserId   = $this->simpletippUserId;
        $this->Template->simpletipp         = $this->simpletipp;
        $this->Template->now                = $this->now;
        $this->Template->resetOption        = (Object) [
                'value' => $this->User->username,
                'label' => 'Eigene Daten anzeigen ('.$this->User->username.')'
        ];

    }

} // END class SimpletippUserselect


