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


namespace Simpletipp;


/**
 * Class SimpletippEmailReminder
 *
 * Provide methods to for email reminder
 * @copyright  Martin Kozianka 2014-2016
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
use Simpletipp\Models\SimpletippModel;
use Simpletipp\Models\SimpletippMatchModel;

class SimpletippEmailReminder extends \Backend
{
    public function tippReminder()
    {
        \System::loadLanguageFile('default');

        $simpletippModel = SimpletippModel::findAll();
        $hours           = 24;
        $now             = time();
        $arrMessages     = [];
        foreach ($simpletippModel as $simpletippObj)
        {
            $match = SimpletippMatchModel::getNextMatch($simpletippObj->leagueID);

            if ($match == null
                || $simpletippObj->lastRemindedMatch == $match->id
                || ($match->deadline > (($hours*3600)+$now)))
            {
                // no next match found or already reminded or more than $hours to start
                $message       = sprintf('[%s] No next match found or already reminded or more than %s to start', $simpletippObj->title, $hours);
                $arrMessages[] = $message;
            }
            else
            {
                // Spiele Seite aus der Konfiguration auslesen
                $matchesPageUrl = \Environment::get('base');
                $pageObj = \PageModel::findById($simpletippObj->matches_page);
                $matchesPageUrl .= ($pageObj) ? $this->generateFrontendUrl($pageObj->row()) : '';

                $userNamesArr    = [];
                $emailSubject    = $GLOBALS['TL_LANG']['simpletipp']['email_reminder_subject'];
                $emailText       = sprintf($GLOBALS['TL_LANG']['simpletipp']['email_reminder_text'],
                    $hours, $match->title, \Date::parse('d.m.Y H:i', $match->deadline), $matchesPageUrl);

                $emailCount = 0;
                foreach(static::getNotTippedUser($simpletippObj->participant_group, $match->id) as $u)
                {
                    $emailSent = '';
                    if ($u['simpletipp_email_reminder'] == '1')
                    {
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

        if ('reminder' === \Input::get('key'))
        {
            foreach($arrMessages as $m)
            {
                \Message::addInfo($m);
            }
            $this->redirect($this->getReferer()."?do=simpletipp_group");
        }
    }

    private function generateEmailObject($simpletippRes, $subject, $text = NULL)
    {
        $email           = new \Email();
        $email->from     = $simpletippRes->adminEmail;
        $email->fromName = $simpletippRes->adminName;
        $email->subject  = $subject;
        if ($text != NULL)
        {
            $email->text  = $text;
            $email->html  = $text;
        }
        return $email;
    }

    public static function getNotTippedUser($groupID, $match_id)
    {
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';

        $result = \Database::getInstance()->prepare("SELECT tblu.*
             FROM tl_member as tblu
             LEFT JOIN tl_simpletipp_tipp AS tblt
             ON ( tblu.id = tblt.member_id AND tblt.match_id = ?)
             WHERE tblt.id IS NULL
             AND CONVERT(tblu.groups USING utf8) LIKE ?
             ORDER BY tblu.lastname")->execute($match_id, $participantStr);

        $arrUser = [];
        while ($result->next())
        {
            $arrUser[] = $result->row();
        }
        return $arrUser;
    }

}
