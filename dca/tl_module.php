<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2012
 * @author     Leo Feyer <http://www.contao.org>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_matches'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_match_page,simpletipp_factor,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_highscore'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_groups,simpletipp_matches_page,simpletipp_factor,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_match'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_matches_page,simpletipp_factor,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['simpletipp_questions'] =
'{title_legend},name,headline,type;'
.'{simpletipp_legend},simpletipp_groups,simpletipp_template;'
.'{expert_legend:hide},guests,cssID,space';



/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_groups'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_groups'],
		'exclude'                 => true,
		'inputType'               => 'select',
		'options_callback'        => array('tl_module_simpletipp', 'getSimpletippGroups'),
		'eval'                    => array('multiple' => true, 'mandatory' => true, 'tl_class'=>'long')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_match_page'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_match_page'],
		'exclude'                 => true,
		'inputType'               => 'pageTree',
		'eval'                    => array('fieldType'=>'radio')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_matches_page'] = array
(
		'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_matches_page'],
		'exclude'                 => true,
		'inputType'               => 'pageTree',
		'eval'                    => array('fieldType'=>'radio')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_factor'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_factor'],
	'exclude'                 => true,
	'default'                 => '3,2,1',
	'inputType'               => 'text',
	'eval'                    => array('rgxp'=> 'SimpletippFactor', 'mandatory' => true, 'tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_module']['fields']['simpletipp_template'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['simpletipp_template'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_simpletipp', 'getSimpletippTemplates'),
	'eval'                    => array('tl_class'=>'w50')
);


/**
 * Class tl_module
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2005-2012
 * @author     Martin Kozianka <http://www.kozianka-online.de>
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
		$groups[0] = 'All Simpletipp groups';
		
		$result = $this->Database->execute(
			"SELECT id, competition, matchgroup FROM tl_simpletipp ORDER BY competition DESC, matchgroup DESC");
		
		while($result->next()) {
			$groups[$result->id] = $result->competition.' '.$result->matchgroup;
		}
		return $groups;
	}
}


