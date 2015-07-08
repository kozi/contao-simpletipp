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
use \Simpletipp\Models\SimpletippModel;

// simpletipp_group
$GLOBALS['TL_DCA']['tl_page']['palettes']['root']                   .= ';{simpletipp_legend:hide},simpletipp_group';

$GLOBALS['TL_DCA']['tl_page']['fields']['simpletipp_group'] = array
(
'label'                   => &$GLOBALS['TL_LANG']['tl_page']['simpletipp_group'],
'exclude'                 => true,
'inputType'               => 'select',
'options_callback'        => array('tl_page_simpletipp', 'getSimpletippGroups'),
'eval'                    => array('multiple' => false, 'mandatory' => false, 'tl_class'=>'w50'),
'sql'                     => "int(10) unsigned NOT NULL default '0'"
);


/**
 * Class tl_module_simpletipp
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2015
 * @author     Martin Kozianka <http://kozianka.de>
 * @package    Controller
 */
class tl_page_simpletipp extends Backend {

    public function getSimpletippGroups() {
        $arrGroups = array();
        $objModels = SimpletippModel::findAll(array('order' => 'title DESC'));

        if ($objModels === null) {
            return $arrGroups;
        }

        foreach($objModels as $objSimpletippModel) {
            $arrGroups[$objSimpletippModel->id] = $objSimpletippModel->title;
        }
        return $arrGroups;
    }
}



