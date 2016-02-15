<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2016 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2016 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_simpletipp_match'] = [

// Config
'config' => [
	'dataContainer'   => 'Table',
	'notEditable'     => true,
	'closed'          => true,
	'sql'             => ['keys' => ['id' => 'primary', 'title' => 'index']]
],
		
// List
'list' => [
	'sorting' => [
		'mode'                    => 2,
		'fields'                  => ['groupID ASC, deadline ASC'],
		'flag'                    => 1,
		'panelLayout'             => 'filter, search, limit'
	],
	'label' => [
		'fields'                  => ['leagueID', 'groupID', 'deadline', 'title', 'result'],
		'showColumns'             => true,
		'label_callback'          => ['tl_simpletipp_match', 'labelCallback'],
	],
],

// Fields
'fields' => [
	'id' => [
			'label'                   => ['ID'],
			'search'                  => false,
            'sql'                     => "int(10) unsigned NOT NULL",
	],
    'tstamp' => [
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['tstamp'],
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
    ],
	'deadline' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['deadline'],
			'search'                  => false,
            'inputType'               => 'text',
            'eval'                    => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
			'sql'                     => "int(10) unsigned NOT NULL"
	],
	'leagueID' => [
			'label'                   => $GLOBALS['TL_LANG']['tl_simpletipp_match']['leagueName'],
			'sql'                     => "int(10) unsigned NOT NULL",
			'options_callback'        => ['tl_simpletipp_match', 'getLeagues'],
			'filter'                  => true,
	],
	'groupID' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['groupName'],
			'options_callback'        => ['tl_simpletipp_match', 'getGroups'],
			'sql'                     => "int(10) unsigned NOT NULL",
			'filter'                  => true,
	],
	'groupName' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['groupName'],
			'sql'                     => "varchar(255) NOT NULL default ''",
	],
    'groupName_short' => [
            'sql'                     => "varchar(255) NOT NULL default ''",
    ],
	'title' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['title'],
			'sql'                     => "varchar(255) NOT NULL default ''",
	],
	'title_short' => [
			'sql'                     => "varchar(255) NOT NULL default ''",
	],
	'team_h' => [
			'foreignKey'              => 'tl_simpletipp_team.id',
			'relation'                => ['type'=>'hasOne', 'load'=>'eager'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	],
	'team_a' => [
			'foreignKey'              => 'tl_simpletipp_team.id',
			'relation'                => ['type'=>'hasOne', 'load'=>'eager'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	],
	'lastUpdate' => [
			'sql'                     => "int(10) unsigned NOT NULL"
	],
	'isFinished' => [
			'sql'                     => "char(1) NOT NULL default ''"
	],
	'resultFirst' => [
			'sql'                     => "varchar(32) NOT NULL default ''",
	],
	'result' => [
			'label'                   => $GLOBALS['TL_LANG']['tl_simpletipp_match']['result'],
			'sql'                     => "varchar(32) NOT NULL default ''",
	],
    'goalData' => [
            'sql'                     => "blob NULL",
    ],

] //fields

];


class tl_simpletipp_match extends Backend
{
	private $leagueInfos;
	private $groups;

	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');

        $this->leagueInfos = [];
        $this->groups      = [];

		$result = $this->Database->execute('SELECT leagueID, leagueInfos FROM tl_simpletipp');
		while($result->next())
		{
            $leagueInfos = unserialize($result->leagueInfos);
            $this->leagueInfos[$result->leagueID] = $leagueInfos;
		}

		$result = $this->Database->execute('SELECT groupID, groupName FROM tl_simpletipp_match GROUP BY groupID ORDER BY groupID');
		while($result->next())
		{
            $this->groups[$result->groupID] = $result->groupName;
		}

	}

	public function getLeagues(DataContainer $dc)
	{
        $leagueOptions = [];
        foreach($this->leagueInfos as $leagueID => $info)
		{
            $leagueOptions[$leagueID] = $info['name'];
        }
		return $leagueOptions;
	}

	public function getGroups(DataContainer $dc)
	{
		//var_dump($dc);
		return $this->groups;
	}
	
	public function labelCallback($row, $label, DataContainer $dc, $args = null)
	{
		$leagueID = $args[0];
		$args[0]  = $this->leagueInfos[$leagueID]['shortcut'];

        // Overwrite groupID with groupName
        $groupID  = $row['groupID'];
        $args[1]  = $this->groups[$groupID];

        // Anstoss
        $args[2]  = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $args[2]);

        return $args;
	}
}





