<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

$GLOBALS['TL_CRON']['hourly'][]              = array('\Simpletipp\SimpletippEmailReminder', 'tippReminder');
$GLOBALS['TL_CRON']['hourly'][]              = array('\Simpletipp\SimpletippMatchUpdater', 'updateMatches');

$GLOBALS['TL_HOOKS']['addCustomRegexp'][]    = array('\Simpletipp\SimpletippCallbacks', 'addCustomRegexp');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][]  = array('\Simpletipp\SimpletippCallbacks', 'randomLine');

$GLOBALS['BE_FFL']['pokalRanges']            = '\Simpletipp\Widgets\PokalRangesField';
$GLOBALS['BE_FFL']['tippInserter']           = '\Simpletipp\Widgets\TippInserterField';

$GLOBALS['TL_MODELS']['tl_simpletipp']       = '\Simpletipp\Models\SimpletippModel';
$GLOBALS['TL_MODELS']['tl_simpletipp_match'] = '\Simpletipp\Models\SimpletippMatchModel';
$GLOBALS['TL_MODELS']['tl_simpletipp_tipp']  = '\Simpletipp\Models\SimpletippTippModel';


array_insert($GLOBALS['FE_MOD']['simpletipp'], 0, array(
    'simpletipp_calendar'   => '\Simpletipp\Modules\SimpletippCalendar',
    'simpletipp_matches'    => '\Simpletipp\Modules\SimpletippMatches',
    'simpletipp_match'      => '\Simpletipp\Modules\SimpletippMatch',
    'simpletipp_userselect' => '\Simpletipp\Modules\SimpletippUserselect',
    'simpletipp_questions'  => '\Simpletipp\Modules\SimpletippQuestions',
    'simpletipp_highscore'  => '\Simpletipp\Modules\SimpletippHighscore',
    'simpletipp_ranking'    => '\Simpletipp\Modules\SimpletippRanking',
    'simpletipp_pokal'      => '\Simpletipp\Modules\SimpletippModulePokal',
    'simpletipp_nottipped'  => '\Simpletipp\Modules\SimpletippNotTipped'
));

array_insert($GLOBALS['TL_CTE']['simpletipp'], 0, array(
    'simpletipp_statistics' => '\Simpletipp\Elements\ContentSimpletippStatistics',
));

array_insert($GLOBALS['BE_MOD'], 1, array(
		'simpletipp' => array(
				'simpletipp_group' => array
				(
						'tables'     => array('tl_simpletipp', 'tl_simpletipp_question'),
						'icon'       => 'system/modules/simpletipp/assets/images/soccer.png',
						'javascript' => 'system/modules/simpletipp/assets/simpletipp-backend.js',
						'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
                        'update'     => array('\Simpletipp\SimpletippMatchUpdater', 'updateMatches'),
                        'calculate'  => array('\Simpletipp\SimpletippMatchUpdater', 'calculateTipps'),
                        'pokal'      => array('\Simpletipp\SimpletippPokal', 'calculate'),
                        'reminder'   => array('\Simpletipp\SimpletippEmailReminder', 'tippReminder'),
				),
				'simpletipp_match' => array
				(
						'tables'     => array('tl_simpletipp_match'),
						'icon'       => 'system/themes/default/images/tablewizard.gif',
                        'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
				),
				'simpletipp_tipp'   => array
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
    'Borussia Dortmund'            => array('Dortmund', 'BVB', 'vereinslogos/dortmund.png'),
    'Bayern München'               => array('Bayern', 'FCB', 'vereinslogos/bayern.png'),
    'Hamburger SV'                 => array('HSV', 'HSV', 'vereinslogos/hamburg.png'),
    'SC Freiburg'                  => array('Freiburg', 'SCF', 'vereinslogos/freiburg.png'),
    'FC Schalke 04'                => array('Schalke', 'S04', 'vereinslogos/schalke.png'),
    'Borussia Mönchengladbach'     => array('Gladbach', 'GLA', 'vereinslogos/gladbach.png'),
    'Bayer 04 Leverkusen'          => array('Leverkusen', 'LEV', 'vereinslogos/leverkusen.png'),
    'Hannover 96'                  => array('Hannover', 'H96', 'vereinslogos/hannover.png'),
    'VfL Wolfsburg'                => array('Wolfsburg', 'WOL', 'vereinslogos/wolfsburg.png'),
    'TSG 1899 Hoffenheim'          => array('Hoffenheim', 'HOF', 'vereinslogos/hoffenheim.png'),
    '1. FC Nürnberg'               => array('Nürnberg', 'FCN', 'vereinslogos/nuernberg.png'),
    '1. FSV Mainz 05'              => array('Mainz', 'MAI', 'vereinslogos/mainz.png'),
    'VfB Stuttgart'                => array('Stuttgart', 'VFB', 'vereinslogos/stuttgart.png'),
    'FC Augsburg'                  => array('Augsburg', 'FCA', 'vereinslogos/augsburg.png'),
    'Eintracht Braunschweig'       => array('Braunschweig', 'BRA', 'vereinslogos/braunschweig.png'),
    'Werder Bremen'                => array('Werder', 'BRE', 'vereinslogos/werder.png'),
    'Hertha BSC'                   => array('Hertha', 'BSC', 'vereinslogos/hertha.png'),
    'Eintracht Frankfurt'          => array('Frankfurt', 'FRA', 'vereinslogos/frankfurt.png'),
    'Fortuna Düsseldorf'           => array('Düsseldorf', 'DUS', 'vereinslogos/duesseldorf.png'),
    'SpVgg Greuther Fuerth'        => array('Fürth', 'FUE', 'vereinslogos/fuerth.png'),
    'SC Paderborn 07'              => array('Paderborn','SCP', 'vereinslogos/paderborn.png'),
    '1. FC Köln'                   => array('Köln','1FC', 'vereinslogos/koeln.png'),
);




