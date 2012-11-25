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
 * Class SimpletippMatches
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */

class SimpletippMatches extends Simpletipp {
	protected $strTemplate  = 'simpletipp_matches_default';
	private $formId         = 'tl_simpletipp';
	private $isPersonal     = false;
	private $sendEmail      = true;
	private $memberId       = null;
	private $matches_filter = null;
	

	public function generate() {

		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippMatches ###';
	
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
		
		// Die übergebenen Tipps eintragen
		if ($this->Input->post('FORM_SUBMIT') === $this->formId) {
			$this->processTipps();
			$this->redirect($this->addToUrl(''));
		}
				
		// Spiele filtern
		$this->setMatchFilter();
		
		if ($this->isParticipant($this->memberId) || $this->memberId == null) {

			if ($this->memberId != null) {
				$this->Template->matches = $this->getMatches();
			}
			else {
				$this->Template->matches = false;
			}

			$this->Template->userselect = $this->getUserSelect();
		}
 		
		$this->Template->isMobile     = $objPage->isMobile;
		$this->Template->formId       = $this->formId;
		$this->Template->action       = ampersand($this->Environment->request);
		$this->Template->memberId     = $this->memberId;

		$this->Template->matchFilter  = $this->getMatchFilter();
		$this->Template->group        = $group;
		$this->Template->summary      = $this->summary;
		$this->Template->messages     = $this->getSimpletippMessages();
		$this->Template->isPersonal   = $this->isPersonal;
		
		if ($this->Template->showAvatar) {
			$this->Template->avatar   = $this->getAvatar();
		}

	}

	private function getMatches() {

		$matches = array();
		if (!$this->group->matches) {
			return $matches;
		};
		
		$sql = "SELECT "
		."matches.id AS matchId, "
		."matches.matchgroup AS matchgroup,"
		."matches.deadline AS deadline,"
		."matches.title AS title,"
		."matches.title_short AS title_short,"
		."matches.result AS result,"
		."tipps.perfect AS perfect,"
		."tipps.difference AS difference,"
		."tipps.tendency AS tendency,"
		."tipps.tipp AS tipp"
		
		." FROM tl_simpletipp_matches AS matches "
		." LEFT JOIN tl_simpletipp_tipps AS tipps ON (matches.id = tipps.match_id AND tipps.member_id = ?)"
		." WHERE matches.id in (".implode(',', $this->group->matches).")"
		." AND (tipps.member_id = ? OR tipps.member_id IS NULL)";

		$this->order_by = ' ORDER BY deadline, matches.id ASC';
		
		if ($this->matches_filter->active) {
			
			$params = array_merge(
				array($this->memberId, $this->memberId),
				$this->matches_filter->params
			);
			$result = $this->Database->prepare($sql.$this->matches_filter->stmt.$this->matches_filter->order_by)
				->limit($this->matches_filter->limit)->execute($params);
		} else {
			$result = $this->Database->prepare($sql.$this->order_by)
				->execute($this->memberId, $this->memberId);
		}

		$matches = array();
		$i = 0;
		
		while ($result->next()) {
			$match = (Object) $result->row();
			$match->matchLink = $this->frontendUrlById($this->simpletipp_match_page,
					'/match/'.$match->matchId);
			
			$match->isStarted      = (time() > $result->deadline);
			$match->date           = date($GLOBALS['TL_CONFIG']['datimFormat'], $match->deadline);
			$match->date_title     = $match->date; 
			$match->points         = $match->perfect    * $this->pointFactors->prfect 
									  + $match->difference * $this->pointFactors->difference
									  + $match->tendency   * $this->pointFactors->tendency;
			
			$mg = explode('.', $match->matchgroup);
			$match->matchgroup_short = $mg[0];
			
			$match->cssClass = ($i++ % 2 === 0 ) ? 'odd':'even';
			$match->pointsClass = '';
			if (strlen($match->result) > 0) {
				$match->pointsClass = $this->getPointsClass(
					$match->perfect, $match->difference, $match->tendency);
			
			}
			
			$teams = explode("-", $match->title_short);
			$match->team_h = standardize($teams[0]);
			$match->team_a = standardize($teams[1]);
				
			$matches[] = $match;
			
			$this->updateSummary($match->points, $match->perfect,
					$match->difference, $match->tendency);
		}
		
		return $matches;
	}
	
