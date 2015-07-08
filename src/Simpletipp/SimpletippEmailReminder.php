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


namespace Simpletipp;


/**
 * Class SimpletippEmailReminder
 *
 * Provide methods to for email reminder
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
use Simpletipp\Models\SimpletippModel;


class SimpletippEmailReminder extends \Backend {

    public function tippReminder() {
        \System::loadLanguageFile('default');

        $simpletippModel = SimpletippModel::findAll();
        $hours           = 24;
        $now             = time();
        $arrMessages     = array();
        foreach ($simpletippModel as $simpletippObj) {
            $match = Simpletipp::getNextMatch($simpletippObj->leagueID);

            if ($match == null
                || $simpletippObj->lastRemindedMatch == $match->id
                || ($match->deadline > (($hours*3600)+$now))) {
                // no next match found or already reminded or more than $hours to start
                $message       = sprintf('No next match found or already reminded or more than %s to start', $hours);
                $arrMessages[] = $message;
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
                foreach(Simpletipp::getNotTippedUser($simpletippObj->participant_group, $match->id) as $u) {

                    $emailSent = '';
                    if ($u['simpletipp_email_reminder'] == '1') {
                        $email = $this->generateEmailObject($simpletippObj, $emailSubject, $emailText);
                        $email->sendTo($u['email']);
                        $emailSent = '@ ';
                        $emailCount++;
                    }

                    $userNamesArr[] = $emailSent.$u['firstname'].' '.$u['lastname'].' ('.$u['username'].')';
                }

                $email       = $this->generateEmailObject($simpletippObj, 'Tipperinnerung verschickt!');
                $email->text = "Tipperinnerung an folgende Tipper verschickt:\n\n".implode("\n", $userNamesArr)."\n\n";
                $email->sendTo($simpletippObj->adminEmail);

                // Update lastRemindedMatch witch current match_id
                $simpletippObj->lastRemindedMatch = $match->id;
                $simpletippObj->save();

                $message = sprintf('Sent %s reminder Emails for %s (%s)', $emailCount,
                    $match->title, \Date::parse('d.m.Y H:i', $match->deadline));
                $arrMessages[] = $message;
                \System::log($message, 'SimpletippCallbacks tippReminder()', TL_INFO);
            } // END else
        } // END foreach


        if ('reminder' === \Input::get('key')) {
            foreach($arrMessages as $m) {
                \Message::addInfo($m);
            }
            $this->redirect(\Environment::get('script').'?do=simpletipp_group');
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
