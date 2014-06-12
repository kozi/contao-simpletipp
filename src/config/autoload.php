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


ClassLoader::addNamespace('Simpletipp');

ClassLoader::addClasses(array(

    // Classes
	'Simpletipp\OpenLigaDB'                    => 'system/modules/simpletipp/classes/OpenLigaDB.php',
    'Simpletipp\Simpletipp'                    => 'system/modules/simpletipp/classes/Simpletipp.php',
    'Simpletipp\SimpletippPoints'              => 'system/modules/simpletipp/classes/SimpletippPoints.php',
    'Simpletipp\SimpletippModule'              => 'system/modules/simpletipp/classes/SimpletippModule.php',
    'Simpletipp\SimpletippCallbacks'           => 'system/modules/simpletipp/classes/SimpletippCallbacks.php',
    'Simpletipp\SimpletippMatchUpdater'        => 'system/modules/simpletipp/classes/SimpletippMatchUpdater.php',
    'Simpletipp\SimpletippEmailReminder'       => 'system/modules/simpletipp/classes/SimpletippEmailReminder.php',
    'Simpletipp\SimpletippPokal'               => 'system/modules/simpletipp/classes/SimpletippPokal.php',
    'Simpletipp\PokalRangesField'              => 'system/modules/simpletipp/classes/PokalRangesField.php',

    // Models
    'Simpletipp\MatchModel'                    => 'system/modules/simpletipp/models/MatchModel.php',
    'Simpletipp\SimpletippModel'               => 'system/modules/simpletipp/models/SimpletippModel.php',

    // Modules
    'Simpletipp\SimpletippUserselect'          => 'system/modules/simpletipp/modules/SimpletippUserselect.php',
    'Simpletipp\SimpletippHighscore'           => 'system/modules/simpletipp/modules/SimpletippHighscore.php',
    'Simpletipp\SimpletippMatches'             => 'system/modules/simpletipp/modules/SimpletippMatches.php',
    'Simpletipp\SimpletippMatch'               => 'system/modules/simpletipp/modules/SimpletippMatch.php',
    'Simpletipp\SimpletippQuestions'           => 'system/modules/simpletipp/modules/SimpletippQuestions.php',
    'Simpletipp\SimpletippCalendar'            => 'system/modules/simpletipp/modules/SimpletippCalendar.php',
    'Simpletipp\SimpletippRanking'             => 'system/modules/simpletipp/modules/SimpletippRanking.php',
    'Simpletipp\SimpletippModulePokal'         => 'system/modules/simpletipp/modules/SimpletippModulePokal.php',
    'Simpletipp\SimpletippNotTipped'           => 'system/modules/simpletipp/modules/SimpletippNotTipped.php',

    // Elements
    'Simpletipp\ContentSimpletippStatistics'   => 'system/modules/simpletipp/elements/ContentSimpletippStatistics.php',

));

TemplateLoader::addFiles(array(

    // Templates
    'simpletipp_highscore_default'	   => 'system/modules/simpletipp/templates',
    'simpletipp_match_default' 		   => 'system/modules/simpletipp/templates',
    'simpletipp_matches_default' 	   => 'system/modules/simpletipp/templates',
    'simpletipp_matches_mobile' 	   => 'system/modules/simpletipp/templates',
    'simpletipp_questions_default' 	   => 'system/modules/simpletipp/templates',
    'simpletipp_filter' 		       => 'system/modules/simpletipp/templates',
    'simpletipp_filter_mobile'	       => 'system/modules/simpletipp/templates',
    'simpletipp_userselect' 		   => 'system/modules/simpletipp/templates',
    'simpletipp_ranking_default'	   => 'system/modules/simpletipp/templates',
    'simpletipp_pokal_default'   	   => 'system/modules/simpletipp/templates',
    'simpletipp_nottipped_default'     => 'system/modules/simpletipp/templates',
    'rss_podcast'                      => 'system/modules/simpletipp/templates',
    'ce_simpletipp_statistics'         => 'system/modules/simpletipp/templates',

    'simpletipp_statPoints'            => 'system/modules/simpletipp/templates',
    'simpletipp_statBestMatches'       => 'system/modules/simpletipp/templates',
    'simpletipp_statBestTeams'         => 'system/modules/simpletipp/templates',
    'simpletipp_statHighscoreTimeline' => 'system/modules/simpletipp/templates',
    'simpletipp_statSpecialMember'     => 'system/modules/simpletipp/templates',
));



