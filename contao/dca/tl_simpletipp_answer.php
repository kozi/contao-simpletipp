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

/*
CREATE TABLE `tl_simpletipp_answer` (
`id` int(10) unsigned NOT NULL auto_increment,
`pid` int(10) unsigned NOT NULL default '0',
`tstamp` int(10) unsigned NOT NULL default '0',
`member_id` int(10) unsigned NOT NULL default '0',
`answer` varchar(128) NOT NULL default '',
PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/

$GLOBALS['TL_DCA']['tl_simpletipp_answer'] = array(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_simpletipp_question',
	    'dataContainer'               => 'Table',
	    'sql' => array(
            'keys' => array('id' => 'primary')
        ),
	    'notEditable'                 => true,
	    'closed'                      => true,
    ),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('pid'),
			'flag'                    => 1,
			'panelLayout'             => 'limit',
		),
		'label' => array
		(
			'fields'                  => array('pid', 'ans'),
			'showColumns'             => true,
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
			'toggle' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['toggle'],
					'icon'                => 'visible.gif',
					'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
					'button_callback'     => array('tl_simpletipp_question', 'toggleIcon')
			),
			'edit' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['edit'],
					'href'                => 'act=edit',
					'icon'                => 'edit.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'					=> '{legend}, question, points, answers;{legend_importer}, importer;',
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
        'member' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_answer']['member'],
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
        ),
		'answer' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_answer']['answer'],
            'eval'                    => array('decodeEntities' => false),
            'sql'                     => "varchar(255) NOT NULL default ''",
        )
		
		
	)
);