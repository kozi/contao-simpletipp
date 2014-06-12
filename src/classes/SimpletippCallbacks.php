<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2011-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp;



/**
 * Class SimpletippCallbacks
 *
 * Provide some methods
 * @copyright  Martin Kozianka 2011-2014
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippCallbacks extends \Backend {


	public function addCustomRegexp($strRegexp, $varValue, Widget $objWidget) {
		if ($strRegexp == 'SimpletippFactor') {
			if (!preg_match('#^[0-9]{1,6},[0-9]{1,6},[0-9]{1,6}$#', $varValue)) {
				$objWidget->addError('Format must be <strong>NUMBER,NUMBER,NUMBER</strong>.');
			}
			return true;
		}
		return false;
	}


    public function createNewsletterChannel() {
        $result = $this->Database->execute("SELECT * FROM tl_simpletipp");

        while($result->next()) {
            $simpletipp = $result->row();
            $channelResult = $this->Database->prepare("SELECT * FROM tl_newsletter_channel WHERE simpletipp = ?")
                        ->execute($simpletipp['id']);

            if ($channelResult->numRows == 0) {
                $nlc = new \NewsletterChannelModel();
                $nlc->setRow(array(
                    'simpletipp' => $simpletipp['id'],
                    'title'      => 'SIMPLETIPP '.$simpletipp['title'],
                    'jumpTo'     => 1, // TODO
                    'tstamp'     => time(),
                ));
                $nlc->save();
                $channel_id = $nlc->id;
            }
            else {
                $channel_id = $channelResult->id;
            }
            $this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE pid = ?")
                ->execute($channel_id);

            $emails     = array();
            $objMembers = \Simpletipp::getGroupMember($simpletipp['participant_group']);
            foreach($objMembers as $objMember) {
                $emails[] = $objMember->email;
            }

            $emails = array_unique($emails);
            foreach($emails as $email) {
                $recipient = new \NewsletterRecipientsModel();
                $recipient->setRow(array(
                    'pid'       => $channel_id,
                    'email'     => $email,
                    'tstamp'    => $simpletipp['tstamp'],
                    'addedOn'   => $simpletipp['tstamp'],
                    'confirmed' => $simpletipp['tstamp'],
                    'active'    => '1'
                ));
                $recipient->save();
            }
        }
    }

    public function randomLine($strTag) {
        $arr = explode('::', $strTag);
        if ($arr[0] == 'random_line') {

            if (!isset($arr[1])) {
                return sprintf('Error! No file defined.');
            }
            if (!file_exists($arr[1])) {
                return sprintf('Error! File "%s" does not exist.', $arr[1]);
            }
            $fileArr = file($arr[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $index = array_rand($fileArr);
            $str   = trim($fileArr[$index]);
            if (count($arr) === 2) {
                return $str;
            }

            if (count($arr) === 3) {
                return sprintf('Error! No format string defined.');
            }

            $strArr = explode($arr[2], $str);
            $tmpl   = $arr[3];
            if  (substr_count($tmpl, '%s') !== count($strArr)) {
                return sprintf('Error! Wrong parameter count in %s (%s).', htmlentities($tmpl), count($strArr));
            }

            return vsprintf($tmpl, $strArr);
        }
        // nicht unser Insert-Tag
        return false;
    }

}

