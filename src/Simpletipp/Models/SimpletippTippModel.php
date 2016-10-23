<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2016 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014-2016 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

namespace Simpletipp\Models;

use \Simpletipp\Models\SimpletippPoints;

class SimpletippTippModel extends \Model
{
    const TIPP_DIVIDER = ':';    
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_simpletipp_tipp';

    public static function getPoints($result, $tipp, $simpletippFactor = null)
    {
        $perfect    = 0;
        $difference = 0;
        $tendency   = 0;

        if (strlen($result) === 0 || strlen($tipp) === 0)
        {
            return new SimpletippPoints($simpletippFactor, 0, 0, 0);
        }
        $tmp = explode(self::TIPP_DIVIDER, $result);
        $rh  = intval($tmp[0], 10); $ra = intval($tmp[1], 10);

        $tmp = explode(self::TIPP_DIVIDER, $tipp);
        $th  = intval($tmp[0], 10); $ta = intval($tmp[1], 10);

        if ($rh === $th && $ra === $ta)
        {
            $perfect = 1;
        }
        elseif (($rh-$ra) === ($th-$ta))
        {
            $difference = 1;
        }
        elseif (($rh < $ra && $th < $ta) || ($rh > $ra && $th > $ta))
        {
            $tendency = 1;
        }

        return new SimpletippPoints($simpletippFactor, $perfect, $difference, $tendency);
    }

    public static function cleanupTipp($tipp)
    {
        $t = preg_replace ('/[^0-9]/',' ', $tipp);
        $t = preg_replace ('/\s+/',self::TIPP_DIVIDER, $t);

        if (strlen($t) < 3)
        {
            return '';
        }

        $tmp = explode(self::TIPP_DIVIDER, $t);

        if(strlen($tmp[0]) < 1 && strlen($tmp[1]) < 1)
        {
            return '';
        }

        $h = intval($tmp[0], 10);
        $a = intval($tmp[1], 10);
        return $h.self::TIPP_DIVIDER.$a;
    }

    public static function addTipp($memberId, $matchId, $tipp) {
        $arrWhere  = ['member_id = ?', 'match_id = ?'];
        $arrValues = [$memberId, $matchId];
        $tippModel = self::findOneBy($arrWhere, $arrValues);
        if ($tippModel === null) {
            $tippModel = new SimpletippTippModel();
            $tippModel->member_id = $memberId;
            $tippModel->match_id  = $matchId; 
        }
        $tippModel->tstamp = time();
        $tippModel->tipp   = $tipp;
        $tippModel->save();
        return $tippModel;        
    }

}