	private function setMatchFilter() {
		$this->matches_filter           = new stdClass;
		$this->matches_filter->type     = '';
		$this->matches_filter->stmt     = '';
		$this->matches_filter->params   = array();
		$this->matches_filter->limit    = 0;
		$this->matches_filter->order_by = ' ORDER BY deadline, matches.id ASC';

		// matchgroup filter
		$group   = $this->Input->get('group');
		$date    = $this->Input->get('date');
		$matches = $this->Input->get('matches');

		if ($group === null && $date === null && $matches === null) {
			$this->redirect($this->addToUrl('matches=current&date=&group='));
		}
		
		if (strlen($matches) > 0 && $matches == 'current') {
			$this->matches_filter->type     = 'current';
			$this->matches_filter->active   = true;

			// TODO :: var_dump($this->simpletipp_group);
			$result = $this->Database->prepare('SELECT matchgroup FROM tl_simpletipp_matches'
					.' WHERE id in ('.implode(',', $this->group->matches).')'
					.' AND deadline > NOW() ORDER BY deadline DESC')->limit(1)->execute();
			
			if ($result->numRows == 1) {
				$this->matches_filter->params[] = $result->matchgroup;
			}
				
			$result = $this->Database->prepare('SELECT matchgroup FROM tl_simpletipp_matches'
					.' WHERE id in ('.implode(',', $this->group->matches).')'
					.' AND deadline < NOW() ORDER BY deadline ASC')->limit(1)->execute();

			if ($result->numRows == 1) {
				$this->matches_filter->params[] = $result->matchgroup;
			}

			if (count($this->matches_filter->params) == 1) {
				$this->matches_filter->params[] = $this->matches_filter->params[0];
			}

			$this->matches_filter->stmt     =
				' AND (matches.matchgroup = ? OR matches.matchgroup = ?)';
			return;
		}
		
		
		if (strlen($matches) > 0 && $matches == 'all') {
			$this->matches_filter->type   = 'all';
			return;
		}

		if (strlen($date) > 0) {
			$this->matches_filter->type   = $date;
			$this->matches_filter->active = true;

			$this->matches_filter->stmt   = ' AND matches.deadline > ?';
			
			if (strpos($date, 'last') !== false) {
				$this->matches_filter->stmt     = ' AND matches.deadline < ?';
				$this->matches_filter->order_by = ' ORDER BY deadline DESC';
			}

			$limit = intval(str_replace(
					array('last-', 'next-'), array('', ''), $date));
			
			$this->matches_filter->params[] = $this->now;
			$this->matches_filter->limit    = $limit;
			return;
		}
		
		if (strlen($group) > 0) {
			$this->matches_filter->type     = $group;
			$this->matches_filter->active   = true;
			$this->matches_filter->stmt     = ' AND matches.matchgroup = ?';
			$this->matches_filter->params[] = $group;
			return;
		}
		
	}

