<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012 <http://kozianka-online.de/>
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


$GLOBALS['TL_HOOKS']['addCustomRegexp'][] = array('SimpletippCallbacks', 'addCustomRegexp');



array_insert($GLOBALS['FE_MOD']['simpletipp'], 0, array(
		'simpletipp_matches'    => 'SimpletippMatches',
		'simpletipp_calendar'   => 'SimpletippCalendar',
		'simpletipp_match'      => 'SimpletippMatch',
		'simpletipp_userselect' => 'SimpletippUserselect',
		'simpletipp_questions'  => 'SimpletippQuestions',
		'simpletipp_highscore'  => 'SimpletippHighscore'
));

array_insert($GLOBALS['BE_MOD'], 1, array(
		'content' => array(
				'simpletipp' => array
				(
						'tables'     => array('tl_simpletipp', 'tl_simpletipp_questions', 'tl_simpletipp_pokal', 'tl_simpletipp_pokal_mapping'),
						'icon'       => 'system/modules/simpletipp/html/soccer.png',
						'javascript' => 'system/modules/simpletipp/html/be_script.js',
						'stylesheet' => 'system/modules/simpletipp/html/be_style.css',
						'settings'   => array('SimpletippSettings', 'settings'),
						'import'     => array('SimpletippSettings', 'importMatches'),
				),
		)
));


