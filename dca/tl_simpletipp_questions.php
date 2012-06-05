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
		'enableVersioning'            => false,
		'onload_callback'			  => array(),
		'onsubmit_callback'			  => array()
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
		'default'					=> '{simpletipp_questions_legend}, question, points, answers;',
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
		'answers' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_questions']['answers'],
			'exclude'                 => true,
			'inputType'               => 'listWizard',
			'eval'					  => array('tl_class' => 'long clr' , 'mandatory'=>true)
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

	public function saveQuestionInputs(DataContainer $dc) {
		
		$pid = $this->Input->post('simpletipp_questions_pid');
		
		$ids       = $this->Input->post('id');
		$delete    = $this->Input->post('delete');
		$points    = $this->Input->post('points');
		$questions = $this->Input->post('question');
		$answers   = $this->Input->post('answers');

		if (!$pid) {
			return false;
		}
		
		$i = 0;
		
		for ($i=0;$i < count($ids);$i++) {
			$id = $ids[$i];
			$q  = $questions[$i];
			$p  = $points[$i];
			$a  = $answers[$i];
			
			// convert answers to array and serialize
			$a = array_map('trim', explode("\n", $a));
			if (is_array($delete) && in_array($id, $delete)) {
				// delete entry
				$result = $this->Database->prepare("DELETE FROM tl_simpletipp_questions"
						." WHERE id = ?")->execute($id);				
			}
			else if ($id == '-1') {
				// new entry
				if (strlen($q) && count($a) > 0) {
					$result = $this->Database->prepare("INSERT INTO tl_simpletipp_questions"
						." (pid,tstamp,question,answers, points) VALUES (?, ?, ?, ?, ?)")
						->execute($pid,time(),$q, serialize($a), $p);
				}
			}
			else {
				// update entry
				$result = $this->Database->prepare("UPDATE tl_simpletipp_questions"
						." SET tstamp=?, question=?, answers=?, points=? WHERE id=?")
						->execute(time(), $q, serialize($a), $p, $id);
			}
		} 
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
	
	public function addQuestions($arrRow) {
		$a = unserialize($arrRow['answers']);
		$a = substr(implode(", ", $a), 0, 60);
		return sprintf('<strong>%s</strong> (%s Punkte) <em class="answers">%s</em>',
			$arrRow['question'],
			$arrRow['points'],
			$a);
	}
	
}


