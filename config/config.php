<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

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
 * PHP version 5
 * @copyright  Martin Kozianka 2012 
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    simpletipp 
 * @license    GNU/LGPL 
 * @filesource
 */

$GLOBALS['TL_HOOKS']['addCustomRegexp'][] = array('SimpletippCallbacks', 'addCustomRegexp');



array_insert($GLOBALS['FE_MOD']['simpletipp'], 0, array(
		'simpletipp_matches'   => 'SimpletippMatches',
		'simpletipp_match'     => 'SimpletippMatch',
		'simpletipp_highscore' => 'SimpletippHighscore'
));

array_insert($GLOBALS['BE_MOD'], 1, array(
		'content' => array(
				'simpletipp' => array
				(
						'tables'     => array('tl_simpletipp'),
						'icon'       => 'system/modules/simpletipp/html/soccer.png',
						'stylesheet' => 'system/modules/simpletipp/html/be_style.css',
						'settings'   => array('SimpletippSettings', 'settings'),
						'import'     => array('SimpletippSettings', 'importMatches'),
				),
		)
));


