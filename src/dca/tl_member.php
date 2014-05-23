<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

// simpletipp_email

$GLOBALS['TL_DCA']['tl_member']['palettes']['default'] .= ';{simpletipp_legend},simpletipp_calendar, simpletipp_email_reminder, simpletipp_email_confirmation';

$GLOBALS['TL_DCA']['tl_member']['fields']['simpletipp_email_confirmation'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['simpletipp_email_confirmation'],
    'default'                 => '1',
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50', 'feEditable'=>true, 'feGroup'=>'simpletipp'),
    'sql'                     => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['simpletipp_email_reminder'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['simpletipp_email_reminder'],
    'default'                 => '1',
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'w50', 'feEditable'=>true, 'feGroup'=>'simpletipp'),
    'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_member']['fields']['simpletipp_calendar'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_member']['simpletipp_calendar'],
    'default'                 => '',
    'exclude'                 => true,
    'inputType'               => 'text',
    'sql'                     => "varchar(255) NOT NULL default '1'",
    'load_callback'           => array
    (
        array('tl_member_simpletipp', 'generateUniqid')
    ),
    'eval'                    => array(),

);


class tl_member_simpletipp {

    public function generateUniqid($varValue) {
        if ($varValue == '') {
            $varValue = str_replace('.', 'cal', uniqid('', true));
        }
        return $varValue;
    }

}
