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


class OpenLigaDB {
	private $client;
	private $leagueShortcut = '';
	private $leagueSaison   =  0;
	
	private static $location = 'http://www.OpenLigaDB.de/Webservices/Sportsdata.asmx?WSDL';
	private static $options  = array('encoding' => 'UTF-8',
			'connection_timeout'   => 10,
			'exceptions'           => 1,
	);

	public function __construct() {
		$this->client = new SoapClient(self::$location, self::$options);
	}
	
	
	public function getAvailLeagues() {
		try {
			$response = $this->client->GetAvailLeagues();
			$data     = $response->GetAvailLeaguesResult->League;
			return $data;
		}
		catch (SoapFault $e) {
			return false;
		}
		catch (Exception $e) {
			return false;
		}			
	}	
	
	public function getMatch() {
		return $match;
	}
	
	public function setLeague($leagueObj) {
		$this->leagueShortcut = $leagueObj->leagueShortcut;
		$this->leagueSaison   = $leagueObj->leagueSaison;
	}

	public function getMatches() {
		if ($this->leagueShortcut == '' || $this->leagueSaison == 0) {
			return false;
		}

		try {
		    $params = new stdClass();
			$params->leagueShortcut = $this->leagueShortcut;
			$params->leagueSaison   = $this->leagueSaison;
			
			$response = $this->client->GetMatchdataByLeagueSaison($params);
			$data     = $response->GetMatchdataByLeagueSaisonResult->Matchdata;
			return $data;
		}
		catch (SoapFault $e) {
		    return false;
		}
		catch (Exception $e) {
		    return false;
		}
	}
	
	
	public function getLastLeagueChange() {
		try {
			$params = new stdClass;
			$params->leagueShortcut = $this->leagueShortcut;
			$params->leagueSaison   = $this->leagueSaison;
			$response = $this->client->GetLastChangeDateByLeagueSaison($params);
		}
		catch (SoapFault $e) {
			var_dump($e);
			return false;
		}
		catch (Exception $e) {
			var_dump($e);
			return false;
		}
		return $response->GetLastChangeDateByLeagueSaisonResult;
	}
	
	public function getLastGroupChange($group) {
		try {
		    $params = new stdClass;
			$params->leagueShortcut = $this->leagueShortcut;
			$params->leagueSaison = $this->leagueSaison;
			$params->groupOrderID = $group;
		    $response = $this->client->GetLastChangeDateByGroupLeagueSaison($params);
		}
		catch (SoapFault $e) {
		    return false;
		}
		catch (Exception $e) {
		    return false;
		}
		return $response->GetLastChangeDateByGroupLeagueSaisonResult;
	}
	
}
