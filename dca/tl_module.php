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



$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_matches'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_group,simpletipp_factor,simpletipp_template,simpletipp_match_page;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_highscore'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_group,simpletipp_matches_page,simpletipp_factor,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_match'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_group,simpletipp_matches_page,simpletipp_factor,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_questions'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_group,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_userselect'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_group;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_calendar'] =
'{title_legend},name,type;'
.'{simpletipp_legend},simpletipp_group,simpletipp_factor,simpletipp_matches_page;';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_ranking'] =
    '{title_legend},name,headline,type;'
    .'{simpletipp_legend},simpletipp_group,simpletipp_template;'
    .'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_pokal'] =
    '{title_legend},name,headline,type;'
    .'{simpletipp_legend},simpletipp_group,simpletipp_template;'
    .'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_nottipped'] =
    '{title_legend},name,headline,type;'
    .'{simpletipp_legend},simpletipp_group,simpletipp_template;'
    .'{expert_legend:hide},guests,cssID,space';

/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_group'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_group'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_simpletipp', 'getSimpletippGroups'),
		'eval'                    => array('multiple' => false, 'mandatory' => true, 'tl_class'=>'w50'),
		'sql'                     => "int(10) unsigned NOT NULL default '0'"
		
);

$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_match_page'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_match_page'],
		'exclude'                 => true,
		'inputType'               => 'pageTree',
		'eval'                    => array('fieldType'=>'radio', 'tl_class' => 'long'),
		'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_matches_page'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_matches_page'],
		'exclude'                 => true,
		'inputType'               => 'pageTree',
		'eval'                    => array('fieldType'=>'radio', 'tl_class' => 'long'),
		'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_factor'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_factor'],
		'exclude'                 => true,
		'default'                 => '3,2,1',
		'inputType'               => 'text',
		'eval'                    => array('rgxp'=> 'SimpletippFactor', 'mandatory' => true, 'tl_class'=>'w50'),
		'sql'                     => "varchar(16) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_template'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_template'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_simpletipp', 'getSimpletippTemplates'),
		'eval'                    => array('tl_class'=>'w50'),
		'sql'                     => "varchar(255) NOT NULL default ''"
);

/**
 * Class tl_module
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2005-2013
 * @author     Martin Kozianka <http://kozianka.de>
 * @package    Controller
 */
class tl_module_simpletipp extends Backend {

	/**
	 * Return all simpletipp_matches templates as array
	 * @param DataContainer
	 * @return array
	 */
	public function getSimpletippTemplates(DataContainer $dc) {
		$intPid = $dc->activeRecord->pid;
		$prefix = $dc->activeRecord->type.'_';
		
		if ($this->Input->get('act') == 'overrideAll') {
			$intPid = $this->Input->get('id');
			$prefix = 'simpletipp_';
		}
		
		return $this->getTemplateGroup($prefix, $intPid);
	}
		
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


