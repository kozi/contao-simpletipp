<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2015 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2015 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

// simpletipp_email

$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ';{simpletipp_legend},simpletipp_calendar, simpletipp_email_reminder, simpletipp_email_confirmation';

$GLOBALS['TL_DCA']['tl_member']['fields']['simpletipp_email_confirmation'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['simpletipp_email_confirmation'],
    'default'                 => '1',
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class'=>'w50', 'feEditable'=>true, 'feGroup'=>'simpletipp'],
    'sql'                     => "char(1) NOT NULL default '1'"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['simpletipp_email_reminder'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['simpletipp_email_reminder'],
    'default'                 => '1',
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class'=>'w50', 'feEditable'=>true, 'feGroup'=>'simpletipp'],
    'sql'                     => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_member']['fields']['simpletipp_calendar'] = [
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['simpletipp_calendar'],
    'default'                 => '',
    'exclude'                 => true,
    'inputType'               => 'text',
    'sql'                     => "varchar(255) NOT NULL default '1'",
    'load_callback'           => [['tl_member_simpletipp', 'generateUniqid']],
    'eval'                    => [],
];

$GLOBALS['TL_DCA']['tl_member']['fields']['simpletipp_bot_secret'] = ['sql' => "varchar(255) NOT NULL default '1'"];
$GLOBALS['TL_DCA']['tl_member']['fields']['telegram_chat_id']      = ['sql' => "varchar(255) NOT NULL default '1'"];


class tl_member_simpletipp
{
    public function generateUniqid($varValue)
    {
        if ($varValue == '')
        {
            $varValue = str_replace('.', 'cal', uniqid('', true));
        }
        return $varValue;
    }

}
