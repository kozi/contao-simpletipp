<?php

$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['onload_callback'][] = array('SimpletippCallbacks', 'createNewsletterChannel');

$GLOBALS['TL_DCA']['tl_newsletter_channel']['fields']['simpletipp'] = array(
    'label' => "SIMPLETIPP",
    'sql'   => "char(1) NOT NULL default ''"
);
