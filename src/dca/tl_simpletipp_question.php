<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


$GLOBALS['TL_DCA']['tl_simpletipp_question'] = array(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_simpletipp',
		'enableVersioning'            => true,
	    'sql' => array(
            'keys' => array('id' => 'primary')
        )
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
			'child_record_callback'   => array('tl_simpletipp_question', 'addQuestions'),
			'headerFields'            => array('title', 'teaser', 'tstamp')
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
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['toggle'],
					'icon'                => 'visible.gif',
					'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
					'button_callback'     => array('tl_simpletipp_question', 'toggleIcon')
			),
			'edit' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['edit'],
					'href'                => 'act=edit',
					'icon'                => 'edit.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['delete'],
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
        'id' => array
        (
            'label'                   => array('ID'),
            'search'                  => false,
            'sql'                     => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid' => array
        (
            'label'                   => array('PID'),
            'search'                  => false,
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tstamp' => array
        (
            'label'                   => array('TSTAMP'),
            'search'                  => false,
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
        ),
        'sorting' => array
        (
            'label'                   => array('SORTING'),
            'search'                  => false,
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
        ),
		'question' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['question'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'tl_class' => 'long'),
            'sql'                     => "text NULL",
		),
		'points' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['points'],
				'exclude'                 => true,
				'inputType'               => 'text',
				'default'				  => 1,
				'eval'					  => array('rgxp' => 'number','mandatory'=>true, 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'",
		),
		'published' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['published'],
				'exclude'                 => true,
				'inputType'               => 'checkbox',
				'eval'                    => array('doNotCopy'=>true, 'tl_class' => 'w50'),
                'sql'                     => "char(1) NOT NULL default ''",
		),
		'importer' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['importer'],
				'inputType'               => 'textarea',
				'exclude'                 => true,
				'eval'                    => array('doNotShow'=>true, 'decodeEntities' => true),
				'load_callback'           => array(
						array('tl_simpletipp_question', 'clearImporter')
				),
				'save_callback'           => array(
						array('tl_simpletipp_question', 'importAnswers')
				),
                'sql'                     => "char(1) NOT NULL default ''",
				
		),
		'answers' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp_question']['answers'],
			'exclude'                 => true,
			'inputType'               => 'listWizard',
			'eval'					  => array('tl_class' => 'long clr' , 'mandatory' => false),
            'sql'                     => "blob NULL",
		)
		
		
	)
);


/**
 * Class tl_simpletipp_question
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2013
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 */

class tl_simpletipp_question extends Backend {

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
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_simpletipp_question::published', 'alexf')) {
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
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_simpletipp_question::published', 'alexf'))
		{
			$this->log('Not enough permissions to publish/unpublish tl_simpletipp_question ID "'.$intId.'"', 'tl_simpletipp_question toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

        $objVersions = new Versions('tl_simpletipp_question', $intId);
        $objVersions->initialize();

		// Update the database
		$this->Database->prepare("UPDATE tl_simpletipp_question SET tstamp=". time()
				.", published='" . ($blnVisible ? 1 : '') . "' WHERE id = ?")
				->execute($intId);

        $objVersions->create();
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

        $objVersions = new Versions('tl_simpletipp_question', $dc->id);
        $objVersions->initialize();

        $this->Database
            ->prepare("UPDATE tl_simpletipp_question SET tstamp = ?, answers = ? WHERE id = ?")
            ->execute(time(), serialize($arr), $dc->id);

        $objVersions->create();

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


