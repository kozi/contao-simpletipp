<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
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
 * @copyright	Copyright Martin Kozianka 2011-2012
 * @author		Martin Kozianka
 * @package		simpletipp
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
		parent::compile();
		
		$this->groups = array_map('intval', unserialize($this->simpletipp_groups));

		if (in_array(0, $this->groups)){
			// 0 means all groups
			$sql_where = "";
		}
		else {
			$sql_where = " AND pid in (".implode(',', $this->groups).")";
		}

		$result = $this->Database->execute("SELECT * FROM tl_simpletipp_questions"
				.$sql_where." ORDER BY pid ASC, sorting ASC");

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


