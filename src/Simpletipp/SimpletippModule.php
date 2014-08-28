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

use \Simpletipp\Models\SimpletippModel;

/**
 * Class Simpletipp
 *
 * @copyright  Martin Kozianka 2011-2014
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

abstract class SimpletippModule extends \Module {
    protected $now;
    protected $simpletipp;
    protected $simpletippGroups;


    protected $simpletippUserId = null;
    protected $isPersonal       = false;

    protected $pointFactors;
    protected $pointSummary;

    protected $factorDifference;
    protected $factorTendency;

    protected $avatarActive = false;
    protected $avatarSql;
    protected $avatarFallback;

    protected $participant_group;

    protected static $cache_key_prefix      = 'simpletipp';
    protected static $cache_key_suffix      = '.json';
    protected static $cache_key_highscore   = 'highscore';
    protected static $cache_key_bestof      = 'bestof';
    protected static $cache_key_points      = 'points';
    protected static $cache_key_special     = 'special';

    public function __construct($objModule = null, $strColumn='main') {
        global $objPage;

        if ($objModule !== null) {
            parent::__construct($objModule, $strColumn);
        }

        $this->loadLanguageFile('tl_simpletipp');
        $this->import('Database');
        $this->import('FrontendUser', 'User');
        $this->now = time();

        // Get simpletipp_group from root page
        $objRootPage = \PageModel::findByPk($objPage->rootId);
        $this->setSimpletipp($objRootPage->simpletipp_group);

        if (TL_MODE !== 'BE') {
            $GLOBALS['TL_CSS'][] = "/system/modules/simpletipp/assets/simpletipp.css||static";
            $GLOBALS['TL_CSS'][] = "/system/modules/simpletipp/assets/simpletipp-statistics.css||static";
        }

        if (\Input::get('user')) {
            $userObj = \MemberModel::findBy('username', \Input::get('user'));
            if ($userObj != null) {
                $this->simpletippUserId = $userObj->id;
                $_SESSION[Simpletipp::$SIMPLETIPP_USER_ID] = $this->simpletippUserId;
                $this->redirect($this->addToUrl('user='));
            }
        }
        if($this->simpletippUserId == null) {
            $this->simpletippUserId = $_SESSION[Simpletipp::$SIMPLETIPP_USER_ID];
            if ($this->simpletippUserId == null) {
                $this->simpletippUserId = $this->User->id;

            }
        }


        $this->isPersonal           = ($this->simpletippUserId === $this->User->id);

        if ($this->Config) {
            $this->avatarActive         = (in_array('avatar', $this->Config->getActiveModules()));
            $this->avatarSql            = ($this->avatarActive) ? ' tl_member.avatar AS avatar,' : '';
        }

        if ($this->avatarActive) {
            $fileObj = \FilesModel::findByPk($GLOBALS['TL_CONFIG']['avatar_fallback_image']);
            $this->avatarFallback = $fileObj->path;
        }


    }

    public function setSimpletipp($simpletippId) {
        $this->simpletipp       = SimpletippModel::findByPk($simpletippId);
        if($this->simpletipp !== null) {
            $this->simpletippGroups = Simpletipp::getLeagueGroups($this->simpletipp->leagueID);
            $this->pointFactors     = $this->simpletipp->getPointFactors();
            $this->pointSummary     = (Object) array('points' => 0, 'perfect'  => 0, 'difference' => 0, 'tendency' => 0);
        }
    }

    protected function getHighscore($matchgroup = null, $arrMemberIds = null) {



        if ($arrMemberIds != null) {
            $restrictToMember  = " AND tl_member.id in (".implode(',', $arrMemberIds).")";
            $arrParticipantIds = $arrMemberIds;
        } else {
            $restrictToMember  = '';
            $arrParticipantIds = Simpletipp::getGroupMemberIds($this->simpletipp->participant_group);
        }

        $this->i = 1;
        $table   = array();
        $matches = $this->getMatchIds($matchgroup);

        if (count($matches) > 0) {
            $result  = \Database::getInstance()->execute("SELECT *, tl_member.id AS member_id,"
                .$this->avatarSql
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
                ." ORDER BY points DESC, sum_perfect DESC, sum_difference DESC");

            while($result->next()) {
                $table[$result->member_id] = $this->getHighscoreRow($result->row());
            }

        }

        if ($arrParticipantIds !== null) {
            // Jetzt noch die member, die noch nichts getippt haben hinzufügen
            $result = $this->Database->execute("SELECT *, tl_member.id AS member_id FROM tl_member"
            ." WHERE tl_member.id in (".implode(',', $arrParticipantIds).")");
            while($result->next()) {
                if (!array_key_exists($result->member_id, $table)) {
                    $table[$result->member_id] = $this->getHighscoreRow($result->row());
                }
            }
        }
        return $table;
    }


    private function getHighscoreRow($memberRow, $params = '') {
        $row           = (Object) $memberRow;

        $row->avatar    = ($row->avatar != null) ? $row->avatar : $this->avatarFallback;
        $row->cssClass  = (($this->i % 2 === 0 ) ? 'odd':'even') . ' pos'.$this->i++;
        $row->cssClass .= ($row->username == $this->User->username) ? ' current' : '';

        $pageModel = \PageModel::findByPk($this->simpletipp_matches_page);
        if ($pageModel !== null) {
            $row->memberLink = self::generateFrontendUrl($pageModel->row(), '/user/'.$row->username.$params);
        }
        return $row;
    }

    private function getMatchIds($matchgroup = null) {
        $matches = array();
        $where   = ($matchgroup !== null) ? ' WHERE leagueID = ? AND groupName = ?' : ' WHERE leagueID = ?';

        if (is_array($matchgroup)) {
            $where   = " WHERE leagueID = ? AND groupName IN ('".implode("','", $matchgroup)."')";;
        }

        $result  = $this->Database->prepare("SELECT id FROM tl_simpletipp_match".$where)
            ->execute($this->simpletipp->leagueID, $matchgroup);

        while($result->next()) {
            $matches[] = $result->id;
        }
        return $matches;

    }

    protected function updateSummary($pointObj) {
        $this->pointSummary->points     += $pointObj->points;
        $this->pointSummary->perfect    += $pointObj->perfect;
        $this->pointSummary->difference += $pointObj->difference;
        $this->pointSummary->tendency   += $pointObj->tendency;
    }


    protected function cachedResult($key, $data = null, $cleanEntries = false) {
        $fn = static::$cache_key_prefix.'_'.$key.'_'.$this->simpletipp->id
            .'_'.$this->simpletipp->lastChanged.static::$cache_key_suffix;
        $objFile = new \File('system/tmp/'.$fn, true);

        if ($data !== null) {

            if ($cleanEntries) {
                foreach ($data as &$item) {
                    Simpletipp::cleanItem($item);
                }
            }
            $objFile->write(serialize($data));
            $objFile->close();
            return null;
        }

        if (!$objFile->exists()) {
            return null;
        }
        return unserialize($objFile->getContent());
    }


} // END class Simpletipp
