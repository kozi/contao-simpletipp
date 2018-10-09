<?php

namespace Simpletipp\Models;

use Contao\FilesModel;
use Contao\Model;

class SimpletippTeamModel extends Model
{
    public $wins = 0;
    public $draws = 0;
    public $losses = 0;
    public $goalsPlus = 0;
    public $goalsMinus = 0;

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_simpletipp_team';

    public function addGoals($plus, $minus)
    {
        $this->goalsPlus += $plus;
        $this->goalsMinus += $minus;
    }

    public function getPoints()
    {
        return (($this->wins * 3) + $this->draws);
    }

    public function goalDiff()
    {
        return ($this->goalsPlus - $this->goalsMinus);
    }

    public function __toString()
    {
        $diff = ($this->goalDiff() > 0) ? '+' . $this->goalDiff() : $this->goalDiff();
        $attribs = array($this->getPoints(), $this->wins, $this->draws, $this->losses, $diff);
        return $this->name . ' [' . $this->short . ', ' . $this->alias . '] (' . implode(', ', $attribs) . ')';
    }

    public function logoPath()
    {
        $logoPath = null;
        $objFile = FilesModel::findByUuid($this->logo);
        if ($objFile !== null) {
            $logoPath = $objFile->path;
        }
        return $logoPath;
    }

}
