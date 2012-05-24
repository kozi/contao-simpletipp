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



$GLOBALS['TL_DCA']['tl_simpletipp'] = array(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'switchToEdit'				  => true,
		'enableVersioning'            => true,
		'onload_callback' => array
		(
				array('tl_simpletipp', 'switchPalette')
		),
		'onsubmit_callback' => array
		(
			array('tl_simpletipp', 'saveMatchResultInputs'),
			array('tl_simpletipp', 'updateDeadline')
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 2,
			'fields'                  => array('deadline DESC'),
			'flag'                    => 1,
			'panelLayout'             => 'limit'
		),
		'label' => array
		(
			'fields'                  => array('competition', 'matchgroup', 'deadline', 'teaser'),
			'showColumns'             => true,
			'label_callback'          => array('tl_simpletipp', 'labelCallback')
		),
		'global_operations' => array
		(
			'import' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['import'],
					'href'                => 'key=import',
					'class'               => 'header_simpletipp_import',
					'attributes'          => 'onclick="Backend.getScrollOffset()"'
			),
			
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
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['toggle'],
					'icon'                => 'visible.gif',
					'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
					'button_callback'     => array('tl_simpletipp', 'toggleIcon')
			),
			'results' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['results'],
					'href'                => 'act=edit&type=results',
					'icon'                => 'tablewizard.gif'
			),
			'member' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['participants'],
					'href'                => 'act=edit&type=participants',
					'icon'                => 'mgroup.gif'
			),
			'edit' => array
			(
					'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['edit'],
					'href'                => 'act=edit',
					'icon'                => 'edit.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_simpletipp']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{simpletipp_legend}, competition, matchgroup, teaser;{simpletipp_legend_spiele}, matches;',
		'participants'                => '{simpletipp_legend}, participants;',
		'results'                     => '{simpletipp_legend}, results;',
	),

	// Fields
	'fields' => array
	(
		'competition' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['competition'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options_callback'        => array('tl_simpletipp', 'getCompetitions'),
			'eval'                    => array('mandatory'=>false, 'tl_class' => 'w50',
											'includeBlankOption' => true,
											'submitOnChange' => true)
		),
		'matchgroup' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['matchgroup'],
			'exclude'                 => true,
			'flag'                    => 1,
			'inputType'               => 'select',
			'options_callback'        => array('tl_simpletipp', 'getMatchgroups'),
			'eval'                    => array('mandatory'=>false, 'tl_class' => 'w50',
											'includeBlankOption' => true,
											'submitOnChange' => true)
		),
		'deadline' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['deadline'],
				'exclude'                 => true,
				'inputType'               => 'text',
				'flag'					  => 6,
				'eval'					  => array('rgxp' => 'datim')
		),
		'teaser' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['teaser'],
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'		=> array('tl_class' => 'long clr' ,'style' => ' height:28px;', 'mandatory'=>false)
		),
		'matches' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['matches'],
				'exclude'                 => true,
				'inputType'               => 'checkbox',
				'options_callback'        => array('tl_simpletipp', 'getMatches'),
				'eval'					  => array('mandatory'=>false, 'tl_class' => 'clr',
						'multiple' => true)
				),
		
		'results' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['results'],
				'exclude'                 => true,
				'inputType'               => 'text',
				'sql'                     => 'blob NULL',
				'input_field_callback'    => array('tl_simpletipp', 'getMatchResultInputs')
		),

		'participants' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['participants'],
				'exclude'                 => true,
				'inputType'               => 'checkbox',
				'options_callback'        => array('tl_simpletipp', 'getParticipants'),
				'eval'					  => array('mandatory'=>false, 'tl_class' => 'clr', 'multiple' => true)
		),
		
		'published' => array
		(
				'label'                   => &$GLOBALS['TL_LANG']['tl_simpletipp']['published'],
				'exclude'                 => true,
				'inputType'               => 'checkbox',
				'eval'                    => array('doNotCopy'=>true)
		)
		
		
	)
);


/**
 * Class tl_simpletipp
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <http://kozianka-online.de/>
 * @package    simpletipp
 */

class tl_simpletipp extends Backend {

	private $points;
	
	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
		
