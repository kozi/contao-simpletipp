<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

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
 * @copyright  Martin Kozianka 2012
 * @author     Martin Kozianka <http://kozianka-online.de>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */



$GLOBALS['TL_DCA']['tl_simpletipp_questions'] = array(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_simpletipp',
		'enableVersioning'            => false
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('sorting'),
			'flag'                    => 1,
			'panelLayout'             => 'limit',
			'child_record_callback'   => array('tl_simpletipp_questions', 'addQuestions'),
			'headerFields'            => array('competition', 'matchgroup', 'deadline', 'teaser')
		),
		'label' => array
		(
			'fields'                  => array('question', 'points'),
			'showColumns'             => true,
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
		),
		'operations' => array
		(
			'toggle' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['toggle'],
					'icon'                => 'visible.gif',
					'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
					'button_callback'     => array('tl_simpletipp_questions', 'toggleIcon')
			),
			'edit' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['edit'],
					'href'                => 'act=edit',
					'icon'                => 'edit.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'					=> '{legend}, question, points, answers;{legend_importer}, importer;',
	),

	// Fields
	'fields' => array
	(
		'question' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['question'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'tl_class' => 'long')
		),
		'points' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['points'],
				'exclude'                 => true,
				'inputType'               => 'text',
				'default'				  => 1,
				'eval'					  => array('rgxp' => 'number','mandatory'=>true, 'tl_class' => 'w50')
		),
		'published' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['published'],
				'exclude'                 => true,
				'inputType'               => 'checkbox',
				'eval'                    => array('doNotCopy'=>true, 'tl_class' => 'w50')
		),
		'importer' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['importer'],
				'inputType'               => 'textarea',
				'exclude'                 => true,
				'eval'                    => array('doNotShow'=>true, 'decodeEntities' => true),
				'load_callback'           => array(
						array('tl_simpletipp_questions', 'clearImporter')
				),
				'save_callback'           => array(
						array('tl_simpletipp_questions', 'importAnswers')
				),
				
		),
		'answers' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['answers'],
			'exclude'                 => true,
			'inputType'               => 'listWizard',
			'eval'					  => array('tl_class' => 'long clr' , 'mandatory' => false)
		)
		
		
	)
);


/**
 * Class tl_simpletipp_questions
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    simpletipp
 */

class tl_simpletipp_questions extends Backend {

	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {
		
		if (strlen($this->Input->get('tid'))) {
			$this->toggleVisibility($this->Input->get('tid'), ($this->Input->get('state') == 1));
			$this->redirect($this->getReferer());
		}
	
		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_simpletipp_questions::published', 'alexf')) {
			return '';
		}
	
		$href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);
	
		if (!$row['published']) {
			$icon = 'invisible.gif';
		}
		
		return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}

	
	public function toggleVisibility($intId, $blnVisible) {

		// Check permissions to publish
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_simpletipp_questions::published', 'alexf'))
		{
			$this->log('Not enough permissions to publish/unpublish tl_simpletipp_questions ID "'.$intId.'"', 'tl_simpletipp_questions toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		$this->createInitialVersion('tl_simpletipp_questions', $intId);

		// Update the database
		$this->Database->prepare("UPDATE tl_simpletipp_questions SET tstamp=". time() 
				.", published='" . ($blnVisible ? 1 : '') . "' WHERE id = ?")
				->execute($intId);
		
		$this->createNewVersion('tl_simpletipp_questions', $intId);
	}
	
	public function clearImporter($varValue, DataContainer $dc) {
		return '';
	}
	
	public function importAnswers($varValue, DataContainer $dc) {
		if (strlen($varValue) === 0) {
			return '';
		}
		
		$arr = explode("\n", $varValue);
		if (count($arr) <= 2) {
			// values seperated by , or ;
			$arr = explode(",", $varValue);
			if (count($arr) <= 2) {
				$arr = explode(";", $varValue);
			}
		}
		$arr = array_filter(array_map('trim', $arr));

		$result = $this->Database->prepare("UPDATE tl_simpletipp_questions"
				." SET tstamp = ?, answers = ? WHERE id = ?")
				->execute(time(), serialize($arr), $dc->id);
		return '';
	}
	
	public function addQuestions($arrRow) {
		$a = implode(", ", unserialize($arrRow['answers']));
		
		if (strlen($a) > 30) {
			$a = substr($a, 0, 30).'...';
		}
			
		return sprintf('<strong>%s</strong> <span class="points">%s Punkt(e)</span> <em class="answers">%s</em>',
			$arrRow['question'],
			$arrRow['points'],
			$a);
	}
	
}