	private function getMatchFilter() {
		$tmpl = new FrontendTemplate('simpletipp_matchfilter');
		$tmpl->act_filter  = $this->matches_filter;
		
		$tmpl->date_filter   = array(
				
			array('title' => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][0], '3'),
					'cssClass' => ($this->matches_filter->type =='last-3') ? ' class="active"':'',
					'desc' => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][1], '3'),
					'href' => $this->addToUrl('date=last-3&group=&matches=')),
			array('title' => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][0], '6'),
					'cssClass' => ($this->matches_filter->type =='last-6') ? ' class="active"':'',
					'desc' => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][1], '6'),
					'href' => $this->addToUrl('date=last-6&group=&matches=')),
			array('title' => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][0], '9'),
					'cssClass' => ($this->matches_filter->type =='last-9') ? ' class="active"':'',
					'desc' => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][1], '9'),
					'href' => $this->addToUrl('date=last-9&group=&matches=')),

			array('title' => $GLOBALS['TL_LANG']['simpletipp']['current'][0],
					'cssClass' => ($this->matches_filter->type =='current') ? ' class="active"':'',
					'desc' => $GLOBALS['TL_LANG']['simpletipp']['current'][1],
					'href' => $this->addToUrl('matches=current&date=&group=')),
			array('title' => $GLOBALS['TL_LANG']['simpletipp']['all'][0],
					'cssClass' => ($this->matches_filter->type =='all') ? ' class="active"':'',
					'desc' => $GLOBALS['TL_LANG']['simpletipp']['all'][1],
					'href' => $this->addToUrl('matches=all&date=&group=')),
				
			array('title' => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][0], '3'),
					'cssClass' => ($this->matches_filter->type =='next-3') ? ' class="active"':'',
					'desc' => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][1], '3'),
					'href' => $this->addToUrl('date=next-3&group=&matches=')),
			array('title' => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][0], '6'),
					'cssClass' => ($this->matches_filter->type =='next-6') ? ' class="active"':'',
					'desc' => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][1], '6'),
					'href' => $this->addToUrl('date=next-6&group=&matches=')),
			array('title' => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][0], '9'),
					'cssClass' => ($this->matches_filter->type =='next-9') ? ' class="active"':'',
					'desc' => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][1], '9'),
					'href' => $this->addToUrl('date=next-9&group=&matches=')),
		);

		$result = $this->Database->execute("SELECT DISTINCT matchgroup FROM tl_simpletipp_matches"
				." WHERE id in (".implode(',', $this->group->matches).") ORDER BY matchgroup");
		 
		$groups = array();
		foreach($this->group->matchgroups as $mg) {
			$groups[] = array(
				'title'    => $mg->short,
				'desc'     => $mg->title,
				'href'     => $this->addToUrl('group='.$mg->title.'&date=&matches='),
				'cssClass' => ($this->matches_filter->type == $mg->title) ? ' class="active"': '',
			);
		}
		$tmpl->group_filter = $groups;
				
		return $tmpl->parse();
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
		$t = preg_replace ('/[^0-9]/',' ',$tipp);
		$t = preg_replace ('/\s+/',':',$t);

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
	private function sendTippEmail($matches) {

		$result = $this->Database->execute('SELECT * FROM tl_simpletipp_matches' 
			.' WHERE id in ('.implode(',', array_keys($matches)).')');

		
		
			
		
		$content = $GLOBALS['TL_LANG']['simpletipp']['email_text'];
		while($result->next()) {
			$content .= sprintf("%s %s = %s\n",
					date('d.m.Y H:i', $result->deadline),
					$result->title,
					$matches[$result->id]
				); 
		}

		
		$email           = new Email();
		$email->from     = $GLOBALS['TL_ADMIN_EMAIL'];
		$email->fromName = $GLOBALS['TL_ADMIN_NAME'];
		
		$email->subject  = sprintf($GLOBALS['TL_LANG']['simpletipp']['email_subject'],
								date('d.m.Y H:i:s'), $this->User->name);

		// Send to user
		$email->text     = $content;
		$email->sendTo($this->User->email);
		
		// Send encoded to admin
		$email->text     = base64_encode($content);
		$email->sendTo($GLOBALS['TL_ADMIN_EMAIL']);
	}
	
	
	private function isParticipant($memberId) {
		if (!$memberId) {
			return false;
		}
		
		$result = $this->Database->prepare("SELECT groups FROM tl_member WHERE id = ?")
					->execute($memberId);

		if ($result->numRows == 1) {
			$groups = unserialize($result->groups);
			return (is_array($groups) && in_array($this->group->participant_group, $groups));
		}
		return false;
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

	private function getUserSelect() {
		global $objPage;
		
		$result = $this->Database->execute(
				"SELECT id, username, firstname, lastname, groups FROM tl_member"
				." ORDER BY lastname ASC, firstname ASC");
		
		$members = array();
		while($result->next()) {
			$groups = unserialize($result->groups);
			if (in_array($this->group->participant_group, $groups)) {
				$m = (Object) $result->row();
				$m->groups = $groups;
				$m->link   = $this->addToUrl('member='.$m->username);
				$members[$m->id] = $m;
			}
		}

		$tmpl = new FrontendTemplate('simpletipp_userselect');
		$tmpl->arr = $members;
		$tmpl->resetOption = (Object) array(
					'value' => $this->frontendUrlById($objPage->id),
					'label' =>' Zurücksetzen');
		$tmpl->selectedUser = $this->memberId;
		return $tmpl->parse();
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
	
} // END class SimpletippMatches


