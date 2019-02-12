<?php

$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] .= ';{simpletipp_legend:hide},simpletipp_group';

$GLOBALS['TL_DCA']['tl_page']['fields']['simpletipp_group'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['simpletipp_group'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_page_simpletipp', 'getSimpletippGroups'],
    'eval' => ['multiple' => false, 'mandatory' => false, 'tl_class' => 'w50', 'includeBlankOption' => true],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];

use Simpletipp\Models\SimpletippModel;

/**
 * Class tl_module_simpletipp
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2014-2019
 * @author     Martin Kozianka <http://kozianka.de>
 * @package    Controller
 */
class tl_page_simpletipp extends Backend
{

    public function getSimpletippGroups()
    {
        $arrGroups = [];
        $objModels = SimpletippModel::findAll(['order' => 'title DESC']);

        if ($objModels === null) {
            return $arrGroups;
        }

        foreach ($objModels as $objSimpletippModel) {
            $arrGroups[$objSimpletippModel->id] = $objSimpletippModel->title;
        }
        return $arrGroups;
    }
}
