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

$GLOBALS['TL_DCA']['tl_simpletipp_tipp'] = array(

// Config
'config' => array
(
	'dataContainer'               => 'Table',
	'notEditable'                 => false,
	'closed'                      => false,
	'sql' => array(
		'keys' => array('id' => 'primary')
		// TODO UNIQUE KEY `one_tipp_for_user_per_match` (`member_id`, `match_id`)
	)

),

// List
'list' => array
(
	'sorting' => array
	(
		'mode'                    => 2,
		'fields'                  => array('tstamp ASC', 'member_id ASC'),
		'flag'                    => 1,
		'panelLayout'             => 'filter, search, limit'
	),
	'label' => array
	(
		'fields'                  => array('member_id', 'match_id', 'tipp', 'result', 'points'),
		'showColumns'             => true,
		'label_callback'          => array('tl_simpletipp_tipp', 'labelCallback')
	),
),

// Fields
'fields' => array
(
	'id' => array
	(
			'label'                   => array('ID'),
			'search'                  => false,
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
	),
	'tstamp' => array
	(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['tstamp'],
			'search'                  => false,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
	),
	'member_id' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['member_id'],
			'sql'                     => "int(10) unsigned NOT NULL",
			'inputType'               => 'select',
			'foreignKey'              => 'tl_member.username',
			'filter'                  => true,
	),
	'match_id' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['match_id'],
			'sql'                     => "int(10) unsigned NOT NULL",
			'inputType'               => 'select',
			'foreignKey'              => 'tl_simpletipp_match.title',
			'filter'                  => true,
	),
	'tipp' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['tipp'],
			'sql'                     => "varchar(9) NOT NULL default ''",
	),
	'perfect' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['perfect'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	),
	'difference' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['difference'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	),
	'tendency' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['tendency'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	),
	'wrong' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['wrong'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	),


) //fields

);


class tl_simpletipp_tipp extends Backend {
	private $memberNames = array();
	private $matches     = array();

	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');

		$result = $this->Database->execute('SELECT id, username FROM tl_member');
		while($result->next()) {
			$this->memberNames[$result->id] = $result->username;
		}

		$result = $this->Database->execute('SELECT id, title, result FROM tl_simpletipp_match');
		while($result->next()) {
			$match = new stdClass;
			$match->id     = $result->id;
			$match->title  = $result->title;
			$match->result = $result->result;

			$this->matches[$match->id] = $match;
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
		$member_id = intval($row['member_id']);
		$match_id  = intval($row['match_id']);

		if(array_key_exists($match_id, $this->matches)) {
            $m        = $this->matches[$match_id];
            $tipp     = $row['tipp'];

			$args[0]  = $this->memberNames[$member_id];
			$args[1]  = $m->title;
			$args[2]  = $tipp;
			$args[3]  = $m->result;

            $points   = Simpletipp::getPoints($m->result, $tipp);
            $c        = $points->getPointsClass();
            $args[4]  = sprintf('<i class="%s">%s</i>', $c, strtoupper(substr($c, 0, 1)));
			$args[5]  = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $row['tstamp']);
		}
		return $args;
	}
}





