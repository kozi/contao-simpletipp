<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5.3
 * @copyright  Martin Kozianka 2012
 * @author     Martin Kozianka <http://kozianka-online.de>
 * @package    simpletipp
 * @license    LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array(
	'Simpletipp'           => 'system/modules/simpletipp/Simpletipp.php',
	'SimpletippHighscore'  => 'system/modules/simpletipp/SimpletippHighscore.php',
	'SimpletippMatches'    => 'system/modules/simpletipp/SimpletippMatches.php',
	'SimpletippMatch'      => 'system/modules/simpletipp/SimpletippMatch.php',
	'SimpletippQuestions'  => 'system/modules/simpletipp/SimpletippQuestions.php',
	'SimpletippSettings'   => 'system/modules/simpletipp/SimpletippSettings.php',
	
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
	'simpletipp_userselect' 		=> 'system/modules/simpletipp/templates'
));



