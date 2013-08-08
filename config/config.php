<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

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

$GLOBALS['simpletipp']['teamShortener'] = array(
    'Borussia Dortmund'            => array('Dortmund', 'BVB'),
    'Bayern München'               => array('Bayern', 'FCB'),
    'Hamburger SV'                 => array('HSV', 'HSV'),
    'SC Freiburg'                  => array('Freiburg', 'SCF'),
    'FC Schalke 04'                => array('Schalke', 'S04'),
    'Borussia Mönchengladbach'     => array('Gladbach', 'GLA'),
    'Bayer 04 Leverkusen'          => array('Leverkusen', 'LEV'),
    'Hannover 96'                  => array('Hannover', 'H96'),
    'VfL Wolfsburg'                => array('Wolfsburg', 'WOL'),
    'TSG 1899 Hoffenheim'          => array('Hoffenheim', 'HOF'),
    '1. FC Nürnberg'               => array('Nürnberg', 'FCN'),
    '1. FSV Mainz 05'              => array('Mainz', 'MAI'),
    'VfB Stuttgart'                => array('Stuttgart', 'VFB'),
    'FC Augsburg'                  => array('Augsburg', 'FCA'),
    'Eintracht Braunschweig'       => array('Braunschweig', 'BRA'),
    'Werder Bremen'                => array('Werder', 'BRE'),
    'Hertha BSC'                   => array('Hertha', 'BSC'),
    'Eintracht Frankfurt'          => array('Frankfurt', 'FRA'),
    'Fortuna Düsseldorf'           => array('Düsseldorf', 'DUS'),
    'SpVgg Greuther Fuerth'        => array('Fürth', 'FUE'),
);


$GLOBALS['TL_HOOKS']['addCustomRegexp'][]   = array('SimpletippCallbacks', 'addCustomRegexp');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('SimpletippCallbacks', 'randomLine');

array_insert($GLOBALS['FE_MOD']['simpletipp'], 0, array(
    'simpletipp_calendar'   => 'SimpletippCalendar',
    'simpletipp_matches'    => 'SimpletippMatches',
    'simpletipp_match'      => 'SimpletippMatch',
    'simpletipp_userselect' => 'SimpletippUserselect',
    'simpletipp_questions'  => 'SimpletippQuestions',
    'simpletipp_highscore'  => 'SimpletippHighscore',
    'simpletipp_ranking'    => 'SimpletippRanking',
    'simpletipp_pokal'      => 'SimpletippPokal',
    'simpletipp_nottipped'  => 'SimpletippNotTipped'
));

array_insert($GLOBALS['BE_MOD'], 1, array(
		'simpletipp' => array(
				'simpletipp_groups' => array
				(
						'tables'     => array('tl_simpletipp', 'tl_simpletipp_question', 'tl_simpletipp_pokal', 'tl_simpletipp_pokal_mapping'),
						'icon'       => 'system/modules/simpletipp/assets/images/soccer.png',
						'javascript' => 'system/modules/simpletipp/assets/simpletipp-backend.js',
						'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
						'settings'   => array('SimpletippSettings', 'settings'),
						'import'     => array('SimpletippSettings', 'importMatches'),
						'update'     => array('SimpletippCallbacks', 'updateMatches'),
				),
				'simpletipp_matches' => array
				(
						'tables'     => array('tl_simpletipp_match'),
						'icon'       => 'system/themes/default/images/tablewizard.gif',
                        'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
				),
				'simpletipp_tipps' => array
				(
						'tables'     => array('tl_simpletipp_tipp'),
						'icon'       => 'system/themes/default/images/tablewizard.gif',
                        'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
				)
				
		)
));


