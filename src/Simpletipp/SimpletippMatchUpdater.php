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


use Contao\Config;
use Contao\Dbafs;
use Contao\File;
use Contao\Input;
use Simpletipp\Models\SimpletippModel;
use Simpletipp\Models\SimpletippMatchModel;
use Simpletipp\Models\SimpletippTeamModel;

/**
 * Class SimpletippMatchUpdater
 *
 * Provide methods to import matches
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippMatchUpdater extends \Backend
{
    private static $lookupLockSeconds     = 180;
    const RESULTTYPE_ENDERGEBNIS   = 'ENDERGEBNIS';
    const RESULTTYPE_HALBZEIT      = 'HALBZEIT';
    const RESULTTYPE_VERLAENGERUNG = 'VERLAENGERUNG';
    const RESULTTYPE_11METER       = '11METER';

    private $oldb   = null;
    private $folder = null;

    public function __construct()
    {
        $this->oldb   = OpenLigaDB::getInstance();
        $this->folder = Config::get('uploadPath').'/simpletipp';
        if (!file_exists($this->folder))
        {
            File::putContent($this->folder.'/.simpletipp', $this->now);
        }
        parent::__construct();
    }

    // TODO Typen auswählbar machen in der Tipprunde
    // TODO Prioritäten festlegen d.h. die resultTypes in eine Reihenfolge bringen
    public static $arrResultTypes = [
        1 => self::RESULTTYPE_HALBZEIT,
        2 => self::RESULTTYPE_ENDERGEBNIS,
        3 => self::RESULTTYPE_VERLAENGERUNG,
        4 => self::RESULTTYPE_11METER,
    ];

    public function updateMatches()
    {
        $id     = (\Input::get('id') !== null) ? intval(\Input::get('id')) : 0;

        if ($id === 0)
        {
            $objSimpletippCollection =  SimpletippModel::findAll();
            foreach($objSimpletippCollection as $objSimpletipp)
            {
                $message = $this->updateSimpletippMatches($objSimpletipp, false);
                \System::log(strip_tags($message), 'SimpletippCallbacks updateMatches()', TL_INFO);
            }
        }
        else
        {
            $objSimpletipp = SimpletippModel::findByPk($id);
            $message       = $this->updateSimpletippMatches($objSimpletipp, true);
            \Message::add($message, TL_INFO);

            if ('update' === Input::get('key'))
            {
                $this->redirect(\Environment::get('script').'?do=simpletipp_group');
            }

        }
    }

    public function updateSimpletippMatches(&$simpletippObj, $forceUpdate = false)
    {
        $leagueInfos = unserialize($simpletippObj->leagueInfos);

        if(!is_array($leagueInfos))
        {
            return 'No league set.';
        }

        if ($this->lastLookupOnlySecondsAgo($simpletippObj))
        {
            $message = sprintf(
                'Letzte Aktualisierung für <strong>%s</strong> erst vor %s Sekunden.',
                $leagueInfos['name'],
                (time() - $simpletippObj->lastLookup));
            return $message;
        }

        $this->oldb->setLeague($leagueInfos);

        $openligaLastChanged    = strtotime($this->oldb->getLastLeagueChange());
        $simpletippLastChanged  = intval($simpletippObj->lastChanged);

        if ($forceUpdate || $simpletippLastChanged != $openligaLastChanged || SimpletippMatchModel::countBy('leagueID', $simpletippObj->leagueID) === 0)
        {
            $matchIDs = $this->updateLeagueMatches($leagueInfos);
            $this->updateTipps($matchIDs);
            $message = sprintf('Liga <strong>%s</strong> aktualisiert! ', $leagueInfos['name']);
        }
        else
        {
            $message = sprintf('Keine Änderungen seit der letzen Aktualisierung in Liga <strong>%s</strong>. ', $leagueInfos['name']);
        }

        $simpletippObj->lastChanged = $openligaLastChanged;
        $simpletippObj->save();

        return $message;
    }

    public function updateTeamTable()
    {
        $objTeams    = SimpletippTeamModel::findAll();
        $arrTeamIds  = [];
        if ($objTeams !== null)
        {
            foreach($objTeams as $objTeam)
            {
                $arrTeamIds[] = intval($objTeam->id);
            }
        }
        $collectionSimpletipp = SimpletippModel::findAll();
        foreach($collectionSimpletipp as $objSimpletipp)
        {

            $leagueInfos = unserialize($objSimpletipp->leagueInfos);

            if(!is_array($leagueInfos))
            {
                break;
            }

            $strLeague = $leagueInfos['shortcut'];
            $this->oldb->setLeague($leagueInfos);

            $arrTeams = $this->oldb->getLeagueTeams();

            foreach($arrTeams as $team)
            {
                if(!in_array($team->teamID, $arrTeamIds))
                {
                    $objTeam          = $this->getTeamModel($team);
                    $objTeam->leagues = $strLeague;
                    $objTeam->save();

                    $arrTeamIds[] = $team->teamID;
                }
                else
                {
                    $objTeam = SimpletippTeamModel::findByPk($team->teamID);
                    if ($objTeam !== null && strpos($objTeam->leagues, $strLeague) === false)
                    {
                        $objTeam->leagues .= ', '.$strLeague;
                        $objTeam->save();
                    }
                }

            }

        }
        // $this->oldb->getLeagueTeams();
    }

    public function calculateTipps() {
        $id = intval(\Input::get('id'));
        if ($id == 0)
        {
            // no id given
            return true;
        }
        $result = $this->Database->prepare(
            "SELECT tl_simpletipp_match.*, tl_simpletipp.leagueInfos
            FROM tl_simpletipp, tl_simpletipp_match
            WHERE tl_simpletipp.id = ? AND tl_simpletipp_match.isFinished = ?
            AND tl_simpletipp_match.leagueID = tl_simpletipp.leagueID"
        )->execute($id, '1');

        $leagueInfos = null;
        $match_ids   = [];

        while ($result->next())
        {
            if ($leagueInfos == null)
            {
                $leagueInfos = unserialize($result->leagueInfos);
            }
            $match_ids[] = $result->id;
        }
        $this->updateTipps($match_ids);

        $message = sprintf('Tipps für die Liga <strong>%s</strong> aktualisiert! ', $leagueInfos['name']);
        \Message::add($message, 'TL_INFO');
        $this->redirect(\Environment::get('script').'?do=simpletipp_group');

    }


    private function updateTipps($ids)
    {
        if (count($ids) === 0)
        {
            //  Nothing to do
            return true;
        }

        $result = $this->Database->execute("SELECT id, result FROM tl_simpletipp_match"
            ." WHERE id in (".implode(',', $ids).")");
        while($result->next())
        {
            $match_results[$result->id] = $result->result;
        }

        $result = $this->Database->execute("SELECT id, match_id, tipp FROM tl_simpletipp_tipp"
            ." WHERE match_id in (".implode(',', $ids).")");
        while($result->next())
        {
            $points = Simpletipp::getPoints($match_results[$result->match_id], $result->tipp);

            $this->Database->prepare("UPDATE tl_simpletipp_tipp"
                ." SET perfect = ?, difference = ?, tendency = ?, wrong = ? WHERE id = ?")
                ->execute($points->perfect, $points->difference, $points->tendency, $points->wrong, $result->id);
        }
    }

    private function lastLookupOnlySecondsAgo(&$objSimpletipp)
    {
        $now = time();
        if (($now - $objSimpletipp->lastLookup) < static::$lookupLockSeconds)
        {
            return true;
        }
        $objSimpletipp->lastLookup = $now;
        $objSimpletipp->save();
        return false;
    }


    private function updateLeagueMatches($leagueInfos)
    {
        $this->oldb = OpenLigaDB::getInstance();
        $this->oldb->setLeague($leagueInfos);

        $matches = $this->oldb->getMatches();
        if ($matches === false)
        {
            return false;
        }

        $newMatches = [];

        foreach($matches as $match)
        {
            $tmp          = get_object_vars($match);
            $matchId      = $tmp['matchID'];

            // GroupName
            $arrGroup     = Simpletipp::groupMapper($tmp);

            $results      = self::parseResults($tmp['matchResults']);

            $teamHome      = SimpletippTeamModel::findByPk($tmp['idTeam1']);
            $teamAway      = SimpletippTeamModel::findByPk($tmp['idTeam2']);
            $strTitle      = $tmp['nameTeam1'].' - '.$tmp['nameTeam2'];
            $strTitleShort = $strTitle;
            if ($teamHome !== null && $teamAway !== null)
            {
                $strTitleShort = $teamHome->short.' - '.$teamAway->short;
            }

            $newMatch     = [
                'leagueID'        => $tmp['leagueID'],
                'groupID'         => $arrGroup['id'],
                'deadline'        => strtotime($tmp['matchDateTimeUTC']),
                'title'           => $strTitle,
                'title_short'     => $strTitleShort,

                'groupName'       => $arrGroup['name'],
                'groupName_short' => $arrGroup['short'],

                'team_h'          => $tmp['idTeam1'],
                'team_a'          => $tmp['idTeam2'],

                'isFinished'      => $tmp['matchIsFinished'],
                'lastUpdate'      => strtotime($tmp['lastUpdate']),
                'result'          => $results[self::RESULTTYPE_ENDERGEBNIS],
                'resultFirst'     => $results[self::RESULTTYPE_HALBZEIT],
            ];
            $newMatches[$matchId] = $newMatch;
        }

        $arrMatchIds = array_keys($newMatches);
        // update existing matches
        foreach(SimpletippMatchModel::findMultipleByIds($arrMatchIds) as $objMatch)
        {
            $matchId = intval($objMatch->id);
            if (array_key_exists($objMatch->id, $newMatches))
            {
                foreach($newMatches[$matchId] as $key => $value)
                {
                    $objMatch->$key = $value;
                }
                $objMatch->save();
                unset($newMatches[$matchId]);
            }
        }

        // add new matches
        foreach($newMatches as $matchId => $arrMatch)
        {
            $objMatch       = new SimpletippMatchModel();
            $arrMatch['id'] = $matchId;

            $objMatch->setRow($arrMatch);
            $objMatch->save();
        }

        return $arrMatchIds;
    }

    private static function parseResults($matchResults)
    {
        // Init result array
        $arrResults = [];
        foreach (static::$arrResultTypes as $type)
        {
            $arrResults[$type] = '';
        }

        // Fill result array
        if ($matchResults->matchResult !== null)
        {
            foreach ($matchResults->matchResult as $res)
            {
                $key = static::$arrResultTypes[$res->resultTypeId];
                $arrResults[$key] = $res->pointsTeam1.Simpletipp::$TIPP_DIVIDER.$res->pointsTeam2;
            }
        }

        return $arrResults;
    }

    private function getTeamModel($team)
    {
        $objTeam          = new SimpletippTeamModel();
        $objTeam->id      = $team->teamID;
        $objTeam->tstamp  = time();
        $objTeam->name    = $team->teamName;

        if (is_array($GLOBALS['simpletipp']['teamData']) && array_key_exists($team->teamID, $GLOBALS['simpletipp']['teamData']))
        {
            $arr = $GLOBALS['simpletipp']['teamData'][$team->teamID];
            $objTeam->short    = $arr[0];
            $objTeam->alias    = standardize($objTeam->short);
            $objTeam->three    = $arr[1];

            if (count($arr[2]) > 0)
            {
                $ext = pathinfo ($arr[2], PATHINFO_EXTENSION);
                $fn  = Config::get('uploadPath').'/simpletipp/'.standardize($team->teamName).'.'.$ext;
                if (!file_exists($fn))
                {
                    file_put_contents(TL_ROOT.'/'.$fn, fopen($arr[2], 'r'));
                }
                $filesModel = Dbafs::addResource($fn);

                if ($filesModel !== null)
                {
                    $objTeam->logo = $filesModel->uuid;
                }
            }
        }
        return $objTeam;
    }
}
