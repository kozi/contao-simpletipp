<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

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
