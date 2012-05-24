<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright	Copyright Martin Kozianka 2011-2012
 * @author		Martin Kozianka
 * @package		simpletipp
 */

/**
 * Class SimpletippMatches
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */
 
class SimpletippMatches extends Simpletipp {
	
	protected $strTemplate = 'simpletipp_matches_default';
	private $formId        = 'tl_simpletipp';
	private $isPersonal    = false;
	private $sendEmail     = true;
	private $memberId      = null;

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippMatches ###';
	
			$this->Template->wildcard .= '<br/>'.$this->headline;
	
			return $this->Template->parse();
		}
		
		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}
	
	protected function compile() {
		parent::compile();
		
		// memberId holen
		$this->getMemberId();
		
		// Die übergebenen Tipps eintragen
		if ($this->Input->post('FORM_SUBMIT') === $this->formId) {
			$this->processTipps();
			$this->redirect($this->addToUrl(''));
		}

		$result = $this->Database->execute("SELECT * FROM tl_simpletipp WHERE published = '1'");
		$competitions = array();
		while ($result->next()) {
			$participants = unserialize($result->participants);
			if (in_array($this->memberId, $participants) || $this->memberId == null) {

				$competition = (Object) $result->row();
				
				if ($this->memberId != null) {
					$competition->matches      = $this->getMatches(unserialize($competition->matches), $matchgroup_filter);
				}
				else {
					$competition->matches = false;
				}

				$participants              = $this->getParticipants(unserialize($competition->participants));
				$competition->userselect   = $this->getUserSelect($participants, $this->memberId);
				$competitions[]            = $competition;
			}
		}

		$this->Template->formId       = $this->formId;
		$this->Template->action       = ampersand($this->Environment->request);
		$this->Template->memberId     = $this->memberId;
		$this->Template->competitions = $competitions;
		$this->Template->summary      = $this->summary;
		$this->Template->messages     = $this->getSimpletippMessages();
		$this->Template->isPersonal   = $this->isPersonal;
		
		if ($this->Template->showAvatar) {
			$this->Template->avatar   = $this->getAvatar();
		}

	}

	private function getParticipants($mIds) {
		$result = $this->Database->execute(
				"SELECT id, username, firstname, lastname"
				." FROM tl_member WHERE id in (".implode(',', $mIds).")"
				." ORDER BY lastname ASC, firstname ASC");
		
		$members = array();
		while($result->next()) {
			$m = (Object) $result->row();
			$m->link = $this->addToUrl('member='.$m->username);
			$members[] = $m;
		}
		
		return $members;
	}

	private function getMatches($matchIds, $matchgroup = false) {
		
		$matches = array();
		if (!$matchIds) {
			return $matches;
		};

		$sql = "SELECT "
		."matches.id AS matchId, "
		."matches.matchgroup AS matchgroup,"
		."matches.deadline AS deadline,"
		."matches.title AS title,"
		."matches.result AS result,"
		."tipps.perfect AS perfect,"
		."tipps.difference AS difference,"
		."tipps.tendency AS tendency,"
		."tipps.tipp AS tipp"
		
		." FROM tl_simpletipp_matches AS matches "
		." LEFT JOIN tl_simpletipp_tipps AS tipps ON (matches.id = tipps.match_id AND tipps.member_id = ?)"
		." WHERE matches.id in (".implode(',', $matchIds).")"
		." AND (tipps.member_id = ? OR tipps.member_id IS NULL)";

		if ($matchgroup) {
			$result = $this->Database->prepare($sql." AND matches.matchgroup = ? ORDER BY deadline")
				->execute($this->memberId, $this->memberId, $matchgroup);
		} else {
			$result = $this->Database->prepare($sql." ORDER BY deadline")
				->execute($this->memberId, $this->memberId);
		}

		$matches = array();
		$i = 0;
		
		while ($result->next()) {
			$match = (Object) $result->row();
			$match->matchLink = $this->frontendUrlById($this->simpletipp_match_page,
					'/match/'.$match->matchId);
			
			$match->isStarted      = (time() > $result->deadline);
			$match->deadline       = date($GLOBALS['TL_CONFIG']['datimFormat'], $match->deadline);
			$match->points         = $match->perfect    * $this->factorPerfect 
									  + $match->difference * $this->factorDifference
									  + $match->tendency   * $this->factorTendency;
			
			$match->cssClass = ($i++ % 2 === 0 ) ? 'odd':'even';
			$match->pointsClass = '';
			if (strlen($match->result) > 0) {
				$match->pointsClass = $this->getPointsClass(
					$match->perfect, $match->difference, $match->tendency);
			
			}
			$matches[] = $match;
			
			$this->updateSummary($match->points, $match->perfect,
					$match->difference, $match->tendency);
		}
		
		return $matches;
	}

	private function processTipps() {
		if (!FE_USER_LOGGED_IN && false) {
			return false;
		}
		$ids   = $this->Input->post('match_ids');
		$tipps = $this->Input->post('tipps');
		
		
		$to_db = array();
		
		if (is_array($ids) && is_array($tipps)
			&& count($ids) === count($tipps) && count($ids) > 0) {
		
			for ($i=0;$i < count($ids);$i++) {
				$id    = intval($ids[$i]);
				$tipp  = $this->cleanupTipp($tipps[$i]);

				if (preg_match('/^(\d{1,4}):(\d{1,4})$/', $tipp)) {
					$to_db[$id] = $tipp;
				}
			}

			$check  = "SELECT id FROM tl_simpletipp_tipps WHERE member_id = ? AND match_id = ?";
			$update = "UPDATE tl_simpletipp_tipps SET tipp = ? WHERE id = ?";
			$insert = "INSERT INTO tl_simpletipp_tipps(tstamp, member_id, match_id, tipp) VALUES(?, ?, ?, ?)";
			$mId = 1; // $this->User->id

			foreach($to_db as $id=>$tipp) {
				$result = $this->Database->prepare($check)->execute($mId, $id);

				if ($result->numRows > 0) {
					$result = $this->Database->prepare($update)
						->execute($tipp, $result->id);
				}
				else {
					$result = $this->Database->prepare($insert)
						->execute(time(), $mId, $id, $tipp);
				}
			}
		}

		if ($this->sendEmail) {
			$this->sendTippEmail($to_db);
			$message = sprintf($GLOBALS['TL_LANG']['simpletipp']['message_inserted_email'], $this->User->email);
		}
		else {
			$message = $GLOBALS['TL_LANG']['simpletipp']['message_inserted'];
		} 
		$this->addSimpletippMessage($message);
		
		return true;
	}
	
	private function cleanupTipp($tipp){
		$t  = str_replace(
				array('/', ';', '.', ',', '-', ' '),
				array(':', ':', ':', ':', ':', ':'),
				trim($tipp)
		);
		
		if (strlen($t) < 3) {
			return '';
		}
		
		$tmp = explode(":", $t);
		
		if(strlen($tmp[0]) < 1 && strlen($tmp[1]) < 1) {
			return '';
		}
		  
		$h = intval($tmp[0], 10);
		$a = intval($tmp[1], 10);
		return $h.':'.$a;
	}
	private function sendTippEmail() {

		$email = new Email();
		$email->subject  = 'TODO - Email subject';
		$email->text     = 'TODO - Email text';
		$email->sendTo($this->User->email);
	}
	
	private function getMemberId() {
		$username = $this->Input->get('member');

		if (FE_USER_LOGGED_IN) {
			$this->import('FrontendUser', 'User');
			if (!$username || $this->User->username == $username) {
				$this->memberId = $this->User->id;
				$this->isPersonal = true;
				
				
				// TODO check send email setting
				$this->sendEmail  = true;
				
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
	
	private function getUserSelect($arr, $selectedUser) {
		global $objPage;
		
		$tmpl = new FrontendTemplate('simpletipp_userselect');
		$tmpl->arr = $arr;
		$tmpl->resetOption = (Object) array(
					'value' => $this->frontendUrlById($objPage->id),
					'label' =>' Zurücksetzen');
		$tmpl->selectedUser = $selectedUser;
		return $tmpl->parse();
	}
	
	private function getAvatar() {
		$result = $this->Database->prepare("SELECT avatar FROM tl_member WHERE id = ?")
			->limit(1)->execute($this->memberId);
		if ($result->numRows == 1) {
			return $result->avatar;
		}
		return false;
	}
	
} // END class SimpletippMatches


