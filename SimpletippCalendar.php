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
 * Class SimpletippCalendar
 *
 * @copyright  Martin Kozianka 2011-2012
 * @author     Martin Kozianka <martin@kozianka-online.de>
 * @package    Controller
 */
require_once (TL_ROOT.'/system/modules/simpletipp/iCalcreator/iCalcreator.class.php');

class SimpletippCalendar extends Simpletipp {
	private $userId = null;

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template = new BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippCalendar ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}
		
		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}
	
	protected function compile() {
		$this->initSimpletipp();

		$username = $this->Input->get('user');
		if ($username) {
			$result = $this->Database->prepare('SELECT id FROM tl_member WHERE username = ?')
				->execute($username);
			if ($result->numRows == 1) {
				$this->userId = $result->id;
			}
		}

		$v = new vcalendar();
		$v->setConfig('unique_id', $this->Environment->base);
		
		$v->setProperty('method', 'PUBLISH');
		$v->setProperty("X-WR-CALNAME", $this->title);
		$v->setProperty("X-WR-CALDESC", $this->title);
		$v->setProperty("X-WR-TIMEZONE", "Europe/Berlin");

		
		foreach($this->getMatchEvents() as $event) {
			$v->setComponent($event);
		}

		// $v->returnCalendar();
		
		
		$xml   = iCal2XML($v);
		$dom   = new DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dl = @$dom->loadXML($xml);
		echo '<pre>'.htmlentities($dom->saveXML()).'</pre>';
		
		exit;
	}

	
	private function getMatchEvents() {
		if ($this->userId === null) {
			$matches = $this->Database->execute(
				'SELECT * FROM tl_simpletipp_matches ORDER BY deadline');
		}
		
		
		else {
			$matches = $this->Database->prepare('SELECT * FROM tl_simpletipp_matches AS tblm'
			.' LEFT JOIN tl_simpletipp_tipps AS tblt'
			.' ON (tblt.match_id = tblm.id  AND tblt.member_id = ?)'
			.' ORDER BY tblm.deadline')->execute($this->userId);
		}

		$events       = array();
		$tmpmatches   = array();
		$lastDeadline = null;
		
		while($matches->next()) {
			
			$m = (Object) $matches->row();
			
			if ($m->deadline === $lastDeadline) {
				$tmpmatches[] = $m;
			}
			else {
				$lastDeadline = $m->deadline;
	
				// save previous entries
				if (sizeof($tmpmatches) > 0) {
					$events[] = $this->getNewEvent($tmpmatches);
				}
	
				// generate new entries array
				$tmpmatches = array();
				$tmpmatches[] = $m;
	
			} // if ($m->deadline === $lastDeadline)
	
		} // foreach($matches as $m)
	
		if (sizeof($tmpmatches) > 0) {
			$events[] = $this->getNewEvent($tmpmatches);
		}
	
		return $events;
	}

	private function getNewEvent($matches) {
		$ev = new vevent();
	
		$timestamp      = $matches[0]->deadline;
		$timestamp_ende = $timestamp + (105*60); // add 105 (2x45 + 15) minutes

		// TODO config parameter!
		$url         = $this->Environment->base.$this->frontendUrlById(
							$this->simpletipp_matches_page,
							"/group/".urlencode($matches[0]->matchgroup));

		$title       = $matches[0]->matchgroup.' ('.sizeof($matches).' Spiele'; // TODO translation
		$info        = " ".date("H:i", $timestamp);
		$description = $url."\n";
		$pointsSum   = 0;
		$all_matches_tipped = true;
		
		foreach($matches as $m) {

			$info  = " ".date("H:i", $timestamp);
			$info2 = "";
			
			// Ist das Ergebnis schon eingetragen und das Spiel angefangen?
			if ($m->result && $this->now > $m->deadline) {
				$info = " (".$m->result.")";
			}
	
			if ($user_id !== null) {
				// Hat der Benutzer die Spiele schon getippt?
				if ($m->tipp_id === null) {
					$all_matches_tipped = false;
				}
				else if ($this->now < $m->deadline) {
					$info2 = " *OK*"; // TODO translation
				}
				else {
					$p          = self::getPointsString($m, $this->pointFactors);
					$pointsSum  = $pointsSum + $p->summe;
					$info2      = " ".$p->str;
				}
	
			} // if ($user_id !== null)
				
			$description .= $m->title.$info.$info2."\n";
			
		} // foreach($matches as $m)
	
		// Only 1 match
		if (sizeof($matches) === 1) {
			$m = $matches[0];
			$title = $m->title.' ('.$matches[0]->matchgroup.')';
		}

		if (!$all_matches_tipped && $this->now < $matches[0]->deadline) {
			$title = "* ".$title;
			// Alarm hinzufügen
			$alarm = new valarm();
			$alarm->setProperty('action', 'DISPLAY');
			$alarm->setProperty('description', 'Tippen!'); // TODO translation
			$alarm->setProperty('trigger', array('hour' => 2));
			$ev->setComponent($alarm);
			$location = "TIPPEN!"; // TODO translation
		}
		else {
			$location = $pointsSum." Punkt(e)"; // TODO translation
		}

		$ev->setProperty('dtstart', $this->getDateArr($timestamp));
		$ev->setProperty('dtend', $this->getDateArr($timestamp_ende));
		$ev->setProperty('LOCATION', $location);
		$ev->setProperty('summary', $title);
		$ev->setProperty('description', $description);
		$ev->setProperty('URL', $url);
		return $ev;
	}

	private function getDateArr($timestamp) {
		return array(
			'year'  => date("Y", $timestamp),
			'month' => date("m", $timestamp),
			'day'   => date("d", $timestamp),
			'hour'  => date("H", $timestamp),
			'min'   => date("i", $timestamp),
			'sec'   => date("s", $timestamp)
		);
	}

} // END class SimpletippCalendar



