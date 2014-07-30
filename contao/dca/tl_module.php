<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */



$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_matches'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_template,simpletipp_match_page;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_highscore'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_matches_page,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_match'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_matches_page,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_questions'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_userselect'] =
'{title_legend},name,headline,type;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_calendar'] =
'{title_legend},name,type;'
.'{simpletipp_legend},simpletipp_matches_page;';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_ranking'] =
    '{title_legend},name,headline,type;'
    .'{simpletipp_legend},simpletipp_template;'
    .'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_pokal'] =
    '{title_legend},name,headline,type;'
    .'{simpletipp_legend},simpletipp_template;'
    .'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_nottipped'] =
    '{title_legend},name,headline,type;'
    .'{simpletipp_legend},simpletipp_template;'
    .'{expert_legend:hide},guests,cssID,space';

/**
 * Add fields to tl_module
 */
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
 * Class tl_module_simpletipp
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2014
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

}

