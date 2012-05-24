<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

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
 * @license    LGPL 
 * @filesource
 */


/**
 * Class SimpletippCallbacks
 *
 * Provide methods to import matches
 * @copyright  Martin Kozianka 2012 
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    Controller
 */
class SimpletippCallbacks extends Backend {

	public function __construct() {
		parent::__construct();
	}

	public function addCustomRegexp($strRegexp, $varValue, Widget $objWidget) {
		if ($strRegexp == 'SimpletippFactor') {
			if (!preg_match('#^[0-9]{1,6},[0-9]{1,6},[0-9]{1,6}$#', $varValue)) {
				$objWidget->addError('Format must be <strong>NUMBER,NUMBER,NUMBER</strong>.');
			}
			return true;
		}
		return false;
	}
}
