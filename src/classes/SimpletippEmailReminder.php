<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


namespace Simpletipp;


/**
 * Class SimpletippEmailReminder
 *
 * Provide methods to for email reminder
 * @copyright  Martin Kozianka 2011-2014
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippEmailReminder extends \Backend {


    public function tippReminder() {
        \System::loadLanguageFile('default');

        $simpletippRes = $this->Database->executeUncached("SELECT * FROM tl_simpletipp");
        $hours         = 24;
        $now           = time();

        while($simpletippRes->next()) {
            $match = \Simpletipp::getNextMatch($simpletippRes->leagueID);

            if ($match == null
                || $simpletippRes->lastRemindedMatch == $match->id
                || ($match->deadline > (($hours*3600)+$now))) {
                // no next match found or already reminded or more than $hours to start
                $message = sprintf('No next match found or already reminded or more than %s to start', $hours);
                \System::log($message, 'SimpletippCallbacks tippReminder()', TL_INFO);
            }
            else {
                $pageObj         = \PageModel::findByIdOrAlias('spiele');
                $userNamesArr    = array();
                $emailSubject    = $GLOBALS['TL_LANG']['simpletipp']['email_reminder_subject'];
                $emailText       = sprintf($GLOBALS['TL_LANG']['simpletipp']['email_reminder_text'],
                    $hours, $match->title, \Date::parse('d.m.Y H:i', $match->deadline),
                    \Environment::get('base').$this->generateFrontendUrl($pageObj->row()));

                $emailCount = 0;
                foreach(\Simpletipp::getNotTippedUser($simpletippRes->participant_group, $match->id) as $u) {

                    $emailSent = '';
                    if ($u['simpletipp_email_reminder'] == '1') {
                        $email = $this->generateEmailObject($simpletippRes, $emailSubject, $emailText);
                        $email->sendTo($u['email']);
                        $emailSent = '@ ';
                        $emailCount++;
                    }

                    $userNamesArr[] = $emailSent.$u['firstname'].' '.$u['lastname'].' ('.$u['username'].')';
                }

                $email       = $this->generateEmailObject($simpletippRes, 'Tipperinnerung verschickt!');
                $email->text = "Tipperinnerung an folgende Tipper verschickt:\n\n".implode("\n", $userNamesArr)."\n\n";
                $email->sendTo($simpletippRes->adminEmail);

                // Update lastRemindedMatch witch current match_id
                $simpletippRes->lastRemindedMatch = $match->id;
                $simpletippRes->save();

                $message = sprintf('Sent %s reminder Emails for %s (%s)', $emailCount,
                    $match->title, \Date::parse('d.m.Y H:i', $match->deadline));
                \System::log($message, 'SimpletippCallbacks tippReminder()', TL_INFO);
            } // END else
        }
    }

    private function generateEmailObject($simpletippRes, $subject, $text = NULL) {
        $email           = new \Email();
        $email->from     = $simpletippRes->adminEmail;
        $email->fromName = $simpletippRes->adminName;
        $email->subject  = $subject;
        if ($text != NULL) {
            $email->text  = $text;
            $email->html  = $text;
        }
        return $email;
    }

}
