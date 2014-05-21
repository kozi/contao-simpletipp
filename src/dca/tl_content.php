<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2013 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012-2013 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


$GLOBALS['TL_DCA']['tl_content']['palettes']['simpletipp_statistics'] = '{type_legend},type,headline;{simpletipp_legend},simpletipp_group,simpletipp_statistics_type;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{invisible_legend:hide},invisible,start,stop';


/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['simpletipp_statistics_type'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_content']['simpletipp_statistics_type'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options'                 => ContentSimpletippStatistics::$types,
		'eval'                    => array('multiple' => false, 'mandatory' => true, 'tl_class'=>'w50'),
		'sql'                     => "varchar(32) NOT NULL default ''"
		
);

$GLOBALS['TL_DCA']['tl_content']['fields']['simpletipp_group'] = array
(
    'label'                   => &$GLOBALS['TL_LANG']['tl_content']['simpletipp_group'],
    'exclude'                 => true,
    'inputType'               => 'select',
    'options_callback'        => array('tl_content_simpletipp', 'getSimpletippGroups'),
    'eval'                    => array('multiple' => false, 'mandatory' => true, 'tl_class'=>'w50'),
    'sql'                     => "int(10) unsigned NOT NULL default '0'"

);



class tl_content_simpletipp extends Backend {

    public function getSimpletippGroups() {
        $groups = array();

        $result = $this->Database->execute(
            "SELECT id, title FROM tl_simpletipp ORDER BY title DESC");

        while($result->next()) {
            $groups[$result->id] = $result->title;
        }
        return $groups;
    }

}