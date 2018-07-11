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

namespace Simpletipp;

use Contao\MemberModel;

/**
 * Class SimpletippCallbacks
 *
 * Provide some methods
 * @copyright  Martin Kozianka 2014-2018
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    Controller
 */
class SimpletippCallbacks extends \Backend
{
	public function addCustomRegexp($strRegexp, $varValue, \Widget $objWidget)
    {
		if ($strRegexp == 'SimpletippFactor')
        {
			if (!preg_match('#^[0-9]{1,6},[0-9]{1,6},[0-9]{1,6}$#', $varValue))
            {
				$objWidget->addError('Format must be <strong>NUMBER,NUMBER,NUMBER</strong>.');
			}
			return true;
		}
		return false;
	}


    public function createNewsletterChannel()
    {
    	
    	$allSimpletippEmails = [];
        $result = $this->Database->execute("SELECT * FROM tl_simpletipp");
        while($result->next())
        {
            $simpletipp = $result->row();
            $channelResult = $this->Database->prepare("SELECT * FROM tl_newsletter_channel WHERE simpletipp = ?")
                        ->execute($simpletipp['id']);

            if ($channelResult->numRows == 0)
            {
                $nlc = new \NewsletterChannelModel();
                $nlc->setRow([
                    'simpletipp' => $simpletipp['id'],
                    'title'      => 'SIMPLETIPP '.$simpletipp['title'],
                    'jumpTo'     => 1, // TODO
                    'tstamp'     => time(),
                ]);
                $nlc->save();
                $channel_id = $nlc->id;
            }
            else {
                $channel_id = $channelResult->id;
            }
            $this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE pid = ?")
                ->execute($channel_id);

            $emails     = [];
            $groupID    = $simpletipp['participant_group'];
            $objMembers = \MemberModel::findBy(['tl_member.groups LIKE ?'], '%s:'.strlen($groupID).':"'.$groupID.'"%');
            if ($objMembers !== null)
            {
                foreach($objMembers as $objMember)
                {
                    $emails[] = $objMember->email;
                    $allSimpletippEmails[] = $objMember->email;
                }
            }
            
            $emails = array_unique($emails);
            foreach($emails as $email)
            {
                $recipient = new \NewsletterRecipientsModel();
                $recipient->setRow([
                    'pid'       => $channel_id,
                    'email'     => $email,
                    'tstamp'    => $simpletipp['tstamp'],
                    'addedOn'   => $simpletipp['tstamp'],
                    'confirmed' => $simpletipp['tstamp'],
                    'active'    => '1'
                ]);
                $recipient->save();
            }
        }

        // Create global newsletter channel with the users from all simpletipp configurations
        $simpletipp_global_id = 999999999;
        $globalChannel = $this->Database->prepare("SELECT * FROM tl_newsletter_channel WHERE simpletipp = ?")->execute($simpletipp_global_id);
        if ($globalChannel->numRows == 0) {
			$nlc = new \NewsletterChannelModel();
			$nlc->setRow([
        		'simpletipp' => $simpletipp_global_id,
	        	'title'      => 'SIMPLETIPP ALL',
            	'jumpTo'     => 1, // TODO
            	'tstamp'     => time(),
			]);
        	$nlc->save();
        	$globalChannel_id = $nlc->id;
        }
        else {
        	$globalChannel_id = $globalChannel->id;
        }
		$this->Database->prepare("DELETE FROM tl_newsletter_recipients WHERE pid = ?")->execute($globalChannel_id);

		$uniqueMails = array_unique($allSimpletippEmails);
        foreach($uniqueMails as $email)
        {
            $recipient = new \NewsletterRecipientsModel();
            $recipient->setRow([
                'pid'       => $globalChannel_id,
                'email'     => $email,
                'tstamp'    => time(),
                'addedOn'   => time(),
                'confirmed' => time(),
                'active'    => '1'
            ]);
            $recipient->save();
        }
        
    }

    public function telegramChatLink($strTag)
    {
        $arr = explode('::', $strTag);
        if ($arr[0] == 'telegram_chat')
        {
            $tmpl = 'https://telegram.me/%s?start=%s';
            $name = trim($arr[1]);

            // Generate new secrect key
            $secretKey = $this->generateBotSecret();

            // Save key in tl_member table
            $this->import('FrontendUser', 'User');
            $this->User->simpletipp_bot_secret = $secretKey;
            $this->User->save();

            return sprintf($tmpl, $name, $secretKey);
        }
        // nicht unser Insert-Tag
        return false;
    }

    private function generateBotSecret($length = 52)
    {
        $index          = 0;
        $use_random_int = (function_exists('random_int'));
        $codeAlphabet   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $lengthAlphabet = strlen($codeAlphabet);
        $token          = '';
        for($i = 0;$i < $length;$i++)
        {
            $index  = ($use_random_int) ? random_int(0,$lengthAlphabet) : mt_rand(0,$lengthAlphabet);
            $token .= $codeAlphabet[$index];
        }
        return $token;
    }


    public function randomLine($strTag)
    {
        $arr = explode('::', $strTag);
        if ($arr[0] == 'random_line')
        {
            if (!isset($arr[1]))
            {
                return sprintf('Error! No file defined.');
            }
            if (!file_exists($arr[1]))
            {
                return sprintf('Error! File "%s" does not exist.', $arr[1]);
            }
            $fileArr = file($arr[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            $index = array_rand($fileArr);
            $str   = trim($fileArr[$index]);
            if (count($arr) === 2)
            {
                return $str;
            }

            if (count($arr) === 3)
            {
                return sprintf('Error! No format string defined.');
            }

            $strArr = explode($arr[2], $str);
            $tmpl   = $arr[3];
            if  (substr_count($tmpl, '%s') !== count($strArr))
            {
                return sprintf('Error! Wrong parameter count in %s (%s).', htmlentities($tmpl), count($strArr));
            }

            return vsprintf($tmpl, $strArr);
        }
        // nicht unser Insert-Tag
        return false;
    }

    public function stripImgTag($flag, $tag, $cachedTag, $flags, $blnCache, $tags, $arrCache, $_rit, $_cnt) {
        $arrResult = [];
        preg_match('/src="([^"]*)"/', $cachedTag, $arrResult);
        if(count($arrResult) === 2) {
            return $arrResult[1];
        }
        return $cachedTag;
    }
}

