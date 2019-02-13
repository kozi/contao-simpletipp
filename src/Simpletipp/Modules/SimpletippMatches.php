<?php

namespace Simpletipp\Modules;

use Simpletipp\Models\SimpletippPoints;
use Simpletipp\Models\SimpletippTeamModel;
use Simpletipp\Models\SimpletippTippModel;
use Simpletipp\SimpletippModule;

/**
 * Class SimpletippMatches
 *
 * @copyright  Martin Kozianka 2014-2019
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */
class SimpletippMatches extends SimpletippModule
{
    protected $strTemplate = 'simpletipp_matches_default';
    private $formId = 'tl_simpletipp_matches';
    private $matches_filter = null;

    public function generate()
    {
        if (TL_MODE == 'BE') {
            $this->Template = new \BackendTemplate('be_wildcard');
            $this->Template->wildcard = '### SimpletippMatches ###';
            $this->Template->wildcard .= '<br/>' . $this->headline;
            return $this->Template->parse();
        }

        // Search for finished matches which are not marked as finished
        // So maybe the current result is not the final one
        $this->finishedMatches();

        $GLOBALS['TL_JAVASCRIPT'][] = "/system/modules/simpletipp/assets/simpletipp.js";
        return parent::generate();
    }

    protected function compile()
    {

        // Die übergebenen Tipps eintragen
        if (\Input::post('FORM_SUBMIT') === $this->formId) {
            $this->processTipps();
            $this->redirect($this->addToUrl(''));
        }

        // Spiele filtern
        $this->setMatchFilter();

        $this->Template->simpletipp = $this->simpletipp;

        $this->Template->member = \MemberModel::findByPk($this->simpletippUserId);
        $this->Template->isPersonal = $this->isPersonal;

        $this->Template->filter = $this->getMatchFilter();
        $this->Template->matches = $this->getMatches();
        $this->Template->formId = $this->formId;
        $this->Template->action = ampersand(\Environment::get('request'));

        $this->Template->summary = $this->pointSummary;
        $this->Template->messages = $this->getSimpletippMessages();
    }

    private function getMatches()
    {
        $matches = [];

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
            $dbStmt = $this->Database->prepare($sql . $this->matches_filter->stmt . $this->matches_filter->order_by);
            if ($this->matches_filter->limit != 0) {
                $dbStmt->limit($this->matches_filter->limit);
            }

            $result = $dbStmt->execute($params);
        } else {
            $result = $this->Database->prepare($sql . $this->order_by)
                ->execute($this->simpletippUserId, $this->simpletipp->leagueID, $this->simpletippUserId);
        }

        $i = 0;
        $pageObj = \PageModel::findByPk($this->simpletipp_match_page);
        $pageRow = ($pageObj != null) ? $pageObj->row() : null;
        $currentGroup = 0;

        while ($result->next()) {

            $match = (Object) $result->row();

            $match->isStarted = (time() > $result->deadline);
            $match->date = date($GLOBALS['TL_CONFIG']['datimFormat'], $match->deadline);
            $match->date_title = $match->date;

            $pointObj = new SimpletippPoints($this->pointFactors, $match->perfect, $match->difference, $match->tendency);
            $match->points = $pointObj->points;

            $match->cssClass = ($i++ % 2 === 0) ? 'odd' : 'even';
            $match->cssClass .= ($i == $result->numRows) ? ' last' : '';
            $match->cssClass .= ($match->isFinished) ? ' finished' : '';

            if (count($matches) > 0 && $match->groupShort != $currentGroup) {
                $prevMatch = &$matches[(count($matches) - 1)];
                $prevMatch->cssClass .= ($currentGroup != 0) ? ' break' : '';
            }
            $currentGroup = $match->groupShort;

            $match->teamHome = SimpletippTeamModel::findByPk($match->team_h);
            $match->teamAway = SimpletippTeamModel::findByPk($match->team_a);

            if ($pageRow !== null) {
                $alias = strtolower($match->teamHome->three . '-' . $match->teamAway->three);
                $match->matchLink = $this->generateFrontendUrl($pageRow, '/match/' . $alias);
            }

            $match->pointsClass = '';
            if (strlen($match->result) > 0) {
                $match->pointsClass = $pointObj->getPointsClass();
            }

            $matches[] = $match;

            $this->updateSummary($pointObj);
        }

