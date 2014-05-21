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

$GLOBALS['TL_DCA']['tl_simpletipp_match'] = array(

// Config
'config' => array
(
	'dataContainer'               => 'Table',
	'notEditable'                 => true,
	'closed'                      => true,
	'sql' => array(
		'keys' => array('id' => 'primary')
	)
		
),
		
// List
'list' => array
(
	'sorting' => array
	(
		'mode'                    => 2,
		'fields'                  => array('groupID ASC'),
		'flag'                    => 1,
		'panelLayout'             => 'filter, search, limit'
	),
	'label' => array
	(
		'fields'                  => array('leagueID', 'groupID', 'title', 'result'),
		'showColumns'             => true,
		'label_callback'          => array('tl_simpletipp_match', 'labelCallback')
	),
),

// Fields
'fields' => array
(
	'id' => array
	(
			'label'                   => array('ID'),
			'search'                  => false,
			'sql'                     => "int(10) unsigned NOT NULL"
	),
    'tstamp' => array
    (
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['tstamp'],
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
    ),
	'deadline' => array
	(
			'label'                   => array('DEADLINE'),
			'search'                  => false,
			'sql'                     => "int(10) unsigned NOT NULL"
	),
	'leagueID' => array(
			'label'                   => $GLOBALS['TL_LANG']['tl_simpletipp_match']['leagueName'],
			'sql'                     => "int(10) unsigned NOT NULL",
			'options_callback'        => array('tl_simpletipp_match', 'getLeagues'),
			'filter'                  => true,
	),
	'groupID' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['groupName'],
			'options_callback'        => array('tl_simpletipp_match', 'getGroups'),
			'sql'                     => "int(10) unsigned NOT NULL",
			'filter'                  => true,
	),
	'groupName' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['groupName'],
			'sql'                     => "varchar(255) NOT NULL default ''",
	),
    'groupName_short' => array(
            'sql'                     => "varchar(255) NOT NULL default ''",
    ),
	'title' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['title'],
			'sql'                     => "varchar(255) NOT NULL default ''",
	),
	'title_short' => array(
			'sql'                     => "varchar(255) NOT NULL default ''",
	),
	'icon_h' => array(
			'sql'                     => "varchar(255) NOT NULL default ''",
	),
	'icon_a' => array(
			'sql'                     => "varchar(255) NOT NULL default ''",
	),
	'team_h' => array(
			'sql'                     => "varchar(255) NOT NULL default ''",
	),
	'team_a' => array(
			'sql'                     => "varchar(255) NOT NULL default ''",
	),
    'team_h_three' => array(
            'sql'                     => "varchar(255) NOT NULL default ''",
    ),
    'team_a_three' => array(
            'sql'                     => "varchar(255) NOT NULL default ''",
    ),
	'lastUpdate' => array(
			'sql'                     => "int(10) unsigned NOT NULL"
	),
	'isFinished' => array(
			'sql'                     => "char(1) NOT NULL default ''"
	),
	'resultFirst' => array(
			'sql'                     => "varchar(32) NOT NULL default ''",
	),
	'result' => array(
			'label'                   => $GLOBALS['TL_LANG']['tl_simpletipp_match']['result'],
			'sql'                     => "varchar(32) NOT NULL default ''",
	),
    'goalData' => array(
            'sql'                     => "blob NULL",
    ),
	
) //fields

);


class tl_simpletipp_match extends Backend {
	private $leagues;
	private $groups;
	
	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');

		$result = $this->Database->execute('SELECT leagueID, leagueInfos FROM tl_simpletipp');
		while($result->next()) {
            $leagueInfos = unserialize($result->leagueInfos);
			$this->leagues[$result->leagueID] = $leagueInfos['name'];
		}
		
		$result = $this->Database->execute('SELECT groupID, groupName FROM tl_simpletipp_match GROUP BY groupID ORDER BY groupID');
		while($result->next()) {
			$this->groups[$result->groupID] = $result->groupName;
		}
		
	}

	public function getLeagues(DataContainer $dc) {
		//var_dump($dc);
		return $this->leagues;
	}

	public function getGroups(DataContainer $dc) {
		//var_dump($dc);
		return $this->groups;
	}
	
	public function labelCallback($row, $label, DataContainer $dc, $args = null) {
		if ($args === null) {
			return $label;
		}
		
		$leagueID = $args[0];
		$args[0]  = $this->leagues[$leagueID];
		
		$groupID = $args[1];
		$args[1]  = $this->groups[$groupID];
		
		
		return $args;
	}
}





