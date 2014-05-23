<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2012-2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    simpletipp
 * @license    LGPL
 * @filesource
 */

class SimpletippPoints {
    public $points     = 0;
    public $perfect    = 0;
    public $difference = 0;
    public $tendency   = 0;
    public $wrong      = 0;


    public function __construct($pointFactors, $perfect, $difference, $tendency) {
        $this->perfect    = $perfect;
        $this->difference = $difference;
        $this->tendency   = $tendency;
        $this->wrong      = 0;
        if ($tendency == 0 && $difference == 0 && $perfect == 0) {
            $this->wrong = 1;
        }

        if ($pointFactors !== null) {
            $this->points = ($this->perfect    * $pointFactors->perfect)
                      + ($this->difference * $pointFactors->difference)
                      + ($this->tendency   * $pointFactors->tendency);
        }
    }

    public function getPointsString() {
        $key = ($this->points === 1) ?  'point' : 'points';
        return $this->points.' '.$GLOBALS['TL_LANG']['simpletipp'][$key];
    }

    public function getPointsClass() {

        if ($this->perfect == 1) {
            return "perfect";
        }

        if ($this->difference == 1) {
            return "difference";
        }

        if ($this->tendency == 1) {
            return "tendency";
        }

        return "wrong";
    }

}