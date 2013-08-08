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

$GLOBALS['TL_DCA']['tl_simpletipp_pokal_mapping'] = array(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_simpletipp_pokal',
		'enableVersioning'            => false,
		'closed' 				      => true,
		'onload_callback'             => array(
				array('tl_simpletipp_pokal_mapping', 'initMapping')
			)
	),

		
		
	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('name'),
			'flag'                    => 1,
			'panelLayout'             => 'limit',
			'disableGrouping'         => true,
			'headerFields'            => array('name'),
			'child_record_callback'   => array('tl_simpletipp_pokal_mapping', 'getMapping')
		),
		'label' => array
		(
			'fields'                  => array('name', 'member'),
			'showColumns'             => true,
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'					=> '{legend}, name, matches;',
	),

/*
    CREATE TABLE `tl_simpletipp_pokal_mapping`
  `name` varchar(64) NOT NULL default '',
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `member` ,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

*/

	// Fields
	'fields' => array
	(
        'id' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_pokal']['matches'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'options_callback'        => array('tl_simpletipp_pokal', 'getMatches'),
            'eval'					  => array('mandatory'=>false, 'tl_class' => 'clr', 'multiple' => true),
        ),
		'member' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_pokal']['name'],
			'exclude'                 => true,
			'reference'               => &$GLOBALS['TL_LANG']['tl_simpletipp_pokal'],
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'tl_class' => 'long'),
            'sql'                     => "blob NULL",
		),

	)
);


/**
 * Class tl_simpletipp_pokal_mapping
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 */

class tl_simpletipp_pokal_mapping extends Backend {
	
	private $phases = array('pokal_group', 'pokal_16','pokal_8', 'pokal_4', 'pokal_2', 'pokal_finale');
	
	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}
	
	public function initMapping(DataContainer $dc) {

		$result = $this->Database
			->prepare('SELECT id FROM tl_simpletipp_pokal WHERE pid = ?')
			->execute($dc->id);
		
		$sql = "INSERT INTO tl_simpletipp_pokal_mapping(pid, name, member) VALUES (?, ?, ?)";
		
		$this->Database->prepare($sql)->execute($dc->id, 'Paring', serialize(array(1,2,3)));
		$this->Database->prepare($sql)->execute($dc->id, 'Paring', serialize(array(1,2,3)));
		
		/*
		$i = 0;
		if ($result->numRows == 0) {
			$sql = "INSERT INTO tl_simpletipp_pokal(pid, tstamp, sorting, name) VALUES (?, ?, ?, ?)";
			foreach($this->phases as $phase) {
				$this->Database->prepare($sql)->execute($dc->id, time(), pow(2, $i++), $phase);
			}
		}
		else {
			$sql = "UPDATE tl_simpletipp_pokal SET sorting = ? WHERE name = ?";
			foreach($this->phases as $phase) {
				$this->Database->prepare($sql)->execute(pow(2, $i++), $phase);
			}
		}
		*/
	}


	public function getMapping($row) {
		
		// return print_r($row, true);
		
		$n    = $row['name'];
		$name = $GLOBALS['TL_LANG']['tl_simpletipp_pokal'][$n];
		$m    = unserialize($row['matches']);
		
		
		
		if (!is_array($m) || count($m) === 0) {
			return sprintf('<span class="name">%s</span>', $name);
		}
		
		
		$result = $this->Database->execute('SELECT DISTINCT matchgroup, deadline FROM tl_simpletipp_match'
				.' WHERE id in ('.implode(',', $m).') ORDER BY deadline');
		
		if ($result->numRows == 0) {
			return sprintf('<span class="name">%s</span>', $name);
		}
		
		$deadline = date($GLOBALS['TL_CONFIG']['datimFormat'], $result->deadline);
		
		$result->reset();
		$matchgroups = array();
		while ($result->next()) {
			$matchgroups[] = $result->matchgroup;
		}
		return sprintf('<span class="name">%s</span><span class="deadline">%s</span><span class="matches">%s</span>',
				$name, $deadline, implode(', ', $matchgroups).' ('.count($m).')');
		
	}

}


