<?php

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


/**
 * Register the classes
 */
ClassLoader::addClasses(array(

    // Classes
	'OpenLigaDB'                    => 'system/modules/simpletipp/classes/OpenLigaDB.php',
    'Simpletipp'                    => 'system/modules/simpletipp/classes/Simpletipp.php',
    'SimpletippPoints'              => 'system/modules/simpletipp/classes/SimpletippPoints.php',
    'SimpletippModule'              => 'system/modules/simpletipp/classes/SimpletippModule.php',
	'SimpletippCallbacks'           => 'system/modules/simpletipp/classes/SimpletippCallbacks.php',

    // Models
    'MatchModel'                    => 'system/modules/simpletipp/models/MatchModel.php',
    'SimpletippModel'               => 'system/modules/simpletipp/models/SimpletippModel.php',

    // Modules
    'SimpletippUserselect'          => 'system/modules/simpletipp/modules/SimpletippUserselect.php',
    'SimpletippHighscore'           => 'system/modules/simpletipp/modules/SimpletippHighscore.php',
    'SimpletippMatches'             => 'system/modules/simpletipp/modules/SimpletippMatches.php',
    'SimpletippMatch'               => 'system/modules/simpletipp/modules/SimpletippMatch.php',
    'SimpletippQuestions'           => 'system/modules/simpletipp/modules/SimpletippQuestions.php',
    'SimpletippCalendar'            => 'system/modules/simpletipp/modules/SimpletippCalendar.php',
    'SimpletippRanking'             => 'system/modules/simpletipp/modules/SimpletippRanking.php',
    'SimpletippPokal'               => 'system/modules/simpletipp/modules/SimpletippPokal.php',

));

TemplateLoader::addFiles(array(

    // Templates
    'simpletipp_highscore_default'	=> 'system/modules/simpletipp/templates',
    'simpletipp_match_default' 		=> 'system/modules/simpletipp/templates',
    'simpletipp_matches_default' 	=> 'system/modules/simpletipp/templates',
    'simpletipp_questions_default' 	=> 'system/modules/simpletipp/templates',
    'simpletipp_matchfilter' 		=> 'system/modules/simpletipp/templates',
    'simpletipp_userselect' 		=> 'system/modules/simpletipp/templates',
    'simpletipp_ranking_default'	=> 'system/modules/simpletipp/templates',
    'simpletipp_pokal_default'   	=> 'system/modules/simpletipp/templates',
    'rss_podcast'                   => 'system/modules/simpletipp/templates',
));



