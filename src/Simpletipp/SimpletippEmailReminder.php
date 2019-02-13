<?php

namespace Simpletipp;

use Contao\Email;
use Simpletipp\Models\SimpletippMatchModel;
use Simpletipp\Models\SimpletippModel;
use Telegram\Bot\Api;

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
        $telegram = null;
        $simpletippModel = SimpletippModel::findAll();
        $hours = 24;
        $now = time();
        $arrMessages = [];
        foreach ($simpletippModel as $simpletippObj) {
            $telegram = (strlen($simpletippObj->telegram_bot_key) > 0) ? new Api($simpletippObj->telegram_bot_key) : null;
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

                $teamH = $match->getRelated('team_h');
                $teamA = $match->getRelated('team_a');
                $matchDate = \Date::parse('d.m.Y H:i', $match->deadline);

                $userNamesArr = [];
                $emailSubject = sprintf($GLOBALS['TL_LANG']['simpletipp']['reminder_email_subject'],
                    $match->title_short . " (" . $matchDate . ")");
                $emailText = sprintf($GLOBALS['TL_LANG']['simpletipp']['reminder_email_text'],
                    $hours, $match->title, $matchDate, $matchesPageUrl);
                $telegramText = sprintf($GLOBALS['TL_LANG']['simpletipp']['reminder_telegram_text'],
                    $hours, $match->title, $matchDate, $matchesPageUrl);

                $htmlLogos = "";
                $commonStyle = "font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;";

                $teamH = $match->getRelated('team_h');
                $teamA = $match->getRelated('team_a');
                $htmlLogos .= \Image::getHtml(\Image::get($teamH->logoPath(), 100, 100, 'box'), $teamH->name, 'style="margin:20px 10px 20px 0;"');
                $htmlLogos .= \Image::getHtml(\Image::get($teamA->logoPath(), 100, 100, 'box'), $teamA->name, 'style="margin:20px 0 20px 10px;"');

                $emailHtml = '<div style="' . $commonStyle . '">' . str_replace("##LOGOS##", $htmlLogos, $this->textToHtml($emailText)) . '</div>';
                $emailText = str_replace("##LOGOS##", "", $emailText);

                if ($this->isDebug) {
                    echo '<div style="font-family:sans-serif;">';
                    echo "<h2>emailSubject</h2>";
                    echo "<pre style='border:1px solid #333;width:560px;padding:10px;'>" . $emailSubject . "</pre>";
                    echo "<h2>emailHtml</h2>";
                    echo "<div style='border:1px solid #333;width:560px;padding:10px;'>" . str_replace('src="assets', 'src="/assets', $emailHtml) . "</div>";
                    echo "<h2>emailText</h2>";
                    echo "<pre style='border:1px solid #333;width:560px;padding:10px;'>" . $emailText . "</pre>";
                    echo "<h2>telegramText</h2>";
                    echo "<pre style='border:1px solid #333;width:560px;padding:10px;'>" . $telegramText . "</pre>";

                    $email = $this->generateEmailObject($simpletippObj, $emailSubject, $emailText, $emailHtml);
                    $email->sendTo($simpletippObj->adminEmail);
                }

                $emailCount = 0;
                foreach (static::getNotTippedUser($simpletippObj->participant_group, $match->id) as $u) {
                    $telegramId = $u['telegram_chat_id'];
                    $emailSent = '';
                    if ($u['simpletipp_email_reminder'] == '1') {
                        $email = $this->generateEmailObject($simpletippObj, $emailSubject, $emailText, $emailHtml);

                        if ($this->isDebug) {
                            $arrMessages[] = $u['email'] . ", " . $emailSubject;
                        } else {
                            $email->sendTo($u['email']);
                        }

                        $emailSent = '@ ';
                        $emailCount++;
                    }
                    if ($telegram != null && $telegramId && strlen($telegramId) > 0 && "martin@kozianka.de" == $u['email']) {
                        // var_dump([$u['email'], $telegramId]);

                        $response = $telegram->sendMessage([
                            "chat_id" => $telegramId,
                            "text" => $telegramText,
                        ]);
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
            echo "<ul>";
            foreach ($arrMessages as $m) {
                echo "<li><p>" . $m . "</p></li>";
            }
            echo "</ul>";
            die("</div>");
        }

        if ('reminder' === \Input::get('key')) {
            foreach ($arrMessages as $m) {
                \Message::addInfo($m);
            }
            $this->redirect($this->getReferer() . "?do=simpletipp_group");
        }
    }

    private function generateEmailObject($simpletippRes, $subject, $text = null, $html = null)
    {
        $email = new Email();
        $email->from = $simpletippRes->adminEmail;
        $email->fromName = $simpletippRes->adminName;
        $email->subject = $subject;
        ($text != null) && ($email->text = $text);
        ($html != null) && ($email->html = $html);
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
