<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2018 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2018 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['onload_callback'][]  = ['Simpletipp\SimpletippCallbacks', 'createNewsletterChannel'];
$GLOBALS['TL_DCA']['tl_newsletter_channel']['fields']['simpletipp']         = ['sql'   => "int(10) unsigned NOT NULL default '0'"];
