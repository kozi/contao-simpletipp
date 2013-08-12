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

$GLOBALS['TL_JAVASCRIPT'][]         = "/system/modules/simpletipp/assets/String.sprintf.js";
$GLOBALS['TL_DCA']['tl_simpletipp'] = array(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ctable'                      => array('tl_simpletipp_question'),
		'switchToEdit'				  => true,
		'enableVersioning'            => true,
		'onsubmit_callback' => array(
			array('tl_simpletipp', 'saveLeagueObject'),
			array('tl_simpletipp', 'updateMatches')
		),
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
			'fields'                  => array('tstamp DESC'),
			'flag'                    => 1,
			'panelLayout'             => 'limit'
		),
		'label' => array
		(
			'fields'                  => array('title', 'leagueObject', 'participant_group', 'tstamp'),
			'showColumns'             => true,
			'label_callback'          => array('tl_simpletipp', 'labelCallback')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
		),
		'operations' => array
		(
            'calculate' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['calculate'],
                'href'                => 'key=calculate',
                'icon'                => 'system/themes/default/images/modules.gif',
            ),

			'update' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['update'],
					'href'                => 'key=update',
					'icon'                => 'system/themes/default/images/reload.gif',
			),
			'questions' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['questions'],
					'href'                => 'table=tl_simpletipp_question',
					'icon'                => 'system/modules/simpletipp/assets/images/question-balloon.png'
			),
			'edit' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['edit'],
					'href'                => 'act=edit',
					'icon'                => 'edit.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{simpletipp_legend}, title, leagueID, adminName, adminEmail, teaser, participant_group;{simpletipp_pokal_legend},pokal_ranges',
	),


	// Fields
	'fields' => array
	(
		'id' => array
		(
				'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['tstamp'],
				'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['title'],
				'exclude'                 => true,
				'flag'                    => 1,
				'inputType'               => 'text',
				'eval'                    => array('mandatory'=>true, 'tl_class' => 'w50', 'maxlength' => 48),
				'sql'                     => "varchar(64) NOT NULL default ''",
		),
        'adminName' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['adminName'],
            'exclude'                 => true,
            'flag'                    => 1,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''",
        ),
        'adminEmail' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['adminEmail'],
            'exclude'                 => true,
            'flag'                    => 1,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''",
        ),

		'leagueID' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['leagueID'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options_callback'        => array('tl_simpletipp', 'getLeagues'),
			'eval'                    => array('mandatory'=>true, 'tl_class' => 'w50', 'submitOnChange' => true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
		),
		'leagueObject' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['leagueObject'],
			'sql'                     => "blob NULL"
		),
		'teaser' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['teaser'],
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'		              => array('tl_class' => 'long clr' ,'style' => ' height:28px;', 'mandatory'=>false),
			'sql'                     => "text NULL",
		),
		'participant_group' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['participant_group'],
				'exclude'                 => true,
				'inputType'               => 'radio',
				'foreignKey'              => 'tl_member_group.name',
				'eval'					  => array('mandatory'=>false, 'tl_class' => 'clr'),
				'sql'                     => "int(10) unsigned NOT NULL default '0'",
		),
        'lastChanged' => array
        (
            'label'        => array('lastChanged', 'lastChanged'),
            'sql'          => "int(10) unsigned NOT NULL default '0'",
        ),
        'lastRemindedMatch' => array
        (
            'label'        => array('lastRemindedMatch', 'lastRemindedMatch'),
            'sql'          => "int(10) unsigned NOT NULL default '0'",
        ),

        'pokal_ranges' => array
        (
            'label'        => &$GLOBALS['TL_LANG']['tl_simpletipp']['pokal_ranges'],
            'exclude'      => true,
            'inputType'    => 'select',
            'eval'         => array('multiple' => true, 'tl_class' => 'pokal_ranges'),
            'options_callback' => array('tl_simpletipp','getMatchgroups'),
            'sql'          => "blob NULL",
        ),

        'pokal_group'  => array('sql' => "blob NULL"),
        'pokal_16'     => array('sql' => "blob NULL"),
        'pokal_8'      => array('sql' => "blob NULL"),
        'pokal_4'      => array('sql' => "blob NULL"),
        'pokal_2'      => array('sql' => "blob NULL"),
        'pokal_finale' => array('sql' => "blob NULL"),

	)
);


/**
 * Class tl_simpletipp
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 */

class tl_simpletipp extends Backend {
    private $memberGroups       = array();
    private $matchGroupOptions  = array();
	public function __construct() {
		parent::__construct();
		$this->cleanupMatches();
		$this->import('BackendUser', 'User');
		$this->import('OpenLigaDB');
		
		// Mitgliedergruppen holen		
		$result = $this->Database->execute("SELECT id, name FROM tl_member_group ORDER BY id");
		while($result->next()) {
			$this->memberGroups[$result->id] = $result->name;
		}
		

		
	}

	public function getLeagues(DataContainer $dc) {
		$leagues = $this->OpenLigaDB->getAvailLeagues();
		$options = array();
		foreach ($leagues as $league) {
            $options[$league->leagueID] = $league->leagueName;
		}
		return $options;
	}	

