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

namespace Simpletipp\Models;

class SimpletippMatchModel extends \Model {

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_simpletipp_match';

    public static function findByShortNames($shortNames) {
        $shorts = explode('-', $shortNames);
        if (sizeof($shorts) !== 2) {
            return null;
        }
        array_map('ucfirst', $shorts);
        $title_short = $shorts[0].' - '.$shorts[1];
        $title_short = str_replace(array('ue', 'ae', 'oe'), array('ü','ä','ö'), $title_short);
        return self::findOneBy('title_short', $title_short);
   }
}
