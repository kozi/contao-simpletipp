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
use Simpletipp\Models\SimpletippModel;
use Simpletipp\Models\SimpletippTippModel;

$GLOBALS['TL_DCA']['tl_simpletipp_tipp'] = [

// Config
'config' => [
	'dataContainer'               => 'Table',
	'notEditable'                 => false,
	'closed'                      => false,
    'onload_callback'             => [['tl_simpletipp_tipp', 'changeInputType']],
    'onsubmit_callback'           => [['tl_simpletipp_tipp', 'processSubmittedTipps']],
	'sql' => [
		'keys' => ['id' => 'primary'],
		// TODO UNIQUE KEY `one_tipp_for_user_per_match` (`member_id`, `match_id`)
	],
],

// List
'list' => [
	'sorting' => [
		'mode'                    => 2,
		'fields'                  => ['tstamp DESC'],
		'flag'                    => 1,
		'panelLayout'             => 'filter, search, limit'
	],
    'global_operations' => [
        'create' => [
            'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['insert_tipp'],
            'href'                => 'act=create',
            'class'               => 'header_create',
            'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="c"'
        ],
    ],
	'label' => [
		'fields'                  => ['member_id:tl_member.username', 'match_id:tl_simpletipp_match.title', 'match_id:tl_simpletipp_match.result', 'points'],
		'showColumns'             => true,
		'label_callback'          => ['tl_simpletipp_tipp', 'labelCallback'],
	],
],

// Palettes
'palettes' => [
    'default'                     => '{simpletipp_legend}, member_id, match_id, tipp',
],

// Fields
'fields' => [
	'id' => [
			'label'                   => ['ID'],
			'search'                  => false,
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
	],
	'tstamp' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['tstamp'],
			'search'                  => false,
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	],
	'member_id' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['member_id'],
            'filter'                  => true,
            'inputType'               => 'select',
            'foreignKey'              => 'tl_member.username',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => ['type' => 'hasOne', 'load' => 'eager'],
            'eval'                    => ['mandatory' => true, 'submitOnChange' => true, 'includeBlankOption' => true],
	],
	'match_id' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['match_id'],
            'filter'                  => true,
            'inputType'               => 'select',
			'foreignKey'              => 'tl_simpletipp_match.title',
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
            'relation'                => ['type' => 'hasOne', 'load' => 'eager'],
            'eval'                    => ['mandatory' => true],
	],
    'tipp' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['tipp'],
            'inputType'               => 'text',
			'sql'                     => "varchar(9) NOT NULL default ''",
            'eval'                    => ['mandatory' => true, 'maxlength' => 5],
	],
	'perfect' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['perfect'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	],
	'difference' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['difference'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	],
	'tendency' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['tendency'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	],
	'wrong' => [
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_tipp']['wrong'],
			'sql'                     => "int(10) unsigned NOT NULL default '0'",
	],


] //fields

];


class tl_simpletipp_tipp extends Backend
{
	private $matches     = [];

	public function __construct()
    {
		parent::__construct();
		$this->import('BackendUser', 'User');

		$result = $this->Database->execute('SELECT id, title, result, groupName FROM tl_simpletipp_match ORDER BY deadline');
		while($result->next())
        {
			$match = new \stdClass;
			$match->id     = $result->id;
			$match->title  = $result->title;
            $match->result = $result->result;
            $match->group  = $result->groupName;

			$this->matches[$match->id] = $match;
		}

	}

    public function getMatchOptions(DataContainer $dc)
    {
        $options = [];
        foreach($this->matches as $match)
        {
            $options[$match->id] = '['.intval($match->group).'] '.$match->title;
        }
        return $options;
    }

	public function labelCallback($row, $label, DataContainer $dc, $args = null)
    {
        if ($args === null)
        {
            return $label;
		}

        $tipp     = $row['tipp'];
        $result   = $args[2];
        $points   = SimpletippTippModel::getPoints($result, $tipp);
        $pClass   = $points->getPointsClass();

        $args[2]  = (strlen($result)>0) ? $tipp.' ['.$result.']' : $tipp;
        $args[3]  = sprintf('<i class="%s">%s</i>', $pClass, strtoupper(substr($pClass, 0, 1)));
        $args[4]  = Date::parse($GLOBALS['TL_CONFIG']['datimFormat'], $row['tstamp']);

		return $args;
	}

