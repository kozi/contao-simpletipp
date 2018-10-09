<?php

$GLOBALS['TL_DCA']['tl_simpletipp_answer'] = [

    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_simpletipp_question',
        'dataContainer' => 'Table',
        'sql' => ['keys' => ['id' => 'primary']],
        'notEditable' => true,
        'closed' => true,
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['pid'],
            'flag' => 1,
            'panelLayout' => 'limit',
        ],
        'label' => [
            'fields' => ['pid', 'ans'],
            'showColumns' => true,
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
        'operations' => [

            'toggle' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['toggle'],
                'icon' => 'visible.gif',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => ['tl_simpletipp_question', 'toggleIcon'],
            ],
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{legend}, question, points, answers;{legend_importer}, importer;',
    ],

    // Fields
    'fields' => [
        'id' => [
            'label' => ['ID'],
            'search' => false,
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'pid' => [
            'label' => ['PID'],
            'search' => false,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp' => [
            'label' => ['TSTAMP'],
            'search' => false,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'member' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp_answer']['member'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'answer' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp_answer']['answer'],
            'eval' => ['decodeEntities' => false],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
    ],
];
