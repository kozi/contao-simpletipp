<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012-2014 <http://kozianka.de/>
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
		'ptable'                      => 'tl_simpletipp',
		'enableVersioning'            => false
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('sorting'),
			'flag'                    => 1,
			'panelLayout'             => 'limit',
			'child_record_callback'   => array('tl_simpletipp_question', 'addQuestions'),
			'headerFields'            => array('title', 'teaser', 'tstamp')
		),
		'label' => array
		(
			'fields'                  => array('question', 'points'),
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
		'question' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['question'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'tl_class' => 'long')
		),
		'points' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['points'],
				'exclude'                 => true,
				'inputType'               => 'text',
				'default'				  => 1,
				'eval'					  => array('rgxp' => 'number','mandatory'=>true, 'tl_class' => 'w50')
		),
		'published' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['published'],
				'exclude'                 => true,
				'inputType'               => 'checkbox',
				'eval'                    => array('doNotCopy'=>true, 'tl_class' => 'w50')
		),
		'importer' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['importer'],
				'inputType'               => 'textarea',
				'exclude'                 => true,
				'eval'                    => array('doNotShow'=>true, 'decodeEntities' => true),
				'load_callback'           => array(
						array('tl_simpletipp_question', 'clearImporter')
				),
				'save_callback'           => array(
						array('tl_simpletipp_question', 'importAnswers')
				),
				
		),
		'answers' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['answers'],
			'exclude'                 => true,
			'inputType'               => 'listWizard',
			'eval'					  => array('tl_class' => 'long clr' , 'mandatory' => false)
		)
		
		
	)
);