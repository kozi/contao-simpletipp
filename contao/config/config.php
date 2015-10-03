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
$GLOBALS['TL_MODELS']['tl_simpletipp_team']  = '\Simpletipp\Models\SimpletippTeamModel';

array_insert($GLOBALS['FE_MOD']['simpletipp'], 0, [
    'simpletipp_calendar'   => '\Simpletipp\Modules\SimpletippCalendar',
    'simpletipp_matches'    => '\Simpletipp\Modules\SimpletippMatches',
    'simpletipp_match'      => '\Simpletipp\Modules\SimpletippMatch',
    'simpletipp_userselect' => '\Simpletipp\Modules\SimpletippUserselect',
    'simpletipp_questions'  => '\Simpletipp\Modules\SimpletippQuestions',
    'simpletipp_highscore'  => '\Simpletipp\Modules\SimpletippHighscore',
    'simpletipp_ranking'    => '\Simpletipp\Modules\SimpletippRanking',
    'simpletipp_pokal'      => '\Simpletipp\Modules\SimpletippModulePokal',
    'simpletipp_nottipped'  => '\Simpletipp\Modules\SimpletippNotTipped',
    'simpletipp_telegram'   => '\Simpletipp\Modules\SimpletippTelegram'
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
            'icon'       => 'system/modules/simpletipp/assets/images/chain.png',
            'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
        ],
        'simpletipp_tipp' => [
            'tables'     => ['tl_simpletipp_tipp'],
            'icon'       => 'system/modules/simpletipp/assets/images/light-bulb.png',
            'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
        ],
        'simpletipp_team' => [
            'tables'     => ['tl_simpletipp_team'],
            'icon'       => 'system/modules/simpletipp/assets/images/clipboard-list.png',
            'stylesheet' => 'system/modules/simpletipp/assets/simpletipp-backend.css',
        ],
    ]
]);

// Bundesliga Teams
$GLOBALS['simpletipp']['teamData'] = [
      7 => ['Dortmund',     'BVB', 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/67/Borussia_Dortmund_logo.svg/1000px-Borussia_Dortmund_logo.svg.png'],
     40 => ['Bayern',       'FCB', 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Logo_FC_Bayern_M%C3%BCnchen.svg/1000px-Logo_FC_Bayern_M%C3%BCnchen.svg.png'],
    100 => ['HSV',          'HSV', 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/66/HSV-Logo.svg/1000px-HSV-Logo.svg.png'],
    112 => ['Freiburg',     'SCF', 'https://upload.wikimedia.org/wikipedia/de/thumb/f/f1/SC-Freiburg_Logo-neu.svg/1000px-SC-Freiburg_Logo-neu.svg.png'],
      9 => ['Schalke',      'S04', 'https://upload.wikimedia.org/wikipedia/de/thumb/6/6d/FC_Schalke_04_Logo.svg/1000px-FC_Schalke_04_Logo.svg.png'],
     87 => ['Gladbach',     'GLA', 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/81/Borussia_M%C3%B6nchengladbach_logo.svg/1000px-Borussia_M%C3%B6nchengladbach_logo.svg.png'],
      6 => ['Leverkusen',   'LEV', 'https://upload.wikimedia.org/wikipedia/de/thumb/9/95/Bayer_04_Leverkusen_Logo.svg/1000px-Bayer_04_Leverkusen_Logo.svg.png'],
     55 => ['Hannover',     'H96', 'https://upload.wikimedia.org/wikipedia/de/thumb/c/cd/Hannover_96_Logo.svg/1000px-Hannover_96_Logo.svg.png'],
    131 => ['Wolfsburg',    'WOL', 'https://upload.wikimedia.org/wikipedia/de/thumb/c/ce/VfL_Wolfsburg_Logo.svg/1000px-VfL_Wolfsburg_Logo.svg.png'],
    123 => ['Hoffenheim',   'HOF', 'https://upload.wikimedia.org/wikipedia/de/thumb/e/e7/Logo_TSG_Hoffenheim.svg/1000px-Logo_TSG_Hoffenheim.svg.png'],
     79 => ['Nürnberg',     'FCN', 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/1._FC_N%C3%BCrnberg_logo.svg/1000px-1._FC_N%C3%BCrnberg_logo.svg.png'],
     81 => ['Mainz',        'MAI', 'https://upload.wikimedia.org/wikipedia/de/thumb/0/0b/FSV_Mainz_05_Logo.svg/1000px-FSV_Mainz_05_Logo.svg.png'],
     16 => ['Stuttgart',    'VFB', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/eb/VfB_Stuttgart_1893_Logo.svg/1000px-VfB_Stuttgart_1893_Logo.svg.png'],
     95 => ['Augsburg',     'FCA', 'https://upload.wikimedia.org/wikipedia/de/thumb/b/b5/Logo_FC_Augsburg.svg/1000px-Logo_FC_Augsburg.svg.png'],
     74 => ['Braunschweig', 'BRA', 'https://upload.wikimedia.org/wikipedia/de/thumb/4/45/Logo_Eintracht_Braunschweig.svg/1000px-Logo_Eintracht_Braunschweig.svg.png'],
    134 => ['Werder',       'BRE', 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/be/SV-Werder-Bremen-Logo.svg/1000px-SV-Werder-Bremen-Logo.svg.png'],
     54 => ['Hertha',       'BSC', 'https://upload.wikimedia.org/wikipedia/de/thumb/8/81/Hertha_BSC_Logo_2012.svg/1000px-Hertha_BSC_Logo_2012.svg.png'],
     91 => ['Frankfurt',    'FRA', 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/04/Eintracht_Frankfurt_Logo.svg/1000px-Eintracht_Frankfurt_Logo.svg.png'],
    185 => ['Düsseldorf',   'DUS', 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/94/Fortuna_D%C3%BCsseldorf.svg/1000px-Fortuna_D%C3%BCsseldorf.svg.png'],
    115 => ['Fürth',        'FUE', 'https://upload.wikimedia.org/wikipedia/de/thumb/6/6d/SpVgg_Greuther_F%C3%BCrth_logo.svg/1000px-SpVgg_Greuther_F%C3%BCrth_logo.svg.png'],
     31 => ['Paderborn',    'SCP', 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e3/SC_Paderborn_07_Logo.svg/1000px-SC_Paderborn_07_Logo.svg.png'],
     65 => ['Köln',         '1FC', 'https://upload.wikimedia.org/wikipedia/de/thumb/1/16/1._FC_K%C3%B6ln.svg/1000px-1._FC_K%C3%B6ln.svg.png'],
    171 => ['Ingolstadt',   'FCI', 'https://upload.wikimedia.org/wikipedia/de/thumb/5/55/FC-Ingolstadt_logo.svg/1000px-FC-Ingolstadt_logo.svg.png'],
    118 => ['Darmstadt',    'SVD', 'https://upload.wikimedia.org/wikipedia/de/thumb/8/87/Svdarmstadt98.svg/1000px-Svdarmstadt98.svg.png'],
];


