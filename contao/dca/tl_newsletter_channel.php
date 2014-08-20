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


$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['onload_callback'][] = array('Simpletipp\SimpletippCallbacks', 'createNewsletterChannel');

$GLOBALS['TL_DCA']['tl_newsletter_channel']['fields']['simpletipp'] = array(
    'label' => "SIMPLETIPP",
    'sql'   => "char(1) NOT NULL default ''"
);