	public function labelCallback($row, $label, DataContainer $dc, $args = null) {
		if ($args === null) {
			return $label;
		}

		$leagueObject = unserialize($row['leagueObject']);

		$args[1] = sprintf('<span title="%s (%s, %s)">%s</span>',
				$leagueObject->leagueName,
				$leagueObject->leagueShortcut, $leagueObject->leagueSaison,
				$leagueObject->leagueName);
		
		
		$groupId = $args[2];
		$args[2] = $this->memberGroups[$groupId];
		
		$args[3] = date($GLOBALS['TL_CONFIG']['datimFormat'], $args[3]);
		 
		return $args;
	}

    public function getMatchgroups(DataContainer $dc) {
        if (count($this->matchGroupOptions) == 0) {
            $this->matchGroupOptions[''] = '-';
            $leagueID = intval($dc->activeRecord->leagueID);
            $groups   = Simpletipp::getLeagueGroups($leagueID);
            foreach ($groups as $g) {
                $this->matchGroupOptions[$g->title] = $g->title;
            }
        }
        return $this->matchGroupOptions;

    }

    public function saveLeagueObject(DataContainer $dc) {
        $leagueID  = intval($dc->activeRecord->leagueID);
		$leagues   = $this->OpenLigaDB->getAvailLeagues();
		$leagueObj = null;
		foreach($leagues as $league) {
			if ($league->leagueID == $leagueID) {
				$leagueObj = $league;
				break;
			}
		}

		if ($leagueObj != null) {
			$this->Database->prepare("UPDATE tl_simpletipp SET leagueObject = ? WHERE id = ?")
			->execute(serialize($leagueObj), $dc->activeRecord->id);
		}
	}
	
	
	public function updateMatches($dc, $leagueObject = null) {
		if ($dc->activeRecord->leagueObject === null && $leagueObject === null) {
			return false;
		}
		if ($dc->activeRecord->leagueObject !== null) {
			$lObj  = unserialize($dc->activeRecord->leagueObject);
		} else {
			$lObj  = $leagueObject;
		}

		if (!is_object($lObj)) {
			return false;
		}

		$this->OpenLigaDB->setLeague($lObj);
		
		$matches = $this->OpenLigaDB->getMatches();
		if ($matches === false) {
			return false;
		}
		
		$matchIDs   = array();
		$newMatches = array();

		foreach($matches as $match) {
			$tmp          = get_object_vars($match);
			$matchIDs[]   = $tmp['matchID'];
			
			$results      = $this->parseResults($tmp['matchResults']);  
			$newMatches[] = array(
				'id'              => $tmp['matchID'],
				'leagueID'        => $tmp['leagueID'],
				'groupID'         => $tmp['groupID'],
                'groupName'       => $tmp['groupName'],
                'groupName_short' => trim(str_replace('. Spieltag', '', $tmp['groupName'])),
				'deadline'        => strtotime($tmp['matchDateTimeUTC']),
				'title'           => sprintf("%s - %s", $tmp['nameTeam1'], $tmp['nameTeam2']),
				'title_short'     => sprintf("%s - %s", Simpletipp::teamShortener($tmp['nameTeam1']), Simpletipp::teamShortener($tmp['nameTeam2'])),

                'team_h'       => Simpletipp::teamShortener($tmp['nameTeam1']),
				'team_a'       => Simpletipp::teamShortener($tmp['nameTeam2']),
                'team_h_three' => Simpletipp::teamShortener($tmp['nameTeam1'], true),
                'team_a_three' => Simpletipp::teamShortener($tmp['nameTeam2'], true),
				'icon_h'       => Simpletipp::iconUrl($tmp['nameTeam1'], '/files/vereinslogos/'),
				'icon_a'       => Simpletipp::iconUrl($tmp['nameTeam2'], '/files/vereinslogos/'),

				'isFinished'   => $tmp['matchIsFinished'],
				'lastUpdate'   => strtotime($tmp['lastUpdate']),
				'resultFirst'  => $results[0],
				'result'       => $results[1],
			);
		}

		$this->Database->execute("DELETE FROM tl_simpletipp_match WHERE id IN ('"
				.implode("', '", $matchIDs)."')");

		foreach($newMatches as $m) {
			$this->Database->prepare("INSERT INTO tl_simpletipp_match %s")->set($m)->execute();
		}

		
		return $matchIDs;
	}

	
	private function parseResults($matchResults) {
		$rFirst = '';
		$rFinal = '';

		if ($matchResults->matchResult === null){
			return array($rFirst, $rFinal);
		}

		foreach ($matchResults->matchResult as $res) {
			 if ($res->resultTypeId === 1) {
				$rFirst = $res->pointsTeam1.':'.$res->pointsTeam2;
			}
			if ($res->resultTypeId === 2) {
				$rFinal = $res->pointsTeam1.':'.$res->pointsTeam2;
			}
		}
		return array($rFirst, $rFinal);
	}

	
	private function cleanupMatches() {
		$this->Database->execute("DELETE FROM tl_simpletipp_match
			WHERE leagueID NOT IN (SELECT tl_simpletipp.leagueID FROM tl_simpletipp)");
	}
}


