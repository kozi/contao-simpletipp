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

$GLOBALS['TL_DCA']['tl_simpletipp_pokal'] = array(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_simpletipp',
		'enableVersioning'            => false,
		'closed' 				      => true,
        'sql' => array(
            'keys' => array('id' => 'primary')
        ),
		'onload_callback'             => array(
				array('tl_simpletipp_pokal', 'initPhases')
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'flag'                    => 1,
			'panelLayout'             => 'limit',
			'disableGrouping'         => true,
			'headerFields'            => array('title', 'teaser', 'tstamp'),
			'child_record_callback'   => array('tl_simpletipp_pokal', 'getPhases')
		),
		'label' => array
		(
			'fields'                  => array('name', 'matches'),
			'showColumns'             => true,
		),
		'operations' => array
		(
			'pairs' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_pokal']['pairs'],
					'href'                => 'table=tl_simpletipp_pokal_mapping',
					'icon'                => 'mgroup.gif'
			),
			'edit' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_pokal']['edit'],
					'href'                => 'act=edit',
					'icon'                => 'edit.gif'
			),
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'					=> '{legend}, name, matches;',
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
        'pid' => array
        (
            'label'                   => array('PID'),
            'search'                  => false,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array
        (
            'label'                   => array('TSTAMP'),
            'search'                  => false,
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
        ),
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_pokal']['name'],
			'exclude'                 => true,
			'reference'               => &$GLOBALS['TL_LANG']['tl_simpletipp_pokal'],
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'tl_class' => 'long'),
            'sql'                     => "varchar(64) NOT NULL default ''"
		),
		'matches' => array
		(
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_pokal']['matches'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'options_callback'        => array('tl_simpletipp_pokal', 'getMatches'),
            'eval'					  => array('mandatory'=>false, 'tl_class' => 'clr', 'multiple' => true),
            'sql'                     => "blob NULL"
		)		
		
	)
);


/**
 * Class tl_simpletipp_pokal
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 */

class tl_simpletipp_pokal extends Backend {
	
	private $phases = array('pokal_group', 'pokal_16', 'pokal_8', 'pokal_4', 'pokal_2', 'pokal_finale');
	
	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}
	
	public function initPhases(DataContainer $dc) {

		$result = $this->Database
			->prepare('SELECT id FROM tl_simpletipp_pokal WHERE pid = ?')
			->execute($dc->id);
		$i = 0;
		if ($result->numRows == 0) {
			$sql = "INSERT INTO tl_simpletipp_pokal(pid, tstamp, name) VALUES (?, ?, ?)";
			foreach($this->phases as $phase) {
				$this->Database->prepare($sql)->execute($dc->id, time(), $phase);
			}
			
			
			
		}
	}


	public function getPhases($row) {
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
	
	public function getMatches(DataContainer $dc) {
		$matches = array();
		$pid = $dc->activeRecord->pid;

		$result = $this->Database->prepare("SELECT leagueID FROM tl_simpletipp WHERE id = ?")
			->execute($dc->activeRecord->pid);
		
		if ($result->numRows == 0) {
			return matches;
		}
		$leagueID = $result->leagueID;
		
		$result = $this->Database->prepare('SELECT * FROM tl_simpletipp_match'
				.' WHERE leagueID = ? ORDER BY groupID, deadline')->execute($leagueID);

		// TODO Phasen markieren
		// TODO Spieltage anzeige
		// TODO Spieltageweise markierung über javascript

		// TODO Label für Spiele in der Übersicht
		// TODO Name muss readonly sein bzw. nur angezeigt werden (Überschrift manipulieren?!?)
		// TODO Button zum auslosen
		// TODO Anzeige der Paarungen im Backend
		
		$str = '<span class="match %s">'
					.'<span class="dline">%s</span>'
			  		.'<span class="title">%s</span>'
			  		.'<span class="matchgroup">%s</span>'
			  .'</span>';
		
		$mg = $result->matchgroup;
		$cssClass = 'odd';
		
		while ($result->next()) {
			$first = '';
			
			if($mg != $result->matchgroup)  {
				$cssClass = ($cssClass == 'odd') ? 'even' : 'odd';
				$first = ' first';
			}
			
			$mg = $result->matchgroup;
			
			$matches[$result->id] = sprintf($str,
					standardize($result->matchgroup).' '.$cssClass.$first,
					date($GLOBALS['TL_CONFIG']['datimFormat'],$result->deadline),
					$result->title,
					$result->matchgroup);
		}
		return $matches;
	}
	
	
	
}


