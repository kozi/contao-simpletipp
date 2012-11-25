<?php

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


/**
 * Register the classes
 */
ClassLoader::addClasses(array(
	'Simpletipp'           => 'system/modules/simpletipp/Simpletipp.php',
	'SimpletippCalendar'   => 'system/modules/simpletipp/SimpletippCalendar.php',
	'SimpletippHighscore'  => 'system/modules/simpletipp/SimpletippHighscore.php',
	'SimpletippMatches'    => 'system/modules/simpletipp/SimpletippMatches.php',
	'SimpletippMatch'      => 'system/modules/simpletipp/SimpletippMatch.php',
	'SimpletippQuestions'  => 'system/modules/simpletipp/SimpletippQuestions.php',
	'SimpletippSettings'   => 'system/modules/simpletipp/SimpletippSettings.php',
	'SimpletippUserselect' => 'system/modules/simpletipp/SimpletippUserselect.php',
	'SimpletippCallbacks'  => 'system/modules/simpletipp/SimpletippCallbacks.php'
));

TemplateLoader::addFiles(array(
	'be_simpletipp_import' 			=> 'system/modules/simpletipp/templates',
	'be_simpletipp_settings' 		=> 'system/modules/simpletipp/templates',
	'be_simpletipp' 				=> 'system/modules/simpletipp/templates',
	'simpletipp_highscore_default'	=> 'system/modules/simpletipp/templates',
	'simpletipp_match_default' 		=> 'system/modules/simpletipp/templates',
	'simpletipp_matches_default' 	=> 'system/modules/simpletipp/templates',
	'simpletipp_questions_default' 	=> 'system/modules/simpletipp/templates',
	'simpletipp_matchfilter' 		=> 'system/modules/simpletipp/templates',
	'simpletipp_userselect' 		=> 'system/modules/simpletipp/templates',
));



