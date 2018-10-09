<?php

$GLOBALS['TL_DCA']['tl_newsletter_channel']['config']['onload_callback'][] = ['Simpletipp\SimpletippCallbacks', 'createNewsletterChannel'];
$GLOBALS['TL_DCA']['tl_newsletter_channel']['fields']['simpletipp'] = ['sql' => "int(10) unsigned NOT NULL default '0'"];
