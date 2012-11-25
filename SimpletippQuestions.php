<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012 <http://kozianka-online.de/>
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */


/**
 * Class SimpletippQuestions
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */
 
class SimpletippQuestions extends Simpletipp {
	private $matchId;
	protected $strTemplate = 'simpletipp_questions_default';

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippQuestions ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}
		
		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}
	
	protected function compile() {

		$result = $this->Database->prepare("SELECT * FROM tl_simpletipp_questions"
				." WHERE pid = ? ORDER BY sorting ASC")->execute($this->simpletipp_group);

		$questions = array();
		while($result->next()) {
			$q = new stdClass;
			$q->id = "question_".$result->id;
			$q->question = $result->question;
			$q->points   = $result->points;
			$q->answers  = unserialize($result->answers);
			
			$questions[] = $q;
		}
		
		$this->Template->questions = $questions;
		
	}

} // END class SimpletippQuestions