/*

header('content-type: text/html; charset=utf-8');

$sql = "SELECT *, CONCAT(team_h,' - ',team_a) as title FROM `tipp1213__matches` ORDER BY deadline";
$result = $this->Database->execute($sql);
echo "<pre>\n";
echo "# 1. Bundesliga 2012/2013\n";
echo "# Spiele der 1. Bundesliga 2012/2013\n";
while($result->next()) {
	$r = (Object) $result->row();
	$day = (intval($r->matchgroup)<10) ? '0'.$r->matchgroup: $r->matchgroup;
	
	echo implode(' ; ',array(
			$r->deadline,
			$r->title,
			$day.'. Spieltag',
			short_title($r->title),
	))."\n";
	
}
echo "\n\n\n</pre>\n";

function short_title($title) {
	$n[] = '1899 Hoffenheim';		$s[] = 'Hoffenheim';
	$n[] = 'Fortuna Düsseldorf';	$s[] = 'Düsseldorf';	
	$n[] = 'Bayer Leverkusen';		$s[] = 'Leverkusen';
	$n[] = 'Eintracht Frankfurt';	$s[] = 'Frankfurt';
	$n[] = '1. FC Nürnberg';		$s[] = 'Nürnberg';
	$n[] = 'Borussia Dortmund';		$s[] = 'Dortmund';
	$n[] = 'FC Schalke 04';			$s[] = 'Schalke';
	$n[] = 'Werder Bremen';			$s[] = 'Bremen';
	$n[] = 'SpVgg Greuther Fürth';	$s[] = 'Fürth';
	$n[] = 'Bayern München';		$s[] = 'Bayern';
	$n[] = 'Hamburger SV';			$s[] = 'Hamburg';
	$n[] = 'FC Augsburg';			$s[] = 'Augsburg';
	$n[] = 'VfB Stuttgart';			$s[] = 'Stuttgart';
	$n[] = 'VfL Wolfsburg';			$s[] = 'Wolfsburg';
	$n[] = 'Bor. Mönchengladbach';	$s[] = 'Gladbach';
	$n[] = 'Hannover 96';			$s[] = 'Hannover';
	$n[] = '1. FSV Mainz 05';		$s[] = 'Mainz';
	$n[] = 'SC Freiburg';			$s[] = 'Freiburg';
	return str_replace($n, $s, $title);;
}


*/