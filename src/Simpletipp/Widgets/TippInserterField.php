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

namespace Simpletipp\Widgets;

use Contao\Date;
use Simpletipp\Models\SimpletippMatchModel;
use Simpletipp\Models\SimpletippTippModel;

class TippInserterField extends \Widget
{
    protected $strTemplate     = 'be_widget';
    protected $blnSubmitInput  = true;
    protected $varValue        = [];


    public function generate()
    {
        $this->varValue = ($this->varValue === null) ? [] : $this->varValue;

        // Initialize the tab index
        if (!\Cache::has('tabindex'))
        {
            \Cache::set('tabindex', 1);
        }
        $tabindex = \Cache::get('tabindex');

        if ($this->activeRecord->member_id === '0')
        {
            return '<p>Bitte zunächst ein Mitglied wählen!</p>';
        }

        if ($this->activeRecord->leagueGroups)
        {
            $arrMatches  = [];
            $collectionM = SimpletippMatchModel::findBy('groupID', $this->activeRecord->leagueGroups, ['order'  => 'deadline ASC']);
            foreach($collectionM as $objMatch)
            {
                $arrMatches[$objMatch->id] = [
                    'id'       => $objMatch->id,
                    'title'    => $objMatch->title,
                    'deadline' => Date::parse("d.m.Y H:i", $objMatch->deadline),
                    'tipp'     => false
                ];
            }
        }

        $collectionT = SimpletippTippModel::findBy('member_id', $this->activeRecord->member_id);
        foreach($collectionT as $objTipp)
        {
            if(array_key_exists($objTipp->match_id, $arrMatches))
            {
                $arrMatches[$objTipp->match_id]['tipp'] = $objTipp->tipp;
            }
        }

        $strReturn = '<table id="ctrl_'.$this->strId.'" class="tl_pokalRanges" data-tabindex="'.$tabindex++.'"><tbody>';
        $tmpl      = '<tr>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                      </tr>';
        $tmplInput = '<input type="hidden" name="tippInserter_matchId[]" value="%s"><input style="width:64px;" type="text" class="tl_text" name="tippInserter_tipp[]" onfocus="Backend.getScrollOffset()">';

        foreach($arrMatches as $arrM)
        {
            $tipp       = ($arrM['tipp'] === false) ? sprintf($tmplInput, $arrM['id']) : $arrM['tipp'];
            $strReturn .= sprintf($tmpl,
                    $arrM['deadline'],
                    $arrM['title'],
                    $tipp
                );
        }
        $strReturn .= '</tbody></table>';

        \Cache::set('tabindex', $tabindex);
        return $strReturn;
    }

    private  function getGroupOptions($value)
    {
        $options = '<option value="-">Bitte wählen...</option>';
        $tmpl    = '<option%s value="%s">%s</option>';
        foreach ($this->leagueGroups as $g)
        {
            $sel = ($value == $g->title) ? ' selected="selected"': '';
            $options .= sprintf($tmpl, $sel, $g->title, $g->title);
        }
        return $options;
    }

    public function validate()
    {

    }

    protected function validator($varInput)
    {
        return $varInput;
    }

}


