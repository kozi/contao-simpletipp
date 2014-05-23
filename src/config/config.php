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

$GLOBALS['TL_CRON']['hourly'][]             = array('SimpletippEmailReminder', 'tippReminder');
$GLOBALS['TL_CRON']['hourly'][]             = array('SimpletippMatchUpdater', 'updateMatches');

$GLOBALS['TL_HOOKS']['addCustomRegexp'][]   = array('SimpletippCallbacks', 'addCustomRegexp');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('SimpletippCallbacks', 'randomLine');

$GLOBALS['BE_FFL']['pokalRanges']           = 'PokalRangesField';

array_insert($GLOBALS['FE_MOD']['simpletipp'], 0, array(
    'simpletipp_calendar'   => 'SimpletippCalendar',
    'simpletipp_matches'    => 'SimpletippMatches',
    'simpletipp_match'      => 'SimpletippMatch',
    'simpletipp_userselect' => 'SimpletippUserselect',
    'simpletipp_questions'  => 'SimpletippQuestions',
    'simpletipp_highscore'  => 'SimpletippHighscore',
    'simpletipp_ranking'    => 'SimpletippRanking',
    'simpletipp_pokal'      => 'SimpletippModulePokal',
    'simpletipp_nottipped'  => 'SimpletippNotTipped'
));

array_insert($GLOBALS['TL_CTE']['simpletipp'], 0, array(
    'simpletipp_statistics' => 'ContentSimpletippStatistics',
));

array_insert($GLOBALS['BE_MOD'], 1, array(
		'simpletipp' => array(
				'simpletipp_groups' => array
				(
						'tables'     => array('tl_simpletipp', 'tl_simpletipp_question'),
						'icon'       => 'system/modules/simpletipp/assets/images/soccer.png',
						'javascript' => 'system/modules/simpletipp/assets/simpletipp-backend.js',
						'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
                        'update'     => array('SimpletippMatchUpdater', 'updateMatches'),
                        'calculate'  => array('SimpletippMatchUpdater', 'calculateTipps'),
                        'pokal'      => array('SimpletippPokal', 'calculate'),
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


// leagueID 676 WM-2014
$GLOBALS['simpletipp']['groupNames'][676] = array(
    'Gruppe A' => array('Brasilien', 'Kroatien', 'Mexiko', 'Kamerun'),
    'Gruppe B' => array('Spanien', 'Niederlande', 'Chile', 'Australien'),
    'Gruppe C' => array('Kolumbien', 'Griechenland', 'Elfenbeinküste', 'Japan'),
    'Gruppe D' => array('Uruguay', 'Costa Rica', 'England', 'Italien'),
    'Gruppe E' => array('Schweiz', 'Ecuador', 'Frankreich', 'Honduras'),
    'Gruppe F' => array('Argentinien', 'Bosnien-Herz.', 'Iran', 'Nigeria'),
    'Gruppe G' => array('Deutschland', 'Portugal', 'Ghana', 'USA'),
    'Gruppe H' => array('Belgien', 'Algerien', 'Russland', 'Südkorea'),
);


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




