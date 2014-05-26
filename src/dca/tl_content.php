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

$GLOBALS['TL_DCA']['tl_content']['palettes']['simpletipp_statistics'] = '{type_legend},type,headline;{simpletipp_legend},simpletipp_statistics_type;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{invisible_legend:hide},invisible,start,stop';


/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['simpletipp_statistics_type'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_content']['simpletipp_statistics_type'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options'                 => ContentSimpletippStatistics::$types,
		'eval'                    => array('multiple' => false, 'mandatory' => true),
		'sql'                     => "varchar(32) NOT NULL default ''"
		
);