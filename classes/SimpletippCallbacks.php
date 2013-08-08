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
 * Class SimpletippCallbacks
 *
 * Provide methods to import matches
 * @copyright  Martin Kozianka 2012-2013 
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippCallbacks extends Backend {

    public function updateMatches(DataContainer $dc) {
		$id = intval(Input::get('id'));

		$result = $this->Database
			->prepare("SELECT leagueObject FROM tl_simpletipp WHERE id = ?")
			->execute($id);
		
		if ($result->numRows > 0) {
			$leagueObj = unserialize($result->leagueObject);
			
			$this->import('tl_simpletipp');
			
			$this->import('OpenLigaDB');
			$this->OpenLigaDB->setLeague($leagueObj);
			
			$lastChange = $this->OpenLigaDB->getLastLeagueChange();

            // TODO
			if(array_key_exists($leagueObj->leagueID, $this->config->leagueUpdates)) {
				$lastUpdate = $this->config->leagueUpdates[$leagueObj->leagueID];
			} else {
				$this->config->leagueUpdates[$leagueObj->leagueID] = $lastChange;
			}

			if ($lastChange !== $lastUpdate) {
				$matchIDs = $this->tl_simpletipp->updateMatches(null, $leagueObj);
				$this->updateTipps($matchIDs);
				$message = sprintf('Liga <strong>%s</strong> aktualisiert! ', $leagueObj->leagueName);
			}
			else {
				$message = sprintf('Keine Änderungen seit der letzen Aktualisierung in Liga <strong>%s</strong>. ', $leagueObj->leagueName);
			}
            Message::add($message, 'TL_INFO');
		}
		$this->redirect(Environment::get('script').'?do=simpletipp_groups');
	}
	
	
	private function updateTipps($ids) {
		if (count($ids) === 0) {
			//  Nothing to do
			return true;
		}
	
		$result = $this->Database->execute(
				"SELECT id, result FROM tl_simpletipp_match"
				." WHERE id in (".implode(',', $ids).")");
		while($result->next()) {
			$match_results[$result->id] = $result->result;
		}
	
		$result = $this->Database->execute(
				"SELECT id, match_id, tipp FROM tl_simpletipp_tipp"
				." WHERE match_id in (".implode(',', $ids).")");
		while($result->next()) {
            $points = Simpletipp::getPoints($match_results[$result->match_id], $result->tipp);

			$this->Database->prepare("UPDATE tl_simpletipp_tipp"
                ." SET perfect = ?, difference = ?, tendency = ?, wrong = ? WHERE id = ?")
                ->execute($points->perfect, $points->difference, $points->tendency, $points->wrong, $result->id);
		}
	}	
	
	
	public function addCustomRegexp($strRegexp, $varValue, Widget $objWidget) {
		if ($strRegexp == 'SimpletippFactor') {
			if (!preg_match('#^[0-9]{1,6},[0-9]{1,6},[0-9]{1,6}$#', $varValue)) {
				$objWidget->addError('Format must be <strong>NUMBER,NUMBER,NUMBER</strong>.');
			}
			return true;
		}
		return false;
	}

    public function createNewsletterChannel() {
        $result = $this->Database->execute("SELECT * FROM tl_simpletipp");

        while($result->next()) {
            $simpletipp = $result->row();
            $channelResult = $this->Database->prepare("SELECT * FROM tl_newsletter_channel WHERE simpletipp = ?")
                        ->execute($simpletipp['id']);

            if ($channelResult->numRows == 0) {
                $nlc = new NewsletterChannelModel();
                $nlc->setRow(array(
                    'simpletipp' => $simpletipp['id'],
                    'title'      => 'SIMPLETIPP '.$simpletipp['title'],
                    'jumpTo'     => 1, // TODO
                    'tstamp'     => time(),
                ));
                $nlc->save();
                $channel_id = $nlc->id;
            }
            else {
                $channel_id = $channelResult->id;
            }
            $this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE pid = ?")
                ->execute($channel_id);

            $emails     = array();
            $memberArr  = Simpletipp::getGroupMember($simpletipp['participant_group'], true);
            foreach($memberArr as $member) {
                $emails[] = $member->email;
            }

            $emails = array_unique($emails);
            foreach($emails as $email) {
                $this->Database->prepare("INSERT INTO tl_newsletter_recipients %s")->set(array(
                    'pid'       => $channel_id,
                    'email'     => $email,
                    'tstamp'    => $simpletipp['tstamp'],
                    'addedOn'   => $simpletipp['tstamp'],
                    'confirmed' => $simpletipp['tstamp'],
                    'active'    => '1',
                ))->execute();
            }
        }
    }


    public function tippReminder() {
        $simpletippRes = $this->Database->execute("SELECT * FROM tl_simpletipp");
        $hours         = 24;
        $now           = time();

        while($simpletippRes->next()) {
            $match = Simpletipp::getNextMatch($simpletippRes->leagueID);

            if ($match == null
                || $simpletippRes->lastRemindedMatch == $match->id
                || ($match->deadline > (($hours*3600)+$now))) {
                // no next match found or already reminded or more than $hours to start
                // return false;
            }

            $pageObj         = PageModel::findByIdOrAlias('spiele');
            $email           = new Email();
            $email->from     = $simpletippRes->adminEmail;
            $email->fromName = $simpletippRes->adminName;
            $email->subject  = $GLOBALS['TL_LANG']['simpletipp']['email_reminder_subject'];
            $email->text     = sprintf($GLOBALS['TL_LANG']['simpletipp']['email_reminder_text'],
                    $hours, $match->title, Date::parse('d.m.Y H:i', $match->deadline),
                    Environment::get('base').$this->generateFrontendUrl($pageObj->row()));

            foreach(Simpletipp::getNotTippedUser($simpletippRes->participant_group, $match->id) as $u) {
                $email->sendTo($u['email']);
                $user[] = $u['firstname'].' '.$u['lastname'].' ('.$u['username'].')';
            }

            $email           = new Email();
            $email->from     = $simpletippRes->adminEmail;
            $email->fromName = $simpletippRes->adminName;
            $email->subject  = 'Tipperinnerung verschickt!';
            $email->text     = sprintf("Tipperinnerung an folgende Tipper verschickt:\n\n%s\n\n",
                                    implode("\n", $user)
            );
            $email->sendTo($simpletippRes->adminEmail);

            // Update lastRemindedMatch witch current match_id
            $this->Database->prepare("UPDATE tl_simpletipp SET lastRemindedMatch = ? WHERE id = ?")
                ->execute($match->id, $simpletippRes->id);
        }
    }



    public function randomLine($strTag) {
        $arr = explode('::', $strTag);
        if ($arr[0] == 'random_line') {

            if (!isset($arr[1])) {
                return sprintf('Error! No file defined.');
            }
            if (!file_exists($arr[1])) {
                return sprintf('Error! File "%s" does not exist.', $arr[1]);
            }
            $fileArr = file($arr[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $index = array_rand($fileArr);
            $str   = trim($fileArr[$index]);
            if (count($arr) === 2) {
                return $str;
            }

            if (count($arr) === 3) {
                return sprintf('Error! No format string defined.');
            }

            $strArr = explode($arr[2], $str);
            $tmpl   = $arr[3];
            if  (substr_count($tmpl, '%s') !== count($strArr)) {
                echo $arr[2];
                var_dump($strArr);
                var_dump($str);
                return sprintf('Error! Wrong parameter count in %s (%s).', htmlentities($tmpl), count($strArr));
            }

            return vsprintf($tmpl, $strArr);
        }
        // nicht unser Insert-Tag
        return false;
    }


}
