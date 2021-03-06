<?php

$GLOBALS['TL_DCA']['tl_simpletipp'] = [

    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_simpletipp_question'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'onsubmit_callback' => [
            ['tl_simpletipp', 'saveLeagueData'],
            ['tl_simpletipp', 'updateTeamTable'],
            ['tl_simpletipp', 'updateMatches'],
        ],
        'ondelete_callback' => [
            ['tl_simpletipp', 'updateTeamTable'],
            ['tl_simpletipp', 'updateMatches'],
        ],
        'sql' => ['keys' => ['id' => 'primary']],
    ],

    // List
    'list' => [
        'sorting' => [

            'mode' => 2,
            'fields' => ['tstamp DESC'],
            'flag' => 1,
            'panelLayout' => 'limit',
        ],
        'label' => [
            'fields' => ['title', 'leagueID', 'participant_group', 'tstamp'],
            'showColumns' => true,
            'label_callback' => ['tl_simpletipp', 'labelCallback'],
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'reminder' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['reminder'],
                'href' => 'key=reminder',
                'class' => 'header_icon header_simpletipp_reminder',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
        'operations' => [
            'calculate' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['calculate'],
                'href' => 'key=calculate',
                'icon' => 'modules.gif',
            ],
            'update' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['update'],
                'href' => 'key=update',
                'icon' => 'sync.gif',
            ],
            'pokal' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['pokal'],
                'href' => 'key=pokal',
                'icon' => 'system/modules/simpletipp/assets/svg/pokal.svg',
            ],
            'questions' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['questions'],
                'href' => 'table=tl_simpletipp_question',
                'icon' => 'show.gif',
            ],
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
            ],
        ],
    ],

    // icon: WRENCH system/themes/flexible/icons/modules.svg
    // icon: (i)
    // icon: SYNC system/themes/flexible/icons/sync.svg
    // icon: send email bundles/contaonewsletter/send.svg

    // Palettes
    'palettes' => [
        'default' => '{simpletipp_legend}, title, leagueShortcut, leagueSaison, factor, matchLength, resultTypeIdFirst, resultTypeIdFinal, quizDeadline, adminName, adminEmail, teaser, participant_group, telegram_bot_key, telegram_url_token;{simpletipp_reminder_legend}, matches_page;{simpletipp_pokal_legend}, pokal_ranges',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['tstamp'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['title'],
            'exclude' => true,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 48],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'adminName' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['adminName'],
            'exclude' => true,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'adminEmail' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['adminEmail'],
            'exclude' => true,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'factor' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['factor'],
            'exclude' => true,
            'default' => '3,2,1',
            'flag' => 1,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'quizDeadline' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['quizDeadline'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'matchLength' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['matchLength'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '6300'",
        ],
        'leagueShortcut' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['leagueShortcut'],
            'exclude' => true,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'submitOnChange' => true],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'leagueSaison' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['leagueSaison'],
            'exclude' => true,
            'flag' => 1,
            'inputType' => 'select',
            'options_callback' => ['tl_simpletipp', 'getSaisons'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50', 'submitOnChange' => true, 'chosen' => true],
            'sql' => "int(10) unsigned NOT NULL default '2018'",
        ],
        'leagueID' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'leagueName' => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'resultTypeIdFirst' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['resultTypeIdFirst'],
            'exclude' => true,
            'flag' => 1,
            'inputType' => 'select',
            'options_callback' => ['tl_simpletipp', 'getResultTypes'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '1'",
        ],
        'resultTypeIdFinal' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['resultTypeIdFinal'],
            'exclude' => true,
            'flag' => 1,
            'inputType' => 'select',
            'options_callback' => ['tl_simpletipp', 'getResultTypes'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '2'",
        ],
        'teaser' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['teaser'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'long clr', 'style' => ' height:28px;', 'mandatory' => false],
            'sql' => "text NULL",
        ],
        'participant_group' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['participant_group'],
            'exclude' => true,
            'inputType' => 'radio',
            'foreignKey' => 'tl_member_group.name',
            'eval' => ['mandatory' => false, 'tl_class' => 'clr', 'mandatory' => true],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'matches_page' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['matches_page'],
            'exclude' => true,
            'inputType' => 'pageTree',
            'eval' => ['fieldType' => 'radio', 'tl_class' => 'long'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'telegram_bot_key' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['telegram_bot_key'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'telegram_url_token' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['telegram_url_token'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'lastChanged' => [
            'label' => ['lastChanged', 'lastChanged'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'lastLookup' => [
            'label' => ['lastLookup', 'lastLookup'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'lastRemindedMatch' => [
            'label' => ['lastRemindedMatch', 'lastRemindedMatch'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'pokal_ranges' => [
            'label' => &$GLOBALS['TL_LANG']['tl_simpletipp']['pokal_ranges'],
            'exclude' => true,
            'inputType' => 'pokalRanges',
            'eval' => ['tl_class' => 'tl_long'],
            'sql' => "blob NULL",
        ],

        'pokal_group' => ['sql' => "blob NULL"],
        'pokal_16' => ['sql' => "blob NULL"],
        'pokal_8' => ['sql' => "blob NULL"],
        'pokal_4' => ['sql' => "blob NULL"],
        'pokal_2' => ['sql' => "blob NULL"],
        'pokal_finale' => ['sql' => "blob NULL"],
    ],
];

use Simpletipp\Models\SimpletippModel;
use Simpletipp\OpenLigaDB;

/**
 * Class tl_simpletipp
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2014-2019
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 */

class tl_simpletipp extends \Backend
{
    private $memberGroups = [];
    private $matchGroupOptions = [];

    public function __construct()
    {
        parent::__construct();
        $this->cleanupMatches();
        $this->import('BackendUser', 'User');

        // Mitgliedergruppen holen
        $result = $this->Database->execute("SELECT id, name FROM tl_member_group ORDER BY id");
        while ($result->next()) {
            $this->memberGroups[$result->id] = $result->name;
        }
    }

    public function labelCallback($row, $label, DataContainer $dc, $args = null)
    {
        if ($args === null) {
            return $label;
        }

        $args[1] = sprintf('<span title="%s">%s, %s</span>', $row['leagueName'], $row['leagueShortcut'], $row['leagueSaison']);
        $groupId = $args[2];
        $args[2] = $this->memberGroups[$groupId];
        $args[3] = date($GLOBALS['TL_CONFIG']['datimFormat'], $args[3]);

        return $args;
    }

    public function getResultTypes(DataContainer $dc)
    {
        $arrOptions = [];

        $arrOptions["1"] = "Ergebnis zur Halbzeit";
        $arrOptions["2"] = "Ergebnis nach 90 Minuten";

        return $arrOptions;
    }

    public function getSaisons(DataContainer $dc)
    {
        $arrOptions = [];
        $maxYear = intval(date("Y")) + 2;
        $minYear = 2002;

        for ($y = $maxYear; $y >= $minYear; $y--) {
            $arrOptions["" . $y] = "" . $y;
        }
        return $arrOptions;
    }

    public function getMatchgroups(DataContainer $dc)
    {
        if (count($this->matchGroupOptions) == 0) {
            $this->matchGroupOptions[''] = '-';
            $leagueID = intval($dc->activeRecord->leagueID);
            $groups = SimpletippModel::getLeagueGroups($leagueID);
            foreach ($groups as $g) {
                $this->matchGroupOptions[$g->title] = $g->title;
            }
        }
        return $this->matchGroupOptions;
    }

    public function saveLeagueData(DataContainer $dc)
    {
        $newID = 0;
        $newName = "";

        // Get and save leagueID
        $leagueData = OpenLigaDB::getLeagueData($dc->activeRecord->leagueShortcut, $dc->activeRecord->leagueSaison);
        if (!is_null($leagueData)) {
            $newID = $leagueData->id;
            $newName = $leagueData->name;
        }

        $objSimpletipp = SimpletippModel::findByPk($dc->activeRecord->id);
        $objSimpletipp->leagueID = $newID;
        $objSimpletipp->leagueName = $newName;
        $objSimpletipp->save();
    }

    public function updateMatches()
    {
        $this->import('\Simpletipp\SimpletippMatchUpdater', 'SimpletippMatchUpdater');
        $this->SimpletippMatchUpdater->updateMatches();
    }

    public function updateTeamTable()
    {
        $this->import('\Simpletipp\SimpletippMatchUpdater', 'SimpletippMatchUpdater');
        $this->SimpletippMatchUpdater->updateTeamTable();
    }

    private function cleanupMatches()
    {
        $this->Database->execute("DELETE FROM tl_simpletipp_match
			WHERE leagueID NOT IN (SELECT tl_simpletipp.leagueID FROM tl_simpletipp)");
    }
}