    public function changeInputType(\DataContainer $dc)
    {
        if ('edit' === \Input::get('act'))
        {
            $GLOBALS['TL_DCA']['tl_simpletipp_tipp']['palettes']['default']         = '{simpletipp_legend}, member_id, simpletippGroups, leagueGroups, tipp';
            $GLOBALS['TL_DCA']['tl_simpletipp_tipp']['fields']['tipp']['label']     = ['Tipps', 'Hier die Tipps eintragen'];

            $GLOBALS['TL_DCA']['tl_simpletipp_tipp']['fields']['simpletippGroups'] = [
                'label'            => ['Tipprunde', 'Tipprunde wählen'],
                'inputType'        => 'select',
                'foreignKey'       => 'tl_simpletipp.title',
                'load_callback'    => [['tl_simpletipp_tipp','loadCallbackSimpletippGroups']],
                'eval'             => ['tl_class' => 'w50', 'submitOnChange' => true, 'includeBlankOption' => true, 'readonly' => true],
            ];
            $GLOBALS['TL_DCA']['tl_simpletipp_tipp']['fields']['leagueGroups'] = [
                'label'            => ['Spieltag/Gruppe', 'Spieltag/Gruppe wählen'],
                'inputType'        => 'select',
                'options_callback' => ['tl_simpletipp_tipp','leagueGroupOptions'],
                'load_callback'    => [['tl_simpletipp_tipp','loadCallbackLeagueGroups']],
                'eval'             => ['tl_class' => 'w50', 'submitOnChange' => true, 'includeBlankOption' => true, 'readonly' => true],
            ];

            $GLOBALS['TL_DCA']['tl_simpletipp_tipp']['fields']['tipp']['inputType'] = 'tippInserter';
        }
        else
        {
            //Cleanup
            $this->Database->execute('DELETE FROM tl_simpletipp_tipp WHERE match_id = 0');

        }
    }

    public function leagueGroupOptions(\DataContainer $dc)
    {
        $options      = [];
        $simpletippId = $this->handleSessionData('simpletippGroups');

        if ($simpletippId)
        {
            $simpletippObj = SimpletippModel::findByPk($simpletippId);
            $leagueGroups  = SimpletippModel::getLeagueGroups($simpletippObj->leagueID);
            foreach ($leagueGroups as $id => $g)
            {
                $options[$id] = $g->title;
            }
        }
        return $options;
    }

    public function loadCallbackSimpletippGroups($varValue, \DataContainer $dc)
    {
        return $this->emptyValueLoadCallback('simpletippGroups');
    }

    public function loadCallbackLeagueGroups($varValue, \DataContainer $dc)
    {
        return $this->emptyValueLoadCallback('leagueGroups');
    }


    private function emptyValueLoadCallback($fieldName)
    {
        if (\Input::post($fieldName))
        {
            $this->handleSessionData($fieldName, \Input::post($fieldName));
        }
        else
        {
            $varValue = $this->handleSessionData($fieldName);
        }
        return $varValue;
    }

    private function handleSessionData($fieldName, $varValue=null)
    {
        $sessionKey = 'tl_simpletipp_tipp.'.$fieldName;
        $session    = \Session::getInstance();
        if ($varValue === null)
        {
            return $session->get($sessionKey);
        }
        else
        {
            $session->set($sessionKey, $varValue);
        }
    }

    public function processSubmittedTipps(\DataContainer $dc)
    {
        $member_id = intval($dc->activeRecord->member_id);
        $arrIds    = \Input::post('tippInserter_matchId');
        $arrTipps  = \Input::post('tippInserter_tipp');
        if (is_int($member_id) && $member_id != 0 && is_array($arrIds) && is_array($arrTipps) && count($arrIds) == count($arrTipps))
        {
            $arrIds   = array_map('intval', $arrIds);
            $arrTipps = array_map('trim', $arrTipps);

            for($i=0;$i<count($arrIds);$i++)
            {
                if(strlen($arrTipps[$i]) > 0)
                {
                    $objTipp = new SimpletippTippModel();
                    $objTipp->setRow([
                        'tstamp'     => time(),
                        'member_id'  => $member_id,
                        'match_id'   => intval($arrIds[$i]),
                        'tipp'       => $arrTipps[$i]
                    ]);
                    $objTipp->save();
                }
            }

        }
    }
}





