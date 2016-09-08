<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2016 Leo Feyer
 *
 * PHP version 5
 *
 * @package    Simpletipp
 * @author     Martin Kozianka <martin@kozianka.de> 
 * @copyright  2014-2016 Martin Kozianka <http://kozianka.de/>
 * @license    LGPL www.gnu.org/licenses/lgpl.html
 * @filesource
 */

namespace Simpletipp;

use \Simpletipp\Models\SimpletippModel;

/**
 * Class Simpletipp
 *
 * @package   Controller
 * @author    Martin Kozianka <martin@kozianka.de> 
 * @copyright 2014-2016 Martin Kozianka <http://kozianka.de/>
 */

abstract class SimpletippModule extends \Module
{
    const SIMPLETIPP_USER_ID = 'SIMPLETIPP_USER_ID';

    protected $now;
    protected $simpletipp;
    protected $simpletippGroups;


    protected $simpletippUserId = null;
    protected $isPersonal       = false;

    protected $pointFactors;
    protected $pointSummary;

    protected $factorDifference;
    protected $factorTendency;

    protected $participant_group;

    protected static $cache_key_prefix      = 'simpletipp';
    protected static $cache_key_suffix      = '.json';
    protected static $cache_key_highscore   = 'highscore';
    protected static $cache_key_bestof      = 'bestof';
    protected static $cache_key_points      = 'points';
    protected static $cache_key_special     = 'special';
    protected static $cache_key_bestTeams   = 'bestTeams';
    protected static $cache_key_bestMatches = 'bestMatches';
    protected static $cache_key_ranking     = 'ranking';
    protected static $cache_key_notTipped   = 'notTipped';

    public function __construct($objModule = null, $strColumn='main')
    {
        global $objPage;

        if ($objModule !== null)
        {
            parent::__construct($objModule, $strColumn);
        }

        $this->loadLanguageFile('tl_simpletipp');
        $this->import('Database');
        $this->import('FrontendUser', 'User');
        $this->now = time();

        // Get simpletipp_group from root page
        $objRootPage = \PageModel::findByPk($objPage->rootId);
        $this->setSimpletipp($objRootPage->simpletipp_group);

        if (TL_MODE !== 'BE')
        {
            $GLOBALS['TL_CSS'][] = "/system/modules/simpletipp/assets/simpletipp.css||static";
            $GLOBALS['TL_CSS'][] = "/system/modules/simpletipp/assets/simpletipp-statistics.css||static";
        }

        if (\Input::get('user'))
        {
            $userObj = \MemberModel::findBy('username', \Input::get('user'));
            if ($userObj != null)
            {
                $this->simpletippUserId = $userObj->id;
                $_SESSION[self::SIMPLETIPP_USER_ID] = $this->simpletippUserId;
                $this->redirect($this->addToUrl('user='));
            }
        }
        if($this->simpletippUserId == null)
        {
            $this->simpletippUserId = $_SESSION[self::SIMPLETIPP_USER_ID];
            if ($this->simpletippUserId == null)
            {
                $this->simpletippUserId = $this->User->id;

            }
        }

        $this->isPersonal = ($this->simpletippUserId === $this->User->id);
    }

    public function setSimpletipp($simpletippId)
    {
        $this->simpletipp = SimpletippModel::findByPk($simpletippId);
        if($this->simpletipp !== null)
        {
            $this->simpletippGroups = SimpletippModel::getLeagueGroups($this->simpletipp->leagueID);
            $this->pointFactors     = $this->simpletipp->getPointFactors();
            $this->pointSummary     = (Object) ['points' => 0, 'perfect'  => 0, 'difference' => 0, 'tendency' => 0];
        }
    }

