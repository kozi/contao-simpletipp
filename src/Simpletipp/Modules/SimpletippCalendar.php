<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2018 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2018 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Modules;

use Simpletipp\SimpletippModule;
use Simpletipp\Models\SimpletippTippModel;
use kigkonsult\iCalcreator\vcalendar;

/**
 * Class SimpletippCalendar
 *
 * @copyright  Martin Kozianka 2014-2018
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */

class SimpletippCalendar extends SimpletippModule
{
	private $title         = 'Tippspiel';
	private $matchesPage   = null;

	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$this->Template = new \BackendTemplate('be_wildcard');
			$this->Template->wildcard  = '### SimpletippCalendar ###<br>';
			$this->Template->wildcard .= $GLOBALS['TL_LANG']['FMD']['simpletipp_calendar_info'];
			return $this->Template->parse();
		}
		return parent::generate();
	}

	protected function compile()
	{
		$isDebug              = (\Input::get('debug') == '1');
		$calId                = trim(str_replace(array('.ics', '.ical'), array('', ''), \Input::get('cal')));
		$this->User           = \MemberModel::findBy('simpletipp_calendar', $calId);

		if (strlen($calId) > 0 && $this->User === null && $calId !== 'common')
		{
			echo 'Calendar not found! '.$calId;
			exit();
		}


		$pageObj           = \PageModel::findByPk($this->simpletipp_matches_page);
		$this->matchesPage = ($pageObj !== null) ? $pageObj->row() : null;

		$v = new vcalendar();
		$v->setConfig('unique_id', \Environment::get('base'));
		$v->setProperty('method', 'PUBLISH');
		$v->setProperty("X-WR-CALNAME",  $this->title);
		$v->setProperty("X-WR-CALDESC",  $this->title);
		$v->setProperty("X-WR-TIMEZONE", $GLOBALS['TL_CONFIG']['timeZone']);

		foreach($this->getMatchEvents() as $event)
		{
			$v->setComponent($event);
		}


		/* DEBUG -----------------------------------------------------------*/
		if ($isDebug)
		{
			$xml   = iCal2XML($v);
			$dom   = new \DOMDocument('1.0', 'UTF-8');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$dl = @$dom->loadXML($xml);
			echo '<pre>'.htmlentities($dom->saveXML()).'</pre>';
			exit;
		}
		/*------------------------------------------------------------------*/


		$v->returnCalendar();
		exit;
	}

	private function getMatchEvents()
	{
		if ($this->User === null)
		{
			$matches = $this->Database->prepare("SELECT * FROM tl_simpletipp_match
				WHERE leagueID = ? ORDER BY deadline")->execute($this->simpletipp->leagueID);
		}
		else
		{
			$matches = $this->Database->prepare("SELECT
                 tblm.*,
                 tblt.*,
                 tblt.id AS tipp_id
                 FROM tl_simpletipp_match AS tblm
				 LEFT JOIN tl_simpletipp_tipp AS tblt
				 ON (tblt.match_id = tblm.id  AND tblt.member_id = ?)
				 WHERE tblm.leagueID = ?
				 ORDER BY tblm.deadline")->execute($this->User->id, $this->simpletipp->leagueID);
		}


		$events       = [];
		$tmpMatches   = [];
		$lastDeadline = null;

		while($matches->next())
		{
			$m = (Object) $matches->row();

			if ($m->deadline === $lastDeadline)
			{
				$tmpMatches[] = $m;
			}
			else
			{
				$lastDeadline = $m->deadline;

				// save previous entries
				if (sizeof($tmpMatches) > 0)
				{
					$events[] = $this->getNewEvent($tmpMatches);
				}

				// generate new entries array
				$tmpMatches   = [];
				$tmpMatches[] = $m;

			} // if ($m->deadline === $lastDeadline)

		} // foreach($matches as $m)

		if (sizeof($tmpMatches) > 0)
		{
			$events[] = $this->getNewEvent($tmpMatches);
		}

		return $events;
	}

	private function getNewEvent($matches)
	{
		$ev             = new \vevent();
		$now            = time();
		$url            = '';
		$timestamp      = $matches[0]->deadline;
		$timestamp_ende = $timestamp + $this->simpletipp->matchLength;

		if ($this->matchesPage !== null)
		{
			$url = \Environment::get('base').\Controller::generateFrontendUrl($this->matchesPage,
					"/group/".urlencode($matches[0]->groupName));
		}

		$title              = $matches[0]->groupName.' ('.sizeof($matches).' '.$GLOBALS['TL_LANG']['simpletipp']['matches'].')';
		$description        = $url."\n";
		$pointsSum          = 0;
		$all_matches_tipped = true;

		foreach($matches as $m)
		{
			$info  = ' '.date('H:i', $timestamp);
			$info2 = '';

			// Ist das Ergebnis schon eingetragen und das Spiel angefangen?
			if ($m->result && $now > $m->deadline)
			{
				$info = ' ('.$m->result.') T['.$m->tipp.']';
				// $info = sprintf(' %s (%s)', $m->result, $m->resultFirst);
			}

			if ($this->User !== null)
			{
				// Hat der Benutzer die Spiele schon getippt?
				if ($m->tipp_id === null)
				{
					$all_matches_tipped = false;
				}
				else if ($now < $m->deadline)
				{
					$info2 = ' *OK*';
				}
				else
				{
					$p          = SimpletippTippModel::getPoints($m->result, $m->tipp, $this->pointFactors);
					$pointsSum  = $pointsSum + $p->points;
					$info2      = ' # '.$p->getPointsString();
				}

			} // if ($this->User !== null)

			$description .= $m->title.$info.$info2."\n";

		} // foreach($matches as $m)

		// Only 1 match
		if (sizeof($matches) === 1)
		{
			$m = $matches[0];
			$title = $m->title.' ('.$matches[0]->groupName.')';
		}

		if (!$all_matches_tipped && $now < $matches[0]->deadline)
		{
			$title = "* ".$title;
			// Alarm hinzufügen
			$alarm = new \valarm();
			$alarm->setProperty('action', 'DISPLAY');
			$alarm->setProperty('description', $GLOBALS['TL_LANG']['simpletipp']['alarmText']);
			$alarm->setProperty('trigger', array('hour' => 2));
			$ev->setComponent($alarm);
			$location = strtoupper($GLOBALS['TL_LANG']['simpletipp']['alarmText']);
		}


		else
		{
			$key      = ($pointsSum === 1) ?  'point' : 'points';
			$location = $pointsSum.' '.$GLOBALS['TL_LANG']['simpletipp'][$key][0];
		}

		$ev->setProperty('dtstart', self::getDateArr($timestamp));
		$ev->setProperty('dtend',  self::getDateArr($timestamp_ende));
		$ev->setProperty('LOCATION', $location);
		$ev->setProperty('summary', $title);
		$ev->setProperty('description', $description);
		$ev->setProperty('URL', $url);
		return $ev;
	}

	private static function getDateArr($timestamp)
	{
		return [
			'year'  => date("Y", $timestamp),
			'month' => date("m", $timestamp),
			'day'   => date("d", $timestamp),
			'hour'  => date("H", $timestamp),
			'min'   => date("i", $timestamp),
			'sec'   => date("s", $timestamp)
		];
	}

} // END class SimpletippCalendar
