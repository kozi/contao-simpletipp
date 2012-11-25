<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012 <http://kozianka-online.de/>
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


/**
 * Class SimpletippUserselect
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */

class SimpletippUserselect extends Simpletipp {
	protected $strTemplate     = 'simpletipp_userselect';
	private $member_group      = null;

	public function generate() {

		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippUserselect ###';
	
			$this->Template->wildcard .= '<br/>'.$this->headline;
	
			return $this->Template->parse();
		}
		
		return parent::generate();
	}
	
	protected function compile() {
		global $objPage;
		$this->initSimpletipp();

		// memberId holen
		$this->getMemberId();
		
		$result = $this->Database
					->prepare('SELECT participant_group FROM tl_simpletipp WHERE id = ? AND published = ?')
					->execute($this->simpletipp_group, 1);

		if ($result->numRows == 0) {
			return;
		}
		
		$this->member_group = $result->participant_group;


		
		$result = $this->Database->execute(
				"SELECT id, username, firstname, lastname, groups FROM tl_member"
				." ORDER BY lastname ASC, firstname ASC");
		
		$members = array();
		while($result->next()) {
			$groups = unserialize($result->groups);
			if (in_array($this->member_group, $groups)) {
				$m = (Object) $result->row();
				$m->groups = $groups;
				$m->link   = $this->addToUrl('member='.$m->username);
				$members[$m->id] = $m;
			}
		}
		
		$this->Template->arr = $members;
		$this->Template->resetOption = (Object) array(
				'value' => $this->frontendUrlById($objPage->id),
				'label' =>' Zurücksetzen');
		
		$this->Template->isMobile     = $objPage->isMobile;
		$this->Template->action       = ampersand($this->Environment->request);
		$this->Template->selectedUser = $this->memberId;

		if ($this->Template->showAvatar) {
			$this->Template->avatar   = $this->getAvatar();
		}

	}
	
	private function getMemberId() {
		$username = $this->Input->get('member');

		if (FE_USER_LOGGED_IN) {
			$this->import('FrontendUser', 'User');
			if (!$username || $this->User->username == $username) {
				$this->memberId = $this->User->id;
				$this->isPersonal = true;

				// TODO check send email setting
				$this->sendEmail = true;
				
				return $this->memberId;
			}
		}
		
		$this->isPersonal = false;		
		if ($username) {
			// get id by username
			$result = $this->Database->prepare("SELECT id FROM tl_member WHERE username = ?")
				->limit(1)->execute($username);

			if ($result->numRows == 1) {
				$this->memberId = $result->id;
				return $this->memberId;
			}
			else {
				return null;
			}
		}
		return null;		
	}

	private function getAvatar() {
		$result = $this->Database->prepare("SELECT avatar FROM tl_member WHERE id = ?")
			->limit(1)->execute($this->memberId);
		if ($result->numRows == 1) {
			$a = ($result->avatar != '') ? $result->avatar : $GLOBALS['TL_CONFIG']['uploadPath'].'/avatars/default128.png';
			return $a;
		}
		return false;
	}
	
} // END class SimpletippUserselect