    public function getHighscore($matchgroup = null, $arrMemberIds = null)
    {
        if ($arrMemberIds != null)
        {
            $restrictToMember  = " AND tl_member.id in (".implode(',', $arrMemberIds).")";
            $arrParticipantIds = $arrMemberIds;
        }
        else
        {
            $restrictToMember  = '';
            $arrParticipantIds = $this->getGroupMemberIds($this->simpletipp->participant_group);
        }

        $this->i = 1;
        $table   = [];
        $matches = $this->getMatchIds($matchgroup);

        if (count($matches) > 0)
        {
            $result  = \Database::getInstance()->execute("SELECT *, tl_member.id AS member_id,"
                ." SUM(tendency) AS sum_tendency,"
                ." SUM(difference) AS sum_difference,"
                ." SUM(perfect) AS sum_perfect,"
                ." SUM(wrong) AS sum_wrong,"
                ." SUM(perfect*".$this->pointFactors->perfect
                ." + difference*".$this->pointFactors->difference
                ." + tendency*".$this->pointFactors->tendency
                .") AS points"
                ." FROM tl_simpletipp_tipp AS tblTipp, tl_member"
                ." WHERE tblTipp.member_id = tl_member.id"
                ." AND tblTipp.match_id in (".implode(',', $matches).")"
                .$restrictToMember
                ." GROUP BY tl_member.id"
                // Erst PUNKTE dann TENDENZEN dann DIFFERENZEN dann RICHTIGE
                ." ORDER BY points DESC, sum_tendency DESC, sum_difference DESC, sum_perfect DESC");

            while($result->next())
            {
                $table[$result->member_id] = $this->getHighscoreRow($result->row());
            }

            // Add points from questions (do not show in matchgroup highscores)
            if ($matchgroup === null) 
            {
                
                $arrQuestionPoints = $this->getQuestionHighscore();
                if (is_array($arrQuestionPoints) && count($arrQuestionPoints) > 0)
                {
                    // Fill table with points
                    foreach($arrQuestionPoints as $qEntry)
                    {
                        if (array_key_exists($qEntry->memberId, $table)) 
                        {
                            $rowObj = &$table[$qEntry->memberId];
                            $rowObj->points = $rowObj->points + $qEntry->questionPoints;
                            $rowObj->questionPoints = $qEntry->questionPoints;
                            $rowObj->questionDetails = $qEntry->questionDetails;                             
                        }
                    }

                    uasort($table, function($a, $b) {
                        $intCmp = $b->points - $a->points; 
                        if($intCmp !== 0) return $intCmp;

                        $intCmp = $b->sum_tendency - $a->sum_tendency;
                        if($intCmp !== 0) return $intCmp;
                        
                        $intCmp = $b->sum_difference - $a->sum_difference;
                        if($intCmp !== 0) return $intCmp;

                        $intCmp = $b->sum_perfect - $a->sum_perfect;
                        if($intCmp !== 0) return $intCmp;

                        $intCmp = $b->questionPoints - $b->questionPoints;
                        return $intCmp;
                    });

                    // recalculate cssClass attribute
                    $i = 1;
                    foreach($table as $row) {
                        $row->cssClass  = (($i % 2 === 0 ) ? 'odd':'even') . ' pos'.$i++;
                        $row->cssClass .= ($row->username == $this->User->username) ? ' current' : '';
                    }
                    
                    
                     
                }
            }


        }

        if ($arrParticipantIds !== null)
        {
            // Jetzt noch die member, die noch nichts getippt haben hinzufügen
            $result = $this->Database->execute("SELECT *, tl_member.id AS member_id FROM tl_member"
            ." WHERE tl_member.id in (".implode(',', $arrParticipantIds).")");
            while($result->next())
            {
                if (!array_key_exists($result->member_id, $table))
                {
                    $table[$result->member_id] = $this->getHighscoreRow($result->row());
                }
            }
        }
        return $table;
    }

    public function getQuestionHighscore() 
    {
        $arrResult = [];
        $result = $this->Database->prepare("SELECT
            tl_simpletipp_question.id as questionId, tl_simpletipp_question.question,
            tl_simpletipp_question.points, tl_simpletipp_question.results,
            tl_simpletipp_answer.id as answerId, tl_simpletipp_answer.answer,
            tl_member.id as memberId, tl_member.*
            FROM tl_simpletipp_answer,tl_simpletipp_question, tl_member
            WHERE tl_simpletipp_answer.pid 
            IN(SELECT id FROM tl_simpletipp_question WHERE tl_simpletipp_question.pid = ?)
            AND tl_simpletipp_question.id = tl_simpletipp_answer.pid
            AND tl_member.id = tl_simpletipp_answer.member"
        )->execute($this->simpletipp->id);



        while ($result->next())
        {
            $row = $result->row();
            $row['results'] = unserialize($row['results']);
            if(!array_key_exists($result->memberId, $arrResult))
            {
                $m = (object) $row; 
                $m->questionPoints = 0;
                $m->questionDetails = []; 
                $arrResult[$result->memberId] = $m;
            }
            $memberObj = &$arrResult[$result->memberId];
            if (in_array($row['answer'], $row['results']))
            {
                $memberObj->questionPoints = $memberObj->questionPoints + $row['points'];
                $memberObj->questionDetails[] = (object) [
                    'question' => $row['question'],
                    'answer'   => $row['answer'],
                    'points'   => $row['points'],
                ];
            };
        }
        return $arrResult;
    }

