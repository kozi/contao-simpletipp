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

$GLOBALS['TL_DCA']['tl_simpletipp_match'] = array(

// Config
'config' => array
(
	'dataContainer'               => 'Table',
	'notEditable'                 => true,
	'closed'                      => true,
	'sql' => array(
		'keys' => array(
            'id'    => 'primary',
            'title' => 'index'
        )
	)
		
),
		
// List
'list' => array
(
	'sorting' => array
	(
		'mode'                    => 2,
		'fields'                  => array('groupID ASC, deadline ASC'),
		'flag'                    => 1,
		'panelLayout'             => 'filter, search, limit'
	),
	'label' => array
	(
		'fields'                  => array('leagueID', 'groupID', 'deadline', 'title', 'result'),
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
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_match']['deadline'],
			'search'                  => false,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
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
	private $leagueInfos;
	private $groups;

	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');

        $this->leagueInfos = array();
        $this->groups      = array();

		$result = $this->Database->execute('SELECT leagueID, leagueInfos FROM tl_simpletipp');
		while($result->next()) {
            $leagueInfos = unserialize($result->leagueInfos);
            $this->leagueInfos[$result->leagueID] = $leagueInfos;
		}

		$result = $this->Database->execute('SELECT groupID, groupName FROM tl_simpletipp_match GROUP BY groupID ORDER BY groupID');
		while($result->next()) {
            $this->groups[$result->groupID] = $result->groupName;
		}

	}

	public function getLeagues(DataContainer $dc) {
        $leagueOptions = array();
        foreach($this->leagueInfos as $leagueID => $info) {
            $leagueOptions[$leagueID] = $info['name'];
        }
		return $leagueOptions;
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
		$args[0]  = $this->leagueInfos[$leagueID]['shortcut'];

        // Overwrite groupID with groupName
        $groupID  = $row['groupID'];
        $args[1]  = $this->groups[$groupID];

        // Anstoss
        $args[2]  = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $args[2]);


        return $args;
	}
}





