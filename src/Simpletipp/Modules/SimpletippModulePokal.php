<?php

namespace Simpletipp\Modules;

use Simpletipp\SimpletippModule;

/**
 * Class SimpletippModulePokal
 *
 * @copyright  Martin Kozianka 2014-2019
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */
class SimpletippModulePokal extends SimpletippModule
{
    protected $strTemplate = 'simpletipp_pokal_default';
    private $groups = [];

    public function generate()
    {
        if (TL_MODE == 'BE') {
            $this->Template = new \BackendTemplate('be_wildcard');
            $this->Template->wildcard = '### SimpletippPokal ###';
            $this->Template->wildcard .= '<br/>' . $this->headline;
            return $this->Template->parse();
        }

        $this->import('\Simpletipp\SimpletippPokal', 'SimpletippPokal');
        $this->strTemplate = $this->simpletipp_template;
        $this->groups = $this->SimpletippPokal->getGroups($this->simpletipp);

        return parent::generate();
    }

    protected function compile()
    {
        foreach ($this->groups as &$group) {
            $group->highscores = $this->getGroupHighscores($group);
        }
        $this->Template->groups = $this->groups;
    }

    public function getGroupHighscores($group)
    {
        if ($group->pairings === null) {
            return null;
        }
        $highscores = [];

        if ($group->pairings === false) {
            return $highscores;
        }

        foreach ($group->pairings as $memberArr) {
            $highscores[] = $this->getHighscore($group->matchgroups, $memberArr);
        }
        return $highscores;
    }

} // END class SimpletippModulePokal
