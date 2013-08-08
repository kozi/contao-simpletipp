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
 * Class Simpletipp
 *
 * @copyright  Martin Kozianka 2011-2013
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
    protected $avatarSql;
    protected $participant_group;

    public function __construct($objModule, $strColumn='main') {
        parent::__construct($objModule, $strColumn);
        $this->loadLanguageFile('tl_simpletipp');
        $this->import('Database');
        $this->import('FrontendUser', 'User');
        $this->now                  = time();
        $this->simpletipp           = SimpletippModel::findByPk($this->simpletipp_group);

        if (TL_MODE !== 'BE') {
            $GLOBALS['TL_CSS'][]        = "/system/modules/simpletipp/assets/simpletipp.css|screen|static";
        }

        if ($this->simpletipp === null) {
            echo 'No simpletipp defined';
            exit;
        }

        $this->simpletippGroups     = Simpletipp::getLeagueGroups($this->simpletipp->leagueID);

        if (Input::get('user')) {
            $userObj = MemberModel::findBy('username', Input::get('user'));
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
        $this->avatarActive         = (in_array('avatar', $this->Config->getActiveModules()));
        $this->avatarSql            = ($this->avatarActive) ? ' tl_member.avatar AS avatar,' : '';

        $factor = explode(',' , $this->simpletipp_factor);
        $this->pointFactors = new stdClass;
        $this->pointFactors->perfect    = $factor[0];
        $this->pointFactors->difference = $factor[1];
        $this->pointFactors->tendency   = $factor[2];

        $this->pointSummary = (Object) array('points' => 0, 'perfect'  => 0, 'difference' => 0, 'tendency' => 0);

    }

    protected static function getGroupMember($groupID, $complete = false, $order = '') {
        $member         = array();
        $participantStr = '%s:'.strlen($groupID).':"'.$groupID.'"%';
        $keys           = ($complete) ? '*' : 'id';

        $result = \Database::getInstance()->prepare("SELECT ".$keys." FROM tl_member WHERE groups LIKE ? ".$order)
            ->execute($participantStr);
        while($result->next()) {
            $member[$result->id] = ($complete) ? (Object) $result->row() : $result->id;
        }
        return $member;
    }

	protected function initSimpletipp() {
		if ($this->simpletipp_group) {

			$result = $this->Database->prepare("SELECT leagueObject, participant_group FROM tl_simpletipp
				 WHERE id =  ?")->execute($this->simpletipp_group);

			if ($result->numRows) {
				$this->league            = unserialize($result->leagueObject);
				$this->participant_group = $result->participant_group;
			} else {
				return false;
			}

            $result = $this->Database->prepare("SELECT DISTINCT groupID, groupName
					FROM tl_simpletipp_match WHERE leagueID = ?
				    ORDER BY groupID")->execute($this->simpletipp->leagueID);

			$this->groups = array();
            while($result->next()) {

                var_dump($result->groupName);

				$short = intval($result->groupName);
				if ($short == 0) {
					$mg    = explode(". ", $result->groupName);
					$short = $mg[0];
				}

				$this->groups[$result->groupID] = (Object) array(
						'title' => $result->groupName,
						'short' => $short);
			}
		}
	}

    protected function updateSummary($pointObj) {
        $this->pointSummary->points     += $pointObj->points;
        $this->pointSummary->perfect    += $pointObj->perfect;
        $this->pointSummary->difference += $pointObj->difference;
        $this->pointSummary->tendency   += $pointObj->tendency;
    }

} // END class Simpletipp
