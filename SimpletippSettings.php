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
 * @copyright  Martin Kozianka 2012 
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    simpletipp 
 * @license    LGPL 
 * @filesource
 */


/**
 * Class SimpletippImporter
 *
 * Provide methods to import matches
 * @copyright  Martin Kozianka 2012 
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    Controller
 */
class SimpletippSettings extends Backend {
	private $key;
	private $tmpl;
	
	public function __construct() {
		parent::__construct();
		$this->loadLanguageFile('tl_simpletipp');
		$this->import('String');
		$this->import('Files');

		$this->key = $this->Input->get('key');
		$this->tmpl = new BackendTemplate('be_simpletipp');
		
		if (in_array($this->key, array('import'))) {

			$this->tmpl->key = $this->key;
			$this->tmpl->title = $GLOBALS['TL_LANG']['tl_simpletipp'][$this->key][1];
			$this->tmpl->action = ampersand($this->Environment->request, true);
			$this->tmpl->formId = 'tl_simpletipp_'.$this->key;
			
			$this->tmpl->back = (Object) array(
					'href' => ampersand(str_replace('&key='.$this->key, '', $this->Environment->request)),
					'label' => $GLOBALS['TL_LANG']['MSC']['backBT']
			);

			$this->tmpl->btn_submit = $GLOBALS['TL_LANG']['tl_simpletipp'][$this->key.'_btn'];
			$this->tmpl->btn_cancel = $GLOBALS['TL_LANG']['MSC']['cancelBT'];

			$this->tmpl->messages = $this->getMessages();
			
			// cancel
			if ($this->tmpl->btn_cancel === $this->Input->post('save')) {
				$this->redirect($this->tmpl->back->href);
			}
				
		}
	}

	public function importMatches() {
		if ($this->key != 'import') {
			return '';
		}
		
		if ($this->Input->post('FORM_SUBMIT') == 'tl_simpletipp_import') {
			$this->doImport();
			$this->redirect($this->Environment->request);
		}

		$formFields = new BackendTemplate('be_simpletipp_import');
		
		$formFields->selectAll    = $GLOBALS['TL_LANG']['MSC']['selectAll'];
		$formFields->filesLabel   = 'Wettbewerbe importieren';
		$formFields->filesHelp    = 'Eine oder mehrere Wettbewerbe zum Import auswählen.';

		$formFields->filesOptions = array_merge(
				$this->getFilesOptions('/tl_files/simpletipp-import'),
				$this->getFilesOptions('/system/modules/simpletipp/import')
		);
		

		$formFields->competitionsLabel   = "Wettbewerbe löschen";
		$formFields->competitionsHelp    = "Spiele der selektierten Wettbewerbe werden gelöscht.";
		$formFields->competitionsOptions = $this->getCompetitions();
		
		$this->tmpl->formFields = $formFields->parse();
		return $this->tmpl->parse();
	}

	private function doImport() {
		$files = $this->Input->post('files');
		$competitions = $this->Input->post('competitions');

		$feedback = array();
		
		if (is_array($competitions) && count($competitions) > 0) {
			$this->deleteLeagues($competitions);
			foreach($competitions as $c) {
				$feedback[] = array(sprintf('<strong>%s</strong> deleted.', $c), 'TL_ERROR');
			}
		}

		if (is_array($files) && count($files) > 0) {
			foreach ($files as $file) {
				$feedback[] = $this->importFile($file);
			}
		}

		foreach($feedback as $message) {
			$this->addMessage($message[0], $message[1]);
		}
		
	}

	private function getFilesOptions($d) {
		$files = array();
		$directory = str_replace('/', DIRECTORY_SEPARATOR, TL_ROOT.$d);

		if (file_exists($directory)) {
			$dir = openDir($directory);
			while ($file = readDir($dir)) {
				if ($file != "." && $file != "..") {

					$entry = $this->getEntry($file, $directory);
					if ($entry !== false) {
						$files[] = $entry;
					}
										
				}
			}
			closeDir($dir);
		}
		
		return $files;
	}
	
	
	private function getEntry($fn, $dir) {
		
		$filename = $dir.DIRECTORY_SEPARATOR.$fn;
		
		if (!is_readable($filename)) {
			return false;
		}
		
		$f = fopen($filename, 'r');
		$r = trim(fgets($f));
		
		if (strlen($r) === 0 || substr($r,0,1) !== '#') {
			return false;
		}
		
		$entry = (Object) array(
				'id'       => md5($filename),
				'size'     => filesize($filename),
				'date'     => filemtime($filename),
				'label'    => trim(str_replace('#', '', $r)),
				'filename' => $fn,
				'file'     => $filename);
		
		return $entry;
	}
	
	
	private function importFile($filename) {
		if (!is_readable($filename)) {
			return array(sprintf('File %s is not readable.', $filename), 'TL_ERROR');
		}
		$f = fopen($filename, 'r');
		$r = trim(fgets($f));
		$competition = trim(str_replace('#', '', $r));

		// Check if competition exists
		$result = $this->Database->prepare("SELECT id FROM tl_simpletipp_matches WHERE competition = ?")
			->limit(1)->execute($competition);
		
		if($result->numRows > 0) {
			return array(sprintf('Competition %s already exists.', $competition), 'TL_INFO');
		}
	
		$count = 0;
		while (($r = fgets($f)) !== false) {
			
			if (substr($r,0,1) !== '#') {
				$row = array_map('trim', explode(';', $r));
				if (count($row) === 3 || count($row) === 4) {
					
					$count++;
					$date = $row[0];
					if (strpos($date, '-') !== false){
						$dt   = new DateTime($timeStr);
						$date = new Date($dt->format('U'));
					
					}
					if (count($row) === 4) {
						$result = $this->Database->prepare("INSERT INTO tl_simpletipp_matches"
								." (deadline,title,competition,matchgroup, result) VALUES (?, ?, ?, ?, ?)")
								->execute($date,$row[1],$competition,$row[2],$row[3]);
					}
					else {
						$result = $this->Database->prepare("INSERT INTO tl_simpletipp_matches"
							." (deadline,title,competition,matchgroup) VALUES (?, ?, ?, ?)")
							->execute($date,$row[1],$competition,$row[2]);
					}
				}
			}
		}
		
		return array(sprintf("Imported competition <strong>%s</strong> (%s matches).", $competition, $count.''), 'TL_NEW');
		
	}
	private function getCompetitions() {
		$competitions = array();
		$result = $this->Database->prepare("SELECT DISTINCT competition FROM tl_simpletipp_matches ORDER BY competition DESC")->execute();
		while($result->next()) {
			$competitions[] = $result->competition;
		}
		return $competitions;
	}
	
	private function deleteLeagues($competitionsArray) {
		$competitions = array_map('mysql_real_escape_string', $competitionsArray);
		$result = $this->Database->execute("DELETE FROM tl_simpletipp_matches"
				." WHERE competition in ('".implode("','",$competitions)."')");
		return true;
	}
	
	protected function addMessage($message, $strGroup) {
		if (version_compare(VERSION, '2.11', '>=')) {
			parent::addMessage($message, $strGroup);
		}
		else {
			if (!in_array($strGroup, array('TL_ERROR', 'TL_CONFIRM', 'TL_INFO'))){
				$strGroup = 'TL_INFO';
			}
			if (!is_array($_SESSION[$strGroup])) {
				$_SESSION[$strGroup] = array();
			}
			$_SESSION[$strGroup][] = $message;
		}
		
	}

}
