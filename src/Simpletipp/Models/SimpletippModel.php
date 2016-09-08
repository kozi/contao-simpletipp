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


class SimpletippModel extends \Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_simpletipp';

    public function getPointFactors()
    {
        $factor = explode(',', $this->factor);
        $pointFactors = new \stdClass;
        $pointFactors->perfect    = intval($factor[0]);
        $pointFactors->difference = intval($factor[1]);
        $pointFactors->tendency   = intval($factor[2]);

        return $pointFactors;
    }

    public static function getLeagueGroups($leagueID)
    {
        $groups = [];
        $result = \Database::getInstance()->prepare("SELECT DISTINCT groupID, groupName
          FROM tl_simpletipp_match WHERE leagueID = ? ORDER BY groupID")->execute($leagueID);

        while($result->next())
        {
            $short = intval($result->groupName);
            if ($short == 0)
            {
                $mg    = explode(". ", $result->groupName);
                $short = $mg[0];
            }

            $groups[$result->groupID] = (Object) [
                'title' => $result->groupName,
                'short' => $short
            ];
        }
        return $groups;
    }
    

}
