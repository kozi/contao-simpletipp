<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['simpletipp_statistics'] = '{type_legend},type,headline;{simpletipp_legend},simpletipp_statistics_type;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['simpletipp_statistics_type'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['simpletipp_statistics_type'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => \Simpletipp\Elements\ContentSimpletippStatistics::$types,
    'eval' => ['multiple' => false, 'mandatory' => true],
    'sql' => "varchar(32) NOT NULL default ''",
];
