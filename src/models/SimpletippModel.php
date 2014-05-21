<?php

class SimpletippModel extends \Model {

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_simpletipp';

    public function getPointFactors() {
        $factor = explode(',', $this->factor);
        $pointFactors = new stdClass;
        $pointFactors->perfect    = intval($factor[0]);
        $pointFactors->difference = intval($factor[1]);
        $pointFactors->tendency   = intval($factor[2]);

        return $pointFactors;
    }

}
