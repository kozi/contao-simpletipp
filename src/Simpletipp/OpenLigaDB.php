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

class OpenLigaDB
{
    const SOAP_URL = 'http://www.OpenLigaDB.de/Webservices/Sportsdata.asmx?WSDL';

    /**
     * @var OpenLigaDB
     */
    protected static $instance;

    private $client;
	private $leagueShortcut = '';
	private $leagueSaison   =  0;

    /**
     * Instantiate the OpenLigaDB object (Factory)
     *
     * @return OpenLigaDB The OpenLigaDB object
     */
    static public function getInstance()
	{
        if (static::$instance === null)
		{
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function __construct()
    {
		$this->client = new \SoapClient(static::SOAP_URL, [
                'encoding'             => 'UTF-8',
                'connection_timeout'   => 10,
                'exceptions'           => 1,
        ]);
	}


	public function getAvailLeagues()
    {
		try
        {
			$response = $this->client->GetAvailLeagues();
			$data     = $response->GetAvailLeaguesResult->League;
			return $data;
		}
		catch (SoapFault $e)
        {
			return false;
		}
		catch (Exception $e)
        {
			return false;
		}
	}

	public function getMatchGoals($matchId)
    {
        try
        {
            $res  = $this->client->GetGoalsByMatch((Object) ['MatchID' => $matchId]);
            $data = $res->GetGoalsByMatchResult->Goal;
            return $data;
        }
        catch (SoapFault $e)
        {
            return false;
        }
        catch (Exception $e)
        {
            return false;
        }
	}

	public function setLeague(array $leagueInfos)
    {
		$this->leagueShortcut = $leagueInfos['shortcut'];
		$this->leagueSaison   = $leagueInfos['saison'];
	}

	public function getMatches()
    {
		if ($this->leagueShortcut == '' || $this->leagueSaison == 0)
        {
			return false;
		}

		try
        {
		    $params = (Object) [
                'leagueShortcut' => $this->leagueShortcut,
                'leagueSaison'   => $this->leagueSaison
            ];
			$res  = $this->client->GetMatchdataByLeagueSaison($params);
			$data = $res->GetMatchdataByLeagueSaisonResult->Matchdata;
			return $data;
		}
		catch (SoapFault $e)
        {
		    return false;
		}
		catch (Exception $e)
        {
		    return false;
		}
	}


    public function getLeagueTeams()
    {
        if ($this->leagueShortcut == '' || $this->leagueSaison == 0)
        {
            return false;
        }

        try
        {
            $params = (Object) [
                'leagueShortcut' => $this->leagueShortcut,
                'leagueSaison'   => $this->leagueSaison
            ];
            $res  = $this->client->GetTeamsByLeagueSaison($params);
            $data = $res->GetTeamsByLeagueSaisonResult->Team;
            return $data;
        }
        catch (SoapFault $e)
        {
            return false;
        }
        catch (Exception $e)
        {
            return false;
        }
    }

	public function getLastLeagueChange()
    {
		try
        {
            $params = (Object) [
                'leagueShortcut' => $this->leagueShortcut,
                'leagueSaison'   => $this->leagueSaison
            ];
			$res  = $this->client->GetLastChangeDateByLeagueSaison($params);
            return $res->GetLastChangeDateByLeagueSaisonResult;
		}
        catch (SoapFault $e)
        {
			return false;
		}
		catch (Exception $e)
        {
			return false;
		}
	}
}
