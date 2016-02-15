<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2016 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2016 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['onload_callback'][]  = ['Simpletipp\SimpletippCallbacks', 'createNewsletterChannel'];
$GLOBALS['TL_DCA']['tl_newsletter_channel']['fields']['simpletipp']         = ['sql'   => "char(1) NOT NULL default ''"];