        return $matches;
    }

    private function setMatchFilter()
    {
        $this->matches_filter = new \stdClass;
        $this->matches_filter->type = '';
        $this->matches_filter->stmt = '';
        $this->matches_filter->params = [];
        $this->matches_filter->limit = 0;
        $this->matches_filter->order_by = ' ORDER BY deadline, matches.id ASC';

        // matchgroup filter
        $group = (\Input::get('group') !== null) ? urldecode(\Input::get('group')) : null;
        $date = \Input::get('date');
        $matches = \Input::get('matches');

        if ($group === null && $date === null && $matches === null) {
            $matches = 'current';
        }

        if (strlen($matches) > 0 && $matches == 'current') {
            $this->matches_filter->type = 'current';
            $this->matches_filter->active = true;

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

            $this->matches_filter->stmt = ' AND (matches.groupID = ? OR matches.groupID = ?)';
            return;
        }


        if (strlen($matches) > 0 && $matches == 'all') {
            $this->matches_filter->type = 'all';
            return;
        }

        if (strlen($date) > 0) {
            $this->matches_filter->type = $date;
            $this->matches_filter->active = true;

            $this->matches_filter->stmt = ' AND matches.deadline > ?';

            if (strpos($date, 'last') !== false) {
                $this->matches_filter->stmt = ' AND matches.deadline < ?';
                $this->matches_filter->order_by = ' ORDER BY deadline DESC, matches.id ASC';
            }

            $limit = intval(str_replace(
                array('last-', 'next-'), array('', ''), $date));

            $this->matches_filter->params[] = $this->now;
            $this->matches_filter->limit = $limit;
            return;
        }

        if (strlen($group) > 0) {
            $this->matches_filter->type = $group;
            $this->matches_filter->active = true;
            $this->matches_filter->stmt = ' AND matches.groupName = ?';
            $this->matches_filter->params[] = $group;
            return;
        }

    }

    private function getMatchFilter()
    {
        $tmpl = new \FrontendTemplate('simpletipp_filter');
        $date_filter = [];

        $lastArr = [9];
        $nextArr = [9];

        foreach ($lastArr as $l) {
            $date_filter['last-' . $l] = array(
                'title' => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][0], $l),
                'desc' => sprintf($GLOBALS['TL_LANG']['simpletipp']['last'][1], $l),
                'href' => $this->addToUrl('date=last-' . $l . '&group=&matches='));
        }

        $date_filter['current'] = array(
            'title' => $GLOBALS['TL_LANG']['simpletipp']['current'][0],
            'desc' => $GLOBALS['TL_LANG']['simpletipp']['current'][1],
            'href' => $this->addToUrl('matches=current&date=&group='));

        $date_filter['all'] = array(
            'title' => $GLOBALS['TL_LANG']['simpletipp']['all'][0],
            'desc' => $GLOBALS['TL_LANG']['simpletipp']['all'][1],
            'href' => $this->addToUrl('matches=all&date=&group='));

        foreach ($nextArr as $n) {
            $date_filter['next-' . $n] = array(
                'title' => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][0], $n),
                'desc' => sprintf($GLOBALS['TL_LANG']['simpletipp']['next'][1], $n),
                'href' => $this->addToUrl('date=next-' . $n . '&group=&matches='));
        }


        $i = 0;
        $count = count($date_filter);
        foreach ($date_filter as $key => &$entry) {
            $cssClasses = 'date_filter count' . $count . ' pos' . $i;
            $cssClasses .= ($i == 0) ? ' first' : '';
            $cssClasses .= ($count === $i + 1) ? ' last' : '';
            $entry['selected'] = '';

            if ($this->matches_filter->type == $key) {
                $cssClasses .= ' active';
                $entry['selected'] = ' selected="selected"';
            }
            $entry['cssClass'] = ' class="' . $cssClasses . '"';
            $i++;
        }

        $tmpl->special_filter = $date_filter;

        if ($this->simpletippGroups !== null) {
            $i = 0;
            $count = count($this->simpletippGroups);
            $prefix = ' class="group_filter count' . $count;

            foreach ($this->simpletippGroups as $mg) {
                $cssClass = $prefix . (($i == 0) ? ' first' : '');
                $cssClass .= ($i + 1 == $count) ? ' last %s"' : ' %s"';
                $act = ($this->matches_filter->type == $mg->title);
                $groups[] = array(
                    'title' => $mg->short,
                    'desc' => $mg->title,
                    'href' => $this->addToUrl('group=' . $mg->title . '&date=&matches='),
                    'cssClass' => ($act) ? sprintf($cssClass, 'pos' . $i++ . ' active') : sprintf($cssClass, 'pos' . $i++),
                    'selected' => ($act) ? ' selected="selected"' : '',
                );
            }

            /*
            usort($groups, function($a, $b) {
                // TODO Configure sorting string or int
                return strcmp($a['title'], $b['title']);
                // return ((int) $a['title']) - ((int) $b['title']);            	
            });
            */

            $tmpl->group_filter = $groups;
        }


        // Add count and pos cssClasses
        return $tmpl->parse();
    }

    private function processTipps()
    {
        if (!FE_USER_LOGGED_IN) {
            return false;
        }
        $ids = \Input::post('match_ids');
        $tipps = \Input::post('tipps');

        $to_db = [];

        if (is_array($ids) && is_array($tipps)
            && count($ids) === count($tipps) && count($ids) > 0) {
            for ($i = 0; $i < count($ids); $i++) {
                $id = intval($ids[$i]);
                $tipp = SimpletippTippModel::cleanupTipp($tipps[$i]);

                if (preg_match('/^(\d{1,4}):(\d{1,4})$/', $tipp)) {
                    $to_db[$id] = $tipp;
                }
            }

            $checkTstamp = "SELECT id FROM tl_simpletipp_match WHERE id = ? AND deadline > ?";
            $checkSql = "SELECT id FROM tl_simpletipp_tipp WHERE member_id = ? AND match_id = ?";
            $updateSql = "UPDATE tl_simpletipp_tipp SET tipp = ?,tstamp = ? WHERE id = ?";
            $insertSql = "INSERT INTO tl_simpletipp_tipp(tstamp, member_id, match_id, tipp) VALUES(?, ?, ?, ?)";
            $memberId = $this->User->id;
            $timestamp = time();

            foreach ($to_db as $id => $tipp) {
                $result = $this->Database->prepare($checkTstamp)->execute($id, $timestamp);
                if ($result->numRows > 0) {
                    $result = $this->Database->prepare($checkSql)->execute($memberId, $id);
                    if ($result->numRows > 0) {
                        $this->Database->prepare($updateSql)->execute($tipp, $timestamp, $result->id);
                    } else {
                        $this->Database->prepare($insertSql)->execute($timestamp, $memberId, $id, $tipp);
                    }
                } else {
                    // Remove from array because match already started
                    unset($to_db[$id]);
                }
            }
        }

        if (count(array_keys($to_db)) == 0) {
            // TODO translation
            $message = "Es wurden keine Tipps eingetragen!";
        } else {
            $this->sendTippEmail($to_db);

            if ($this->User->simpletipp_email_confirmation == '1') {
                $message = sprintf($GLOBALS['TL_LANG']['simpletipp']['message_inserted_email'], $this->User->email);
            } else {
                $message = $GLOBALS['TL_LANG']['simpletipp']['message_inserted'];
            }
        }
        $this->addSimpletippMessage($message);
        return true;
    }


    private function sendTippEmail($matches)
    {
        if (!is_array($matches) || count(array_keys($matches)) == 0) {
            // Nothing to send
            return false;
        }

        $result = $this->Database->execute('SELECT * FROM tl_simpletipp_match'
            . ' WHERE id in (' . implode(',', array_keys($matches)) . ')');

        $content = $GLOBALS['TL_LANG']['simpletipp']['email_text'];
        while ($result->next()) {
            $content .= sprintf("%s %s = %s\n",
                date('d.m.Y H:i', $result->deadline),
                $result->title,
                $matches[$result->id]
            );
        }

        $content .= "\n\n--\n" . \Contao\Environment::get("base") . "\n" . $this->simpletipp->adminEmail;
        $subject = sprintf($GLOBALS['TL_LANG']['simpletipp']['email_subject'],
            date('d.m.Y H:i:s'), $this->User->firstname . ' ' . $this->User->lastname);

        // Send to user
        if ($this->User->simpletipp_email_confirmation == '1') {
            $email = new \Email();
            $email->from = $this->simpletipp->adminEmail;
            $email->fromName = $this->simpletipp->adminName;
            $email->subject = SimpletippModule::EMOJI_SOCCER . " " . $subject;
            $email->text = $content;
            $email->replyTo($this->User->email);
            $email->sendTo($this->User->email);
        }

        // Send encoded to admin
        $email = new \Email();
        $email->from = $this->simpletipp->adminEmail;
        $email->fromName = $this->simpletipp->adminName;
        $email->subject = SimpletippModule::EMOJI_SOCCER . " " . $subject;
        $email->text = base64_encode($content);
        $email->replyTo($this->User->email);
        $email->sendTo($this->simpletipp->adminEmail);

        return true;
    }

    private function finishedMatches()
    {
        $matchEnd = time() - $this->simpletipp->matchLength;
        $result = $this->Database->prepare("SELECT * FROM tl_simpletipp_match
               WHERE leagueID = ? AND deadline < ? AND isFinished = ?")
            ->execute($this->simpletipp->leagueID, $matchEnd, 0);

        if ($result->numRows > 0) {
            $this->import('\Simpletipp\SimpletippMatchUpdater', 'SimpletippMatchUpdater');
            $this->SimpletippMatchUpdater->updateSimpletippMatches($this->simpletipp);
        }

    }

} // END class SimpletippMatches
