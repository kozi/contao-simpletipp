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
		'fields'                  => ['leagueName', 'groupName', 'deadline', 'title', 'result'],
		'showColumns'             => true
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
	'LeagueName' => [
			'label'                   => $GLOBALS['TL_LANG']['tl_simpletipp_match']['leagueName'],
			'sql'                     => "varchar(255) NOT NULL default ''",
			'filter'                  => true,
	],
	'leagueID' => [
			'sql'                     => "int(10) unsigned NOT NULL",
	],
	'groupID' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['groupName'],
			'sql'                     => "int(10) unsigned NOT NULL",
			'filter'                  => true,
	],
	'groupName' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['groupName'],
			'sql'                     => "varchar(255) NOT NULL default ''",
	],
    'groupOrderID' => [
			'sql'                     => "varchar(255) NOT NULL default ''",
	],
    'groupShort' => [
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
