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

$GLOBALS['TL_CRON']['hourly'][]              = ['\Simpletipp\SimpletippEmailReminder', 'tippReminder'];
$GLOBALS['TL_CRON']['hourly'][]              = ['\Simpletipp\SimpletippMatchUpdater', 'updateMatches'];

$GLOBALS['TL_HOOKS']['addCustomRegexp'][]    = ['\Simpletipp\SimpletippCallbacks', 'addCustomRegexp'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][]  = ['\Simpletipp\SimpletippCallbacks', 'randomLine'];

$GLOBALS['BE_FFL']['pokalRanges']            = '\Simpletipp\Widgets\PokalRangesField';
$GLOBALS['BE_FFL']['tippInserter']           = '\Simpletipp\Widgets\TippInserterField';

$GLOBALS['TL_MODELS']['tl_simpletipp']       = '\Simpletipp\Models\SimpletippModel';
$GLOBALS['TL_MODELS']['tl_simpletipp_match'] = '\Simpletipp\Models\SimpletippMatchModel';
$GLOBALS['TL_MODELS']['tl_simpletipp_tipp']  = '\Simpletipp\Models\SimpletippTippModel';


array_insert($GLOBALS['FE_MOD']['simpletipp'], 0, [
    'simpletipp_calendar'   => '\Simpletipp\Modules\SimpletippCalendar',
    'simpletipp_matches'    => '\Simpletipp\Modules\SimpletippMatches',
    'simpletipp_match'      => '\Simpletipp\Modules\SimpletippMatch',
    'simpletipp_userselect' => '\Simpletipp\Modules\SimpletippUserselect',
    'simpletipp_questions'  => '\Simpletipp\Modules\SimpletippQuestions',
    'simpletipp_highscore'  => '\Simpletipp\Modules\SimpletippHighscore',
    'simpletipp_ranking'    => '\Simpletipp\Modules\SimpletippRanking',
    'simpletipp_pokal'      => '\Simpletipp\Modules\SimpletippModulePokal',
    'simpletipp_nottipped'  => '\Simpletipp\Modules\SimpletippNotTipped'
]);

array_insert($GLOBALS['TL_CTE']['simpletipp'], 0, [
    'simpletipp_statistics' => '\Simpletipp\Elements\ContentSimpletippStatistics',
]);

array_insert($GLOBALS['BE_MOD'], 1, [
		'simpletipp' => [
				'simpletipp_group' => [
						'tables'     => ['tl_simpletipp', 'tl_simpletipp_question'],
						'icon'       => 'system/modules/simpletipp/assets/images/soccer.png',
						'javascript' => 'system/modules/simpletipp/assets/simpletipp-backend.js',
						'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
                        'update'     => ['\Simpletipp\SimpletippMatchUpdater', 'updateMatches'],
                        'calculate'  => ['\Simpletipp\SimpletippMatchUpdater', 'calculateTipps'],
                        'pokal'      => ['\Simpletipp\SimpletippPokal', 'calculate'],
                        'reminder'   => ['\Simpletipp\SimpletippEmailReminder', 'tippReminder'],
				],
				'simpletipp_match' => [
						'tables'     => ['tl_simpletipp_match'],
						'icon'       => 'system/themes/default/images/tablewizard.gif',
                        'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
				],
				'simpletipp_tipp'   => [
						'tables'     => ['tl_simpletipp_tipp'],
						'icon'       => 'system/themes/default/images/tablewizard.gif',
                        'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
				]
				
		]
]);

$GLOBALS['simpletipp']['teamShortener'] = [
    'Borussia Dortmund'            => ['Dortmund', 'BVB', 'vereinslogos/dortmund.png'],
    'Bayern München'               => ['Bayern', 'FCB', 'vereinslogos/bayern.png'],
    'Hamburger SV'                 => ['HSV', 'HSV', 'vereinslogos/hamburg.png'],
    'SC Freiburg'                  => ['Freiburg', 'SCF', 'vereinslogos/freiburg.png'],
    'FC Schalke 04'                => ['Schalke', 'S04', 'vereinslogos/schalke.png'],
    'Borussia Mönchengladbach'     => ['Gladbach', 'GLA', 'vereinslogos/gladbach.png'],
    'Bayer 04 Leverkusen'          => ['Leverkusen', 'LEV', 'vereinslogos/leverkusen.png'],
    'Hannover 96'                  => ['Hannover', 'H96', 'vereinslogos/hannover.png'],
    'VfL Wolfsburg'                => ['Wolfsburg', 'WOL', 'vereinslogos/wolfsburg.png'],
    'TSG 1899 Hoffenheim'          => ['Hoffenheim', 'HOF', 'vereinslogos/hoffenheim.png'],
    '1. FC Nürnberg'               => ['Nürnberg', 'FCN', 'vereinslogos/nuernberg.png'],
    '1. FSV Mainz 05'              => ['Mainz', 'MAI', 'vereinslogos/mainz.png'],
    'VfB Stuttgart'                => ['Stuttgart', 'VFB', 'vereinslogos/stuttgart.png'],
    'FC Augsburg'                  => ['Augsburg', 'FCA', 'vereinslogos/augsburg.png'],
    'Eintracht Braunschweig'       => ['Braunschweig', 'BRA', 'vereinslogos/braunschweig.png'],
    'Werder Bremen'                => ['Werder', 'BRE', 'vereinslogos/werder.png'],
    'Hertha BSC'                   => ['Hertha', 'BSC', 'vereinslogos/hertha.png'],
    'Eintracht Frankfurt'          => ['Frankfurt', 'FRA', 'vereinslogos/frankfurt.png'],
    'Fortuna Düsseldorf'           => ['Düsseldorf', 'DUS', 'vereinslogos/duesseldorf.png'],
    'SpVgg Greuther Fuerth'        => ['Fürth', 'FUE', 'vereinslogos/fuerth.png'],
    'SC Paderborn 07'              => ['Paderborn','SCP', 'vereinslogos/paderborn.png'],
    '1. FC Köln'                   => ['Köln','1FC', 'vereinslogos/koeln.png'],
    'FC Ingolstadt 04'             => ['Ingolstadt','FCI', 'vereinslogos/ingolstadt.png'],
    'SV Darmstadt 98'              => ['Darmstadt','SCD', 'vereinslogos/darmstadt.png'],
];