    private function getHighscoreRow($memberRow, $params = '')
    {
        $row            = (Object) $memberRow;
        $row->cssClass  = (($this->i % 2 === 0 ) ? 'odd':'even') . ' pos'.$this->i++;
        $row->cssClass .= ($row->username == $this->User->username) ? ' current' : '';

        $pageModel = \PageModel::findByPk($this->simpletipp_matches_page);
        if ($pageModel !== null)
        {
            $row->memberLink = self::generateFrontendUrl($pageModel->row(), '/user/'.$row->username.$params);
        }
        return $row;
    }

    private function getMatchIds($matchgroup = null)
    {
        $matches = [];
        $where   = ($matchgroup !== null) ? ' WHERE leagueID = ? AND groupName = ?' : ' WHERE leagueID = ?';

        if (is_array($matchgroup))
        {
            $where   = " WHERE leagueID = ? AND groupName IN ('".implode("','", $matchgroup)."')";;
        }

        $result  = $this->Database->prepare("SELECT id FROM tl_simpletipp_match".$where)
            ->execute($this->simpletipp->leagueID, $matchgroup);

        while($result->next())
        {
            $matches[] = $result->id;
        }
        return $matches;

    }

    protected function updateSummary($pointObj)
    {
        $this->pointSummary->points     += $pointObj->points;
        $this->pointSummary->perfect    += $pointObj->perfect;
        $this->pointSummary->difference += $pointObj->difference;
        $this->pointSummary->tendency   += $pointObj->tendency;
    }


    /**
     * @param $groupID
     * @param string $order
     * @return \MemberModel|\Model\Collection
     */
    protected function getGroupMember($groupID, $order = 'tl_member.lastname ASC, tl_member.firstname ASC')
    {
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';
        $objMembers     = \MemberModel::findBy(
                                ['tl_member.groups LIKE ?'],
                                $participantStr,
                                ['order' => $order]
                          );
        return $objMembers;
    }

    protected function getGroupMemberIds($groupID)
    {
        $arrIds     = [];
        $objMembers = $this->getGroupMember($groupID);
        if ($objMembers!== null) {
            foreach ($objMembers as $objMember) {
                $arrIds[] = $objMember->id;
            }
        }
        return $arrIds;
    }
    

    protected function cache($key, $data = null, $cleanEntries = false)
    {
        $fn = static::$cache_key_prefix.'_'.$key.'_'.$this->simpletipp->id
            .'_'.$this->simpletipp->lastChanged.static::$cache_key_suffix;
        $objFile = new \File('system/tmp/'.$fn, true);

        if ($data !== null)
        {
            if ($cleanEntries)
            {
                foreach ($data as &$item)
                {
                    $this->cleanItem($item);
                }
            }
            $objFile->write(serialize($data));
            $objFile->close();
            return null;
        }

        if (!$objFile->exists())
        {
            return null;
        }
        return unserialize($objFile->getContent());
    }

    private function cleanItem(&$item)
    {
        if (is_object($item))
        {
            unset($item->password);
            unset($item->session);
            unset($item->autologin);
            unset($item->activation);
            foreach($item as $property => $value)
            {
                if (is_string($value) && strlen($value) == 0)
                {
                    unset($item->$property);
                }
            }
        }
        if (is_array($item))
        {
            foreach($item as $key => $value)
            {
                if (is_string($value) && strlen($value) == 0)
                {
                    unset($item[$key]);
                }
            }
        }
    }



    protected function getSimpletippMessages()
    {
        if (!is_array($_SESSION['TL_SIMPLETIPP_MESSAGE']))
        {
            $_SESSION['TL_SIMPLETIPP_MESSAGE'] = [];
        }

        if (count($_SESSION['TL_SIMPLETIPP_MESSAGE']) == 0)
        {
            return '';
        }

        $messages = '';
        foreach($_SESSION['TL_SIMPLETIPP_MESSAGE'] AS $message)
        {
            $messages .= sprintf("	<div class=\"message\">%s</div>\n", $message);
        }
        // Reset
        $_SESSION['TL_SIMPLETIPP_MESSAGE'] = [];
        return sprintf("\n<div class=\"simpletipp_messages\">\n%s</div>\n", $message);
    }

    protected function addSimpletippMessage($message)
    {
        if (!is_array($_SESSION['TL_SIMPLETIPP_MESSAGE']))
        {
            $_SESSION['TL_SIMPLETIPP_MESSAGE'] = [];
        }
        $_SESSION['TL_SIMPLETIPP_MESSAGE'][] = $message;
    }


} // END class setSimpletipp
