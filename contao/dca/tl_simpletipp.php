<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2015 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2015 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_simpletipp'] = array(

	// Config
	'config' => [
		'dataContainer'               => 'Table',
		'ctable'                      => ['tl_simpletipp_question'],
		'switchToEdit'				  => true,
		'enableVersioning'            => true,
		'onsubmit_callback' => [
			['tl_simpletipp', 'updateTeamTable'],
			['tl_simpletipp', 'updateMatches'],
			['tl_simpletipp', 'saveLeagueInfos'],
		],
		'ondelete_callback' => [
			['tl_simpletipp', 'updateTeamTable'],
			['tl_simpletipp', 'updateMatches'],
		],
		'sql' => ['keys' => ['id' => 'primary']]
	],
		
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
			),
			'reminder' => array(
				'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['reminder'],
				'href'                => 'key=reminder',
				'class'               => 'header_icon header_simpletipp_reminder',
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
            'pokal' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['pokal'],
                'href'                => 'key=pokal',
                'icon'                => 'system/modules/simpletipp/assets/images/pokal.png',
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
		'default'                     => '{simpletipp_legend}, title, leagueID, factor, matchLength, quizDeadline, adminName, adminEmail, teaser, participant_group;{simpletipp_pokal_legend}, pokal_ranges',
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
        'factor' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['factor'],
            'exclude'                 => true,
            'default'                 => '3,2,1',
            'flag'                    => 1,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''",
        ),
        'quizDeadline' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['quizDeadline'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
            'sql'                     => "varchar(10) NOT NULL default ''",
        ),
        'matchLength' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['matchLength'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp'=>'digit', 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '6300'"
        ),
        //'matchResultType'   => array(
        // TODO
        // ),
		'leagueID'     => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['leagueID'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options_callback'        => array('tl_simpletipp', 'getLeagues'),
			'eval'                    => array('mandatory'=> true, 'tl_class' => 'w50', 'submitOnChange' => true, 'chosen' => true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
		),
		'leagueInfos' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['leagueInfos'],
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
				'eval'					  => array('mandatory'=>false, 'tl_class' => 'clr', 'mandatory' => true),
				'sql'                     => "int(10) unsigned NOT NULL default '0'",
		),
        'lastChanged' => array
        (
            'label'        => array('lastChanged', 'lastChanged'),
            'sql'          => "int(10) unsigned NOT NULL default '0'",
        ),
        'lastLookup' => array
        (
            'label'        => array('lastLookup', 'lastLookup'),
            'sql'          => "int(10) unsigned NOT NULL default '0'",
        ),
        'lastRemindedMatch' => array
        (
            'label'        => array('lastRemindedMatch', 'lastRemindedMatch'),
            'sql'          => "int(10) unsigned NOT NULL default '0'",
        ),

        'pokal_ranges' => array
        (
            'label'            => &$GLOBALS['TL_LANG']['tl_simpletipp']['pokal_ranges'],
            'exclude'          => true,
            'inputType'        => 'pokalRanges',
            'eval'             => array('tl_class' => 'tl_long'),
            'sql'              => "blob NULL",
        ),

        'pokal_group'  => array('sql' => "blob NULL"),
        'pokal_16'     => array('sql' => "blob NULL"),
        'pokal_8'      => array('sql' => "blob NULL"),
        'pokal_4'      => array('sql' => "blob NULL"),
        'pokal_2'      => array('sql' => "blob NULL"),
        'pokal_finale' => array('sql' => "blob NULL"),

	)
);


use \Simpletipp\OpenLigaDB;
use \Simpletipp\Simpletipp;
use \Simpletipp\Models\SimpletippModel;

/**
 * Class tl_simpletipp
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 */

class tl_simpletipp extends \Backend
{
    private $memberGroups       = [];
    private $matchGroupOptions  = [];

	public function __construct()
    {
		parent::__construct();
		$this->cleanupMatches();
		$this->import('BackendUser', 'User');
        $this->oldb = OpenLigaDB::getInstance();

		// Mitgliedergruppen holen		
		$result = $this->Database->execute("SELECT id, name FROM tl_member_group ORDER BY id");
		while($result->next())
        {
			$this->memberGroups[$result->id] = $result->name;
		}
	}

	public function getLeagues(DataContainer $dc)
    {
		$leagues = $this->oldb->getAvailLeagues();
		$options = [];
        $tmpl    = '%s [%s, %s]';
		foreach ($leagues as $league)
        {
            $options[$league->leagueID] = sprintf($tmpl,
                $league->leagueName,
                \String::substr($league->leagueShortcut, 10),
                $league->leagueID
            );
		}
        asort($options);
        return $options;
	}	

	public function labelCallback($row, $label, DataContainer $dc, $args = null)
    {
		if ($args === null)
        {
			return $label;
		}

        $leagueInfos = unserialize($row['leagueInfos']);

		$args[1] = sprintf('<span title="%s (%s, %s)">%s</span>',
                $leagueInfos['name'],
                $leagueInfos['shortcut'],$leagueInfos['saison'],
                $leagueInfos['name']);
		
		
		$groupId = $args[2];
		$args[2] = $this->memberGroups[$groupId];
		
		$args[3] = date($GLOBALS['TL_CONFIG']['datimFormat'], $args[3]);
		 
		return $args;
	}

    public function getMatchgroups(DataContainer $dc)
    {
        if (count($this->matchGroupOptions) == 0)
        {
            $this->matchGroupOptions[''] = '-';
            $leagueID = intval($dc->activeRecord->leagueID);
            $groups   = Simpletipp::getLeagueGroups($leagueID);
            foreach ($groups as $g)
            {
                $this->matchGroupOptions[$g->title] = $g->title;
            }
        }
        return $this->matchGroupOptions;
    }

    public function saveLeagueInfos(DataContainer $dc)
	{
        $leagueID  = intval($dc->activeRecord->leagueID);
		$leagues   = $this->oldb->getAvailLeagues();
		$leagueObj = null;
		foreach($leagues as $league)
		{
			if ($league->leagueID == $leagueID)
			{
				$leagueObj = $league;
			}
		}

        if ($leagueObj != null)
		{
            $objSimpletipp = SimpletippModel::findByPk($dc->activeRecord->id);
            $objSimpletipp->leagueInfos = serialize([
                'name'     => $leagueObj->leagueName,
                'shortcut' => $leagueObj->leagueShortcut,
                'saison'   => $leagueObj->leagueSaison
			]);
            $objSimpletipp->save();
		}

	}

    public function updateMatches()
	{
        $this->import('\Simpletipp\SimpletippMatchUpdater', 'SimpletippMatchUpdater');
        $this->SimpletippMatchUpdater->updateMatches();
    }

    public function updateTeamTable()
	{
        $this->import('\Simpletipp\SimpletippMatchUpdater', 'SimpletippMatchUpdater');
        $this->SimpletippMatchUpdater->updateTeamTable();
    }

    private function cleanupMatches()
	{
		$this->Database->execute("DELETE FROM tl_simpletipp_match
			WHERE leagueID NOT IN (SELECT tl_simpletipp.leagueID FROM tl_simpletipp)");
	}
}
