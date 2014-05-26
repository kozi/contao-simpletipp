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
 * Class SimpletippMatches
 *
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippMatches extends SimpletippModule {
	protected $strTemplate  = 'simpletipp_matches_default';
	private $formId         = 'tl_simpletipp';
	private $matches_filter = null;
	

	public function generate() {

        // Search for finished matches which are not marked as finished
        // So maybe the current result is not the final one
        $this->finishedMatches();


		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard  = '### SimpletippMatches ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}

        if ($this->isMobile) {
            $this->strTemplate  = 'simpletipp_matches_mobile';
        }

        $GLOBALS['TL_JAVASCRIPT'][] = "/system/modules/simpletipp/assets/simpletipp.js";
		return parent::generate();
    }

    protected function compile() {
        global $objPage;

		// Die übergebenen Tipps eintragen
		if ($this->Input->post('FORM_SUBMIT') === $this->formId) {
			$this->processTipps();
			$this->redirect($this->addToUrl(''));
		}

		// Spiele filtern
		$this->setMatchFilter();

        $this->Template->simpletipp = $this->simpletipp;

        $this->Template->member     = \Contao\MemberModel::findByPk($this->simpletippUserId);
        $this->Template->isPersonal = $this->isPersonal;

        $this->Template->filter     = $this->getMatchFilter();
        $this->Template->matches    = $this->getMatches();


        $this->Template->formId     = $this->formId;
        $this->Template->action     = ampersand(Environment::get('request'));
        $this->Template->isMobile   = $this->isMobile;

		$this->Template->summary    = $this->pointSummary;
		$this->Template->messages   = Simpletipp::getSimpletippMessages();
	}

	private function getMatches() {
		$matches = array();

		$sql = "SELECT
				matches.*,
				tipps.perfect AS perfect,
				tipps.difference AS difference,
				tipps.tendency AS tendency,
				tipps.tipp AS tipp
			FROM tl_simpletipp_match AS matches
		 	LEFT JOIN tl_simpletipp_tipp AS tipps ON (matches.id = tipps.match_id AND tipps.member_id = ?)
		 	WHERE matches.leagueID = ?
		 	AND (tipps.member_id = ? OR tipps.member_id IS NULL)";

		$this->order_by = ' ORDER BY deadline, matches.id ASC';

		if ($this->matches_filter->active) {
			
			$params = array_merge(
				array($this->simpletippUserId, $this->simpletipp->leagueID, $this->simpletippUserId),
				$this->matches_filter->params
			);

			$result = $this->Database->prepare($sql.$this->matches_filter->stmt.$this->matches_filter->order_by)
				->limit($this->matches_filter->limit)->execute($params);
		} else {
			
			
			$result = $this->Database->prepare($sql.$this->order_by)
				->execute($this->simpletippUserId, $this->simpletipp->leagueID, $this->simpletippUserId);
		}

		$i            = 0;
        $pageObj      = PageModel::findByPk($this->simpletipp_match_page);
        $pageRow      = ($pageObj !=null) ? $pageObj->row() : null;
        $currentGroup = 0;

        while ($result->next()) {

            $match              = (Object) $result->row();

            $match->isStarted   = (time() > $result->deadline);
			$match->date        = date($GLOBALS['TL_CONFIG']['datimFormat'], $match->deadline);
			$match->date_title  = $match->date;

            $pointObj           = new SimpletippPoints($this->pointFactors, $match->perfect, $match->difference, $match->tendency);
			$match->points      = $pointObj->points;

            $match->cssClass    = ($i++ % 2 === 0 ) ? 'odd':'even';
            $match->cssClass   .= ($i == $result->numRows) ? ' last' : '';
            $match->cssClass   .= ($match->isFinished) ? ' finished' : '';

            if (count($matches) > 0 && $match->groupName_short != $currentGroup) {
                $prevMatch = &$matches[(count($matches)-1)];
                $prevMatch->cssClass .= ($currentGroup != 0) ? ' break' : '';
            }
            $currentGroup = $match->groupName_short;

            if ($pageRow !== null) {
                $alias = strtolower($match->team_h.'-'.$match->team_a);
                $alias = str_replace(array('ü','ä','ö'), array('ue', 'ae', 'oe'), $alias);
                $match->matchLink = $this->generateFrontendUrl($pageRow, '/match/'.$alias);
            }

            $match->pointsClass = '';
			if (strlen($match->result) > 0) {
				$match->pointsClass = $pointObj->getPointsClass();
			}
			
			$teams = explode("-", $match->title_short);
			$match->alias_h = standardize($teams[0]);
			$match->alias_a = standardize($teams[1]);


            $this->convertIconLinks($match);

            $matches[] = $match;

			$this->updateSummary($pointObj);
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
        $group   = (Input::get('group') !== null) ? urldecode(Input::get('group')) : null;
		$date    = Input::get('date');
		$matches = Input::get('matches');

		if ($group === null && $date === null && $matches === null) {
			$this->redirect($this->addToUrl('matches=current&date=&group='));
		}
		
		if (strlen($matches) > 0 && $matches == 'current') {
			$this->matches_filter->type     = 'current';
			$this->matches_filter->active   = true;

			$result = $this->Database->prepare("SELECT groupID FROM tl_simpletipp_match
						WHERE leagueID = ?
						AND deadline < ? ORDER BY deadline DESC")
						->limit(1)->execute($this->simpletipp->leagueID, $this->now);

			if ($result->numRows == 1) {
				$this->matches_filter->params[] = $result->groupID;
			}
				
			$result = $this->Database->prepare("SELECT groupID FROM tl_simpletipp_match
					WHERE leagueID = ?
					AND deadline > ? ORDER BY deadline ASC")
					->limit(1)->execute($this->simpletipp->leagueID, $this->now);

			if ($result->numRows == 1) {
				$this->matches_filter->params[] = $result->groupID;
			}

			if (count($this->matches_filter->params) == 1) {
				$this->matches_filter->params[] = $this->matches_filter->params[0];
			}

			
			$this->matches_filter->stmt     =
				' AND (matches.groupID = ? OR matches.groupID = ?)';
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
				$this->matches_filter->order_by = ' ORDER BY deadline DESC, matches.id ASC';
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
			$this->matches_filter->stmt     = ' AND matches.groupName = ?';
			$this->matches_filter->params[] = $group;
			return;
		}
		
	}

	private function getMatchFilter() {
        $tmplStr     = ($this->isMobile) ? 'simpletipp_filter_mobile' : 'simpletipp_filter';
        $tmpl        = new FrontendTemplate($tmplStr);
        $date_filter = array();

        $lastArr     = array(9);
        $nextArr     = array(9);

        foreach ($lastArr as $l) {
            $date_filter[] = array(
                    'title'    => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][0], $l),
                    'cssClass' => ($this->matches_filter->type =='last-'.$l) ? ' class="date_filter active"':' class="date_filter"',
                    'selected' => ($this->matches_filter->type =='last-'.$l) ? ' selected="selected"':'',
                    'desc'     => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][1], $l),
                    'href'     => $this->addToUrl('date=last-'.$l.'&group=&matches='));
        }

        $date_filter[] = array(
                    'title'    => $GLOBALS['TL_LANG']['simpletipp']['current'][0],
					'cssClass' => ($this->matches_filter->type =='current') ? ' class="date_filter active"':' class="date_filter"',
                    'selected' => ($this->matches_filter->type =='current') ? ' selected="selected"':'',
					'desc'     => $GLOBALS['TL_LANG']['simpletipp']['current'][1],
					'href'     => $this->addToUrl('matches=current&date=&group='));

        $date_filter[] = array(
                    'title'    => $GLOBALS['TL_LANG']['simpletipp']['all'][0],
					'cssClass' => ($this->matches_filter->type =='all') ? ' class="date_filter active"':' class="date_filter"',
                    'selected' => ($this->matches_filter->type =='all') ? ' selected="selected"':'',
					'desc'     => $GLOBALS['TL_LANG']['simpletipp']['all'][1],
					'href'     => $this->addToUrl('matches=all&date=&group='));

        foreach ($nextArr as $n) {
            $date_filter[] = array(
                'title'    => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][0], $n),
                'cssClass' => ($this->matches_filter->type =='next-'.$n) ? ' class="date_filter active"':' class="date_filter"',
                'selected' => ($this->matches_filter->type =='next-'.$n) ? ' selected="selected"':'',
                'desc'     => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][1], $n),
                'href'     => $this->addToUrl('date=next-'.$n.'&group=&matches='));
        }

        $tmpl->special_filter = $date_filter;

        if ($this->simpletippGroups !== null) {
            foreach($this->simpletippGroups as $mg) {
                $groups[] = array(
                    'title'    => $mg->short,
                    'desc'     => $mg->title,
                    'href'     => $this->addToUrl('group='.$mg->title.'&date=&matches='),
                    'cssClass' => ($this->matches_filter->type == $mg->title) ? ' class="active"': '',
                    'selected' => ($this->matches_filter->type == $mg->title) ? ' selected="selected"':'',
                );
		    }
            $tmpl->group_filter = $groups;
        }
		return $tmpl->parse();
	}

	private function processTipps() {
		global $objPage;
		
		if (!FE_USER_LOGGED_IN) {
			return false;
		}
        $ids   = $this->Input->post('match_ids');
        $tipps = $this->Input->post('tipps');

		$to_db = array();
		
		if (is_array($ids) && is_array($tipps)
			&& count($ids) === count($tipps) && count($ids) > 0) {
		
			for ($i=0;$i < count($ids);$i++) {
				$id    = intval($ids[$i]);
				$tipp  = Simpletipp::cleanupTipp($tipps[$i]);

				if (preg_match('/^(\d{1,4}):(\d{1,4})$/', $tipp)) {
					$to_db[$id] = $tipp;
				}
			}

			$checkSql  = "SELECT id FROM tl_simpletipp_tipp WHERE member_id = ? AND match_id = ?";
			$updateSql = "UPDATE tl_simpletipp_tipp SET tipp = ? WHERE id = ?";
			$insertSql = "INSERT INTO tl_simpletipp_tipp(tstamp, member_id, match_id, tipp) VALUES(?, ?, ?, ?)";
			$memberId  = $this->User->id;

			foreach($to_db as $id=>$tipp) {
				$result = $this->Database->prepare($checkSql)->execute($memberId, $id);

				if ($result->numRows > 0) {
					$result = $this->Database->prepare($updateSql)
						->execute($tipp, $result->id);
				}
				else {
					$result = $this->Database->prepare($insertSql)
						->execute(time(), $memberId, $id, $tipp);
				}
			}
		}

        if (count(array_keys($to_db)) == 0) {
            // TODO translation
            $message = "Es wurden keine Tipps eingetragen!";
        }
        else {
            $this->sendTippEmail($to_db);

            if ($this->User->simpletipp_email_confirmation == '1') {
                $message = sprintf($GLOBALS['TL_LANG']['simpletipp']['message_inserted_email'], $this->User->email);
            }
            else {
                $message = $GLOBALS['TL_LANG']['simpletipp']['message_inserted'];
            }
        }
        Simpletipp::addSimpletippMessage($message);
		return true;
	}

	private function sendTippEmail($matches) {

        if (!is_array($matches) || count(array_keys($matches)) == 0){
            // Nothing to send
            return false;
        }

		$result = $this->Database->execute('SELECT * FROM tl_simpletipp_match'
			.' WHERE id in ('.implode(',', array_keys($matches)).')');
		
		$content = $GLOBALS['TL_LANG']['simpletipp']['email_text'];
		while($result->next()) {
            $content .= sprintf("%s %s = %s\n",
					date('d.m.Y H:i', $result->deadline),
					$result->title,
					$matches[$result->id]
				); 
		}

        $content .= "\n\n--\n".\Contao\Environment::get("base")."\n".$GLOBALS['TL_ADMIN_EMAIL'];
        $subject  = sprintf($GLOBALS['TL_LANG']['simpletipp']['email_subject'],
                       date('d.m.Y H:i:s'), $this->User->firstname.' '.$this->User->lastname);

        // Send to user
        if ($this->User->simpletipp_email_confirmation == '1') {
            $email           = new Email();
            $email->from     = $this->simpletipp->adminEmail;
            $email->fromName = $this->simpletipp->adminName;
            $email->subject  = $subject;
            $email->text     = $content;
            $email->replyTo($this->User->email);
            $email->sendTo($this->User->email);
        }

        // Send encoded to admin
        $email           = new Email();
        $email->from     = $this->simpletipp->adminEmail;
        $email->fromName = $this->simpletipp->adminName;
        $email->subject  = $subject;
        $email->text     = base64_encode($content);
        $email->replyTo($this->User->email);
        $email->sendTo($GLOBALS['TL_ADMIN_EMAIL']);

        return true;
	}

    private function finishedMatches() {

        $matchEnd = time() - Simpletipp::$MATCH_LENGTH;
        $result   = $this->Database->prepare("SELECT * FROM tl_simpletipp_match
               WHERE leagueID = ? AND deadline < ? AND isFinished = ?")
            ->execute($this->simpletipp->leagueID, $matchEnd, 0);

        if ($result->numRows > 0) {
            $this->import('SimpletippMatchUpdater');
            $this->SimpletippMatchUpdater->updateSimpletippMatches($this->simpletipp);
        }

    }


    private function convertIconLinks(&$match) {

        foreach(array('h','a') as $suffix) {
            $iconKey  = 'icon_'.$suffix;
            $aliasKey = 'alias_'.$suffix;
            $strAlias = $match->$aliasKey;
            $url      = $match->$iconKey;

            // Wikimedia hack TODO search for '/??px'
            $url      =  str_replace('20px', '512px', $url);

            // TODO Read path from module configuration
            $strFile  = 'files/simpletipp-icons/' .$strAlias.'.'.pathinfo($url, PATHINFO_EXTENSION);

            if (!file_exists(TL_ROOT . '/'.$strFile)) {
                $fileData = file_get_contents($url);
                $file     = new \File($strFile);
                $file->write($fileData);
                $file->close();
            }

            $match->$iconKey = $strFile;
        }

    }


} // END class SimpletippMatches


