<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2018 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2018 <http://kozianka.de/>
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
use Simpletipp\Models\SimpletippTippModel;

/**
 * Class SimpletippMatchUpdater
 *
 * Provide methods to import matches
 * @copyright  Martin Kozianka 2014-2018
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippMatchUpdater extends \Backend
{
    const GROUPNAME_VORRUNDE          = 'Vorrunde';

    private static $lookupLockSeconds = 0;

    private $resultTypeIdFirst;
    private $resultTypeIdFinal;

    private $folder = null;

    public function __construct()
    {
        $this->folder = Config::get('uploadPath').'/simpletipp';
        if (!file_exists($this->folder))
        {
            File::putContent($this->folder.'/.simpletipp', $this->now);
        }
        parent::__construct();
    }

    public function updateMatches()
    {
        $id = (\Input::get('id') !== null) ? intval(\Input::get('id')) : 0;
        if ($id === 0)
        {
            $objSimpletippCollection = SimpletippModel::findAll();
            $now = time();
            foreach($objSimpletippCollection as $objSimpletipp)
            {
                // Only update current leagues (lastChange is not older than 1 year (31557600 seconds))
                if ($objSimpletipp->lastChanged == 0 || $objSimpletipp->lastChanged + 31557600 > $now)
                {
                    $message = $this->updateSimpletippMatches($objSimpletipp);
                    \System::log(strip_tags($message), 'SimpletippCallbacks updateMatches()', TL_INFO);
                }

            }
        }
        else
        {
            $objSimpletipp = SimpletippModel::findByPk($id);
            $message       = $this->updateSimpletippMatches($objSimpletipp);
            \Message::add($message, TL_INFO);

            if ('update' === Input::get('key'))
            {
                $this->redirect($this->getReferer());
            }

        }
    }

    public function updateSimpletippMatches(&$simpletippObj)
    {
        if($simpletippObj->leagueID == 0)
        {
            return 'No league set.';
        }

        if ($this->lastLookupOnlySecondsAgo($simpletippObj))
        {
            $message = sprintf(
                'Letzte Aktualisierung für <strong>%s</strong> erst vor %s Sekunden.',
                $simpletippObj->leagueName,
                (time() - $simpletippObj->lastLookup));
            return $message;
        }

        $matchIDs = $this->updateLeagueMatches($simpletippObj);
        $this->updateTipps($matchIDs);
        $message = sprintf('Liga <strong>%s</strong> aktualisiert! ', $simpletippObj->leagueName);

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

        // Reset league information on all teams
        $collectionTeam = SimpletippTeamModel::findAll();
        foreach($collectionTeam as $objTeam)
        {
            $objTeam->leagues = "";
            $objTeam->save();
        }


        $collectionSimpletipp = SimpletippModel::findAll();
        foreach($collectionSimpletipp as $objSimpletipp)
        {
            if($objSimpletipp->leagueID == 0)
            {
                continue;
            }
            
            $strLeague = $objSimpletipp->leagueShortcut;
            $arrTeams  = OpenLigaDB::getLeagueTeams($objSimpletipp->leagueShortcut, $objSimpletipp->leagueSaison);

            foreach($arrTeams as $team)
            {
                if(!in_array($team['TeamId'], $arrTeamIds))
                {
                    $objTeam          = $this->getTeamModel($team);
                    $objTeam->leagues = $strLeague;
                    $objTeam->save();

                    $arrTeamIds[] = $team['TeamId'];
                }
                else
                {
                    $objTeam = SimpletippTeamModel::findByPk($team['TeamId']);
                    if ($objTeam !== null && strpos($objTeam->leagues, $strLeague) === false)
                    {
                        $objTeam->leagues .= (strlen($objTeam->leagues) > 0) ? ', '.$strLeague : $strLeague;
                        $objTeam->save();
                    }
                }

            }

        }
    }

    public function calculateTipps() {
        $id = intval(\Input::get('id'));
        if ($id == 0)
        {
            // no id given
            return true;
        }
        $result = $this->Database->prepare(
            "SELECT tl_simpletipp_match.*, tl_simpletipp.leagueName
            FROM tl_simpletipp, tl_simpletipp_match
            WHERE tl_simpletipp.id = ? AND tl_simpletipp_match.isFinished = ?
            AND tl_simpletipp_match.leagueID = tl_simpletipp.leagueID"
        )->execute($id, '1');

        $leagueName = null;
        $match_ids   = [];

        while ($result->next())
        {
            if ($leagueName == null)
            {
                $leagueName = $result->leagueName;
            }
            $match_ids[] = $result->id;
        }
        $this->updateTipps($match_ids);

        $message = sprintf('Tipps für die Liga <strong>%s</strong> aktualisiert! ', $leagueName);
        \Message::add($message, 'TL_INFO');
        $this->redirect($this->getReferer()."?do=simpletipp_group");                
    }


    private function updateTipps($ids)
    {
        if (!is_array($ids) || count($ids) === 0)
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
            $points = SimpletippTippModel::getPoints($match_results[$result->match_id], $result->tipp);

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


    private function updateLeagueMatches(&$simpletippObj)
    {
        $matches = OpenLigaDB::getMatches($simpletippObj->leagueShortcut, $simpletippObj->leagueSaison);

        if ($matches === false)
        {
            return false;
        }

        // Keys aus Konfiguration lesen 
        $this->resultTypeIdFirst = $simpletippObj->resultTypeIdFirst;
        $this->resultTypeIdFinal = $simpletippObj->resultTypeIdFinal;

        $lastChange = "";
        $newMatches = [];

        foreach($matches as $match)
        {
            $matchId      = $match['MatchID'];

            $resultObj    = $this->parseResults($match);

            $teamHome      = SimpletippTeamModel::findByPk($match['Team1']['TeamId']);
            $teamAway      = SimpletippTeamModel::findByPk($match['Team2']['TeamId']);
            $strTitle      = $match['Team1']['TeamName'].' - '.$match['Team2']['TeamName'];
            $strTitleShort = $match['Team1']['ShortName'].' - '.$match['Team2']['ShortName'];
    
            if ($teamHome !== null && $teamAway !== null)
            {
                $strTitle      = $teamHome->name.' - '.$teamAway->name;
                
                if(count($teamHome->short) > 0 && count($teamAway->short) > 0)
                {
                    $strTitleShort = $teamHome->short.' - '.$teamAway->short;
                }
                else
                {
                    $strTitleShort = $strTitle;
                }
            }

            $newMatch = [
                'leagueID'        => $match['LeagueId'],
                'leagueName'      => $match['LeagueName'],
                
                'groupID'         => $match['Group']['GroupID'],
                'groupName'       => $match['Group']['GroupName'],
                'groupOrderID'    => $match['Group']['GroupOrderID'],
                'groupShort'      => $this->groupNameShort($match['Group']['GroupName']),

                'deadline'        => strtotime($match['MatchDateTimeUTC']),
                'title'           => $strTitle,
                'title_short'     => $strTitleShort,

                'team_h'          => $match['Team1']['TeamId'],
                'team_a'          => $match['Team2']['TeamId'],

                'isFinished'      => $match['MatchIsFinished'],
                'lastUpdate'      => strtotime($match['LastUpdateDateTime']),

                'resultFirst'     => $resultObj->resultFirst,
                'result'          => $resultObj->result,

                'goalData'        => serialize($this->goalData($match))
            ];
            
            $lastChange = ($match['LastUpdateDateTime'] > $lastChange) ? $match['LastUpdateDateTime'] : $lastChange;

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

        $simpletippObj->lastChanged = strtotime($lastChange);
        $simpletippObj->save();

        return $arrMatchIds;
    }
    
    private function groupNameShort($groupName)
    {
        $replaceFrom = [];
        $replaceTo   = [];

        $replaceFrom[] = 'Gruppenphase';     $replaceTo[] = 'G';        
        $replaceFrom[] = 'Achtelfinale';     $replaceTo[] = '⅛';
        $replaceFrom[] = 'Viertelfinale';    $replaceTo[] = '¼';
        $replaceFrom[] = 'Halbfinale';       $replaceTo[] = '½';
        $replaceFrom[] = 'Spiel um Platz 3'; $replaceTo[] = 'P3';
        $replaceFrom[] = 'Finale';           $replaceTo[] = 'F';
        $replaceFrom[] = 'Gruppe';           $replaceTo[] = '';
        $replaceFrom[] = '. Spieltag';       $replaceTo[] = '';

        return trim(str_replace($replaceFrom, $replaceTo, $groupName));
    }

    private function parseResults($match)
    {
        $matchResults   = $match["MatchResults"];

        $resultObj = new \stdClass;
        $resultObj->resultFirst = "";
        $resultObj->result      = "";

        foreach($matchResults as $matchResult)
        {            
            if ($matchResult["ResultTypeID"] == $this->resultTypeIdFirst)
            {
                $resultObj->resultFirst = $matchResult["PointsTeam1"].SimpletippTippModel::TIPP_DIVIDER.$matchResult["PointsTeam2"];
            }
            else if ($matchResult["ResultTypeID"] == $this->resultTypeIdFinal)
            {
                $resultObj->result = $matchResult["PointsTeam1"].SimpletippTippModel::TIPP_DIVIDER.$matchResult["PointsTeam2"];
            }
        }
        return $resultObj;
    }



    public function goalData($match)
    {
        $goals = $match["Goals"];

        if(!is_array($goals) || count($goals) == 0)
        {
            return NULL;   
        }
        
        usort($goals, function($goal_a, $goal_b) {
            return ($goal_b['MatchMinute'] - $goal_a['MatchMinute']);
        });

        $goalData = [];

        foreach($goals as $goal)
        {
            $goalData[] = (Object) [
                'name'     => $goal['GoalGetterName'],
                'minute'   => $goal['MatchMinute'],
                'result'   => $goal['ScoreTeam1'].SimpletippTippModel::TIPP_DIVIDER.$goal['ScoreTeam2'],
                'penalty'  => $goal['IsPenalty'],
                'ownGoal'  => $goal['IsOwnGoal'],
                'overtime' => $goal['IsOvertime'],
                'home'     => ($previousHome !== $goal['ScoreTeam1']),
                'comment'  => $goal['comment']
            ];
            $previousHome = $goal['ScoreTeam1'];
        }
        return $goalData;
    }

    private function getTeamModel($team)
    {
        $objTeam          = new SimpletippTeamModel();
        $objTeam->id      = $team['TeamId'];
        $objTeam->tstamp  = time();
        $objTeam->name    = $team['TeamName'];

        if (is_array($GLOBALS['simpletipp']['teamData']) && array_key_exists($team['TeamId'], $GLOBALS['simpletipp']['teamData']))
        {
            $arr = $GLOBALS['simpletipp']['teamData'][$objTeam->id];
            $objTeam->short    = $arr[0];
            $objTeam->alias    = standardize($objTeam->short);
            $objTeam->three    = $arr[1];

            if (count($arr[2]) > 0)
            {
                $ext = pathinfo ($arr[2], PATHINFO_EXTENSION);
                $fn  = Config::get('uploadPath').'/simpletipp/'.standardize($objTeam->name).'.'.$ext;
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
