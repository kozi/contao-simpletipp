<?php

namespace Simpletipp;

use Contao\Email;
use Simpletipp\Models\SimpletippMatchModel;
use Simpletipp\Models\SimpletippModel;

/**
 * Class SimpletippEmailReminder
 *
 * Provide methods to for email reminder
 * @copyright  Martin Kozianka 2014-2019
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippEmailReminder extends \Backend
{
    public function tippReminder()
    {
        \System::loadLanguageFile('default');

        $this->isDebug = isset($_GET["debug"]);
        $simpletippModel = SimpletippModel::findAll();
        $hours = 24;
        $now = time();
        $arrMessages = [];
        foreach ($simpletippModel as $simpletippObj) {
            $match = SimpletippMatchModel::getNextMatch($simpletippObj->leagueID);

            if ($match == null) {
                $arrMessages[] = sprintf('[%s] No next match found', $simpletippObj->title, $hours);
            } else if (!$this->isDebug && ($simpletippObj->lastRemindedMatch == $match->id || ($match->deadline > (($hours * 3600) + $now)))) {
                // no next match found or already reminded or more than $hours to start
                $arrMessages[] = sprintf('[%s] Already reminded for %s (%s) or more than %s to start',
                    $simpletippObj->title, $match->title, \Date::parse('d.m.Y H:i', $match->deadline), $hours);
            } else {
                // Spiele Seite aus der Konfiguration auslesen
                $matchesPageUrl = \Environment::get('base');
                $pageObj = \PageModel::findById($simpletippObj->matches_page);
                $matchesPageUrl .= ($pageObj) ? $this->generateFrontendUrl($pageObj->row()) : '';

                $userNamesArr = [];
                $emailSubject = sprintf($GLOBALS['TL_LANG']['simpletipp']['email_reminder_subject'],
                    $match->title);
                $emailText = sprintf($GLOBALS['TL_LANG']['simpletipp']['email_reminder_text'],
                    $hours, $match->title, \Date::parse('d.m.Y H:i', $match->deadline), $matchesPageUrl);

                if ($this->isDebug) {
                    echo "<pre>emailSubject\n\n";
                    var_dump($emailSubject);
                    echo "\n\n\n</pre><pre>emailSubject\n\n";
                    var_dump($emailText);
                    echo "\n\n\n</pre>";
                }

                $emailCount = 0;
                foreach (static::getNotTippedUser($simpletippObj->participant_group, $match->id) as $u) {
                    $emailSent = '';
                    if ($u['simpletipp_email_reminder'] == '1') {
                        $email = $this->generateEmailObject($simpletippObj, $emailSubject, $emailText);

                        if ($this->isDebug) {
                            $arrMessages[] = "EMAIL to: " . $u['email'] . " SUBJECT: " . $emailSubject;
                        } else {
                            $email->sendTo($u['email']);
                        }

                        $emailSent = '@ ';
                        $emailCount++;
                    }

                    $userNamesArr[] = $emailSent . $u['firstname'] . ' ' . $u['lastname'] . ' (' . $u['username'] . ')';
                }

                $email = $this->generateEmailObject($simpletippObj, 'Tipperinnerung verschickt!');
                $email->text = "Tipperinnerung an folgende Tipper verschickt:\n\n" . implode("\n", $userNamesArr) . "\n\n";
                (!$this->isDebug) && $email->sendTo($simpletippObj->adminEmail);

                // Update lastRemindedMatch witch current match_id
                $simpletippObj->lastRemindedMatch = $match->id;
                $simpletippObj->save();

                $message = sprintf('Sent %s reminder Emails for %s (%s)', $emailCount,
                    $match->title, \Date::parse('d.m.Y H:i', $match->deadline));
                $arrMessages[] = $message;
                \System::log($message, 'SimpletippCallbacks tippReminder()', 'TL_INFO');
            } // END else
        } // END foreach

        if ($this->isDebug) {
            echo "<pre>";
            var_dump($arrMessages);
            die("\n\n\nDEBUG DONE!</pre>");
        }

        if ('reminder' === \Input::get('key')) {
            foreach ($arrMessages as $m) {
                \Message::addInfo($m);
            }
            $this->redirect($this->getReferer() . "?do=simpletipp_group");
        }
    }

    private function generateEmailObject($simpletippRes, $subject, $text = null)
    {
        $email = new Email();
        $email->from = $simpletippRes->adminEmail;
        $email->fromName = $simpletippRes->adminName;
        $email->subject = $subject;
        if ($text != null) {
            $email->text = $text;
            $email->html = $this->textToHtml($text);
        }
        return $email;
    }

    private function textToHtml($text)
    {
        $pattern = '@(http(s)?://)(([a-zA-Z0-9])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
        $html = preg_replace($pattern, '<a href="http$2://$3">$0</a>', $text);
        return nl2br($html);
    }
    public static function getNotTippedUser($groupID, $match_id)
    {
        $participantStr = '%s:' . strlen($groupID) . ':"' . $groupID . '"%';

        $result = \Database::getInstance()->prepare("SELECT tblu.*
             FROM tl_member as tblu
             LEFT JOIN tl_simpletipp_tipp AS tblt
             ON ( tblu.id = tblt.member_id AND tblt.match_id = ?)
             WHERE tblt.id IS NULL
             AND CONVERT(tblu.groups USING utf8) LIKE ?
             ORDER BY tblu.lastname")->execute($match_id, $participantStr);

        $arrUser = [];
        while ($result->next()) {
            $arrUser[] = $result->row();
        }
        return $arrUser;
    }

}
