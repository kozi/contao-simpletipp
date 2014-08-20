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
use \Simpletipp\Simpletipp;
use \Simpletipp\Models\SimpletippTippModel;

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
		'fields'                  => array('tstamp DESC'),
		'flag'                    => 1,
		'panelLayout'             => 'filter, search, limit'
	),
	'label' => array
	(

		'fields'                  => array('member_id', 'title', 'tipp_result', 'points'),
		'showColumns'             => true,
		'label_callback'          => array('tl_simpletipp_tipp', 'labelCallback')
	),
),

// Palettes
'palettes' => array
(
    'default'                     => '{simpletipp_legend}, member_id, match_id, tipp',
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
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	),
	'member_id' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['member_id'],
            'filter'                  => true,
            'inputType'               => 'select',
            'foreignKey'              => 'tl_member.username',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => array('type' => 'belongsTo', 'load' => 'eager'),
            'eval'                    => array('mandatory' => true),
	),
	'match_id' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['match_id'],
            'filter'                  => true,
            'inputType'               => 'select',
			'foreignKey'              => 'tl_simpletipp_match.title',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => array('type' => 'belongsTo', 'load' => 'eager'),
            'eval'                    => array('mandatory' => true),
	),
	'tipp' => array(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['tipp'],
            'inputType'               => 'text',
			'sql'                     => "varchar(9) NOT NULL default ''",
            'eval'                    => array('mandatory' => true, 'maxlength' => 5),
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
	private $matches     = array();

	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');

		$result = $this->Database->execute('SELECT id, title, result, groupName FROM tl_simpletipp_match ORDER BY deadline');
		while($result->next()) {
			$match = new \stdClass;
			$match->id     = $result->id;
			$match->title  = $result->title;
            $match->result = $result->result;
            $match->group  = $result->groupName;

			$this->matches[$match->id] = $match;
		}

	}

    public function getMatchOptions(DataContainer $dc) {
        $options = array();
        foreach($this->matches as $match) {
            $options[$match->id] = '['.intval($match->group).'] '.$match->title;
        }
        return $options;
    }

	public function labelCallback($row, $label, DataContainer $dc, $args = null) {

        // var_dump($objTipp);
        if ($args === null) {
            return $label;
		}

        $objTipp  = SimpletippTippModel::findByPk($row['id']);
        $m        = $objTipp->getRelated('match_id');
        $u        = $objTipp->getRelated('member_id');
        $tipp     = $row['tipp'];

        $points   = Simpletipp::getPoints($m->result, $tipp);
        $pClass   = $points->getPointsClass();

        $args[0]  = $u->username;
        $args[1]  = $m->title;
        $args[2]  = (strlen($m->result)>0) ? $tipp.' ['.$m->result.']' : $tipp;
        $args[3]  = sprintf('<i class="%s">%s</i>', $pClass, strtoupper(substr($pClass, 0, 1)));
        $args[4]  = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $row['tstamp']);

		return $args;
	}
}