		$this->points = new stdClass;
		$this->points->perfect  = 3;
		$this->points->distance = 2;
		$this->points->tendency = 1;
		
	}

	public function getMatches(DataContainer $dc) {
		$matches = array();
		
		$competition = $dc->activeRecord->competition;
		$matchgroup = $dc->activeRecord->matchgroup;
		$result = null;		
		
		if (!$competition) {
			// Kein(e) Liga/Wettbewerb gewählt
			return $matches;
		}
		else if ($matchgroup){
			$result = $this->Database->prepare("SELECT * FROM tl_simpletipp_matches WHERE matchgroup = ? AND competition = ? ORDER BY deadline ASC")
						->execute($matchgroup, $competition);
		}
		else {
			$result = $this->Database->prepare("SELECT * FROM tl_simpletipp_matches WHERE competition = ? ORDER BY deadline ASC")
						->execute($competition);
		}
		
		while ($result->next()) {
			$dl = date($GLOBALS['TL_CONFIG']['datimFormat'],$result->deadline);
			$matches[$result->id] = '<span  class="dline">'.$dl.'</span> '
									.'<span class="title">'.$result->title.'</span>';
		}
		return $matches;
	}
	
	
	public function getParticipants(DataContainer $dc) {
		$member = array();

		$result = $this->Database->execute("SELECT * FROM tl_member ORDER BY lastname");
		while ($result->next()) {
			$member[$result->id] = $result->firstname.' '.$result->lastname.' ['
					.$result->username.', '
					.$result->email.']';
			
		}

		return $member;
	}	
	
	public function getMatchgroups(DataContainer $dc) {
		
		$competition = $dc->activeRecord->competition;

		$result = $this->Database->prepare("SELECT DISTINCT matchgroup FROM tl_simpletipp_matches WHERE competition = ? ORDER BY matchgroup")
			->execute($competition);
		
		$matchgroups = array();
		while ($result->next()) {
			$matchgroups[$result->matchgroup] = $result->matchgroup;
		}
		
		return $matchgroups;
	}
	
	public function getCompetitions() {
		$competitions = array();

		$result = $this->Database->prepare("SELECT DISTINCT competition FROM tl_simpletipp_matches")->execute();
		
		while ($result->next()) {
			$competitions[$result->competition] = $result->competition;
		}
		return $competitions;
	}

	public function labelCallback($row, $label, DataContainer $dc, $args = null) {
		$teaser = strip_tags($row['teaser']);
		if (strlen($teaser) > 40) {
			$teaser = substr($teaser, 0, 40);
		}
		
		$matches       = unserialize($row['matches']);
		$participants  = unserialize($row['participants']);
		
		$lbl = "";
		
		if (is_array($matches)) {
			$sql = 'SELECT id FROM tl_simpletipp_matches WHERE id in ('.implode(',', $matches).')';
			$result = $this->Database->execute($sql);

			$lbl .= '<span class="matches">'.$result->numRows
				.' '.$GLOBALS['TL_LANG']['tl_simpletipp']['matches'][0].'</span> ';
		}
		
		if (is_array($matches) && is_array($participants)) {
			$lbl .= ' / ';
		}
		
		if (is_array($participants)) {
		$lbl .= '<span class="participants">'.count($participants)
			.' '.$GLOBALS['TL_LANG']['tl_simpletipp']['participants'][0].'</span> ';
		}
		
		if ($args === null) {
			$dl = date($GLOBALS['TL_CONFIG']['datimFormat'], $row['deadline']);
			return '<span class="tablecell competition">'.$row['competition'].'</span>'
				.'<span class="tablecell matchgroup">'.$row['matchgroup'].'</span>'
				.'<span class="tablecell deadline">'.$dl.'</span>'
				.'<span class="tablecell label">'.$lbl.'</span>';
		}
		
		
		$args[3]  = $lbl;
		
		return $args;
	}
	
	public function updateDeadline(DataContainer $dc) {
		$match_id = $dc->activeRecord->id;
		$matches  = $dc->activeRecord->matches;

		if (is_array($matches) && count($matches) > 0) {

			$result = $this->Database->prepare("SELECT deadline FROM tl_simpletipp_matches"
				." WHERE id in (".implode(',', $matches).")"
				." ORDER BY deadline ASC")->limit(1)->execute();

			while ($result->numRows === 1) {
				$sql = "UPDATE tl_simpletipp SET deadline = ? WHERE id = ?";
				$result = $this->Database->prepare($sql)
							->execute($result->deadline, $match_id);
			}
		}
	}
	
	public function getMatchResultInputs(DataContainer $dc) {
		$matches = unserialize($dc->activeRecord->matches);
		$legend = "Ergebnisse";
		if (!$matches) {
			$content = $GLOBALS['TL_LANG']['MSC']['noResult'];
		}
		else {
			$sql = "SELECT * FROM tl_simpletipp_matches WHERE id in (".implode(',', $matches).")"
						." ORDER BY deadline ASC";

			$result = $this->Database->execute($sql);
		
			$content = '<div class="long"><table class="simpletipp_results"><tbody>';
			$i=0;
			$h = 'onmouseout="Theme.hoverRow(this,0)" onmouseover="Theme.hoverRow(this,1)"';
			while ($result->next()) {
				$css_class = ($i++ % 2 === 0 ) ? 'odd':'even'; 
				$dl = date($GLOBALS['TL_CONFIG']['datimFormat'],$result->deadline);
				$content .= "\n	<tr ".$h." class=\"".$css_class."\"><td>".$dl.'</td>'
				.'<td>'.$result->title.'</td>'
				.'<td><input type="hidden" value="'.$result->id.'" name="simpletipp_match_ids[]" />'
				.'<input style="width:55px;" class="tl_text" type="text" value="'.$result->result.'" name="simpletipp_match_results[]" /></td></tr>';
			}
			$content .= "</tbody></table></div>\n";

		} // else END
		
		return sprintf('<div class="clr"><fieldset id="ctrl_results" class="tl_checkbox_container"><legend>%s</legend>%s</fieldset></div>', $legend, $content);
	}
	
	public function saveMatchResultInputs(DataContainer $dc) {
		
		$ids = $this->Input->post('simpletipp_match_ids');
		$res = $this->Input->post('simpletipp_match_results');

		if (!is_array($ids) || !is_array($res) || count($ids) === 0 || count($ids) !== count($res)) {
			return false;
		}
		
		$results = array();
		for ($i=0;$i < count($ids);$i++) {
			$id        = intval($ids[$i]);
			$ergebnis  = $res[$i];
			if (preg_match('/^(\d{1,4}):(\d{1,4})$/', $ergebnis)) {
				$results[$id] = $ergebnis;
			}
		} 

		// In der Datenbank speichern
		$updatedIds = array();
		$sql = "UPDATE tl_simpletipp_matches SET result = ? WHERE id = ?";
		foreach($results as $id=>$result) {
			$db_result = $this->Database->prepare($sql)->execute($result, $id);
			$updatedIds[] = $id;
			/*if ($db_result->__get('affectedRows') !== 0) {
				// Nur die ids der geänderten Ergebnisse speichern
				$updatedIds[] = $id;
			}*/
		}
		
		// Punkte aktualisieren
		$this->updateTipps($updatedIds);
	}
	
	
	private function updateTipps($ids) {
		if (count($ids) === 0) {
			//  Nothing to do
			return true;
		}
		
		

		$result = $this->Database->execute(
				"SELECT id, result FROM tl_simpletipp_matches"
				." WHERE id in (".implode(',', $ids).")");
		while($result->next()) {
			$match_results[$result->id] = $result->result;
		}

		$result = $this->Database->execute(
				"SELECT id, match_id, tipp FROM tl_simpletipp_tipps"
				." WHERE match_id in (".implode(',', $ids).")");
		while($result->next()) {
			$points = $this->getPoints($match_results[$result->match_id], $result->tipp);
			$update = $this->Database->prepare(
					"UPDATE tl_simpletipp_tipps"
					." SET perfect = ?, difference = ?, tendency = ?, wrong = ? WHERE id = ?")
					->execute($points->perfect, $points->difference, 
							$points->tendency, $points->wrong, $result->id);
		}
	}
	
	
	public function switchPalette(DataContainer $dc) {
		// Listing mode, return
		if (!$dc->id) {
			return;
		}

		$type = $this->Input->get('type');
		if ($type === 'participants' || $type === 'results') {
			$GLOBALS['TL_DCA']['tl_simpletipp']['palettes']['default'] =
				$GLOBALS['TL_DCA']['tl_simpletipp']['palettes'][$type];
		} 
		
	}
	
	private function getPoints($result, $tipp) {
		$points = new stdClass;
		$points->perfect    = 0;
		$points->difference = 0;
		$points->tendency   = 0;
		$points->wrong      = 0;
		
		if (strlen($result) === 0 || strlen($tipp) === 0) {
			return $points;
		}
		$tmp = explode(":", $result);
		$rh = intval($tmp[0], 10); $ra = intval($tmp[1], 10);
	
		$tmp = explode(":", $tipp);
		$th = intval($tmp[0], 10); $ta = intval($tmp[1], 10);
	
		if ($rh === $th && $ra === $ta) {
			$points->perfect = 1;
			return $points;
		}
	
		if (($rh-$ra) === ($th-$ta)) {
			$points->difference = 1;
			return $points;
		}
	
		if (($rh < $ra && $th < $ta) || ($rh > $ra && $th > $ta)) {
			$points->tendency = 1;
			return $points;
		}
		
		$points->wrong = 1;
		return $points;
	}
	

	public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {
		
		if (strlen($this->Input->get('tid'))) {
			$this->toggleVisibility($this->Input->get('tid'), ($this->Input->get('state') == 1));
			$this->redirect($this->getReferer());
		}
	
		// Check permissions AFTER checking the tid, so hacking attempts are logged
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_simpletipp::published', 'alexf')) {
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
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_simpletipp::published', 'alexf'))
		{
			$this->log('Not enough permissions to publish/unpublish simpletipp ID "'.$intId.'"', 'tl_simpletipp toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}

		$this->createInitialVersion('tl_simpletipp', $intId);

		// Update the database
		$this->Database->prepare("UPDATE tl_simpletipp SET tstamp=". time() 
				.", published='" . ($blnVisible ? 1 : '') . "' WHERE id = ?")
				->execute($intId);
		
		$this->createNewVersion('tl_simpletipp', $intId);
	}
	
}


