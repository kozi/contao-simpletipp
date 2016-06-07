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

namespace Simpletipp\Modules;

use \Simpletipp\Simpletipp;
use \Simpletipp\SimpletippModule;

/**
 * Class SimpletippQuestions
 *
 * @copyright  Martin Kozianka 2014-2016
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */
 
class SimpletippQuestions extends SimpletippModule
{
    private $questions     = null;
    private $formId        = 'tl_simpletipp_questions';
	protected $strTemplate = 'simpletipp_questions_default';

	public function generate()
    {
		if (TL_MODE == 'BE')
        {
			$this->Template = new \BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippQuestions ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}

		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}
	
	protected function compile()
    {
        $this->questions = [];
        $participants = [];

        $result = $this->Database->prepare("SELECT * FROM tl_simpletipp_question"
            ." WHERE pid = ? ORDER BY sorting ASC")->execute($this->simpletipp->id);

        while($result->next())
        {
			$q = new \stdClass;
            $q->id             = $result->id;
            $q->key            = "question_".$result->id;
			$q->question       = $result->question;
            $q->points         = $result->points;
            $q->results        = ($result->results == '') ? [] : unserialize($result->results);
            $q->answers        = [];
            foreach (unserialize($result->answers) as $val)
            {
                $q->answers[$val] = (object) [
                    'value'  => $val,
                    'count'  => 0,
                    'member' => [],
                ];
            }

            $q->emptyValue     = '-';
            $q->arrUserAnswers = [];

            $this->questions[$q->id] = $q;
		}

        if (count($this->questions) > 0)
        {
            $objMembers   = Simpletipp::getGroupMember($this->simpletipp->participant_group);
            if ($objMembers != null)
            {
                foreach ($objMembers as $objMember)
                {
                    $objM                 = (object) $objMember->row();
                    $objM->questionPoints = 0;

                    $participants[$objMember->id] = $objM;
                }
            }

            $ids    = implode(',', array_keys($this->questions));
            $result = $this->Database->execute("SELECT * FROM tl_simpletipp_answer WHERE pid IN(".$ids.")");
            while($result->next())
            {
                $question             = &$this->questions[$result->pid];
                $objMember            = &$participants[$result->member];

                if ($objMember === null)
                {
                    continue;
                }

                $objMember->theAnswer = $result->answer;

                if (in_array($objMember->theAnswer, $question->results))
                {
                    $objMember->questionPoints += $question->points;
                }

                $question->answers[$objMember->theAnswer]->member[] = clone $objMember;
                $question->answers[$objMember->theAnswer]->count++;

                if ($result->member == $this->simpletippUserId)
                {
                    $question->currentMember = clone $objMember;
                }
            }
        }

        $quizFinished = time() > $this->simpletipp->quizDeadline;

        // Die übergebenen Antworten eintragen
        if (!$quizFinished && $this->Input->post('FORM_SUBMIT') === $this->formId)
        {
            $this->processAnswers();
            $this->redirect($this->addToUrl(''));
        }

        $this->Template->finished   = $quizFinished;
        $this->Template->formId     = $this->formId;
        $this->Template->action     = ampersand(\Environment::get('request'));
        $this->Template->messages   = Simpletipp::getSimpletippMessages();

        $this->Template->isPersonal = $this->isPersonal;
        $this->Template->member     = $participants[$this->simpletippUserId];
		$this->Template->questions  = $this->questions;

        usort($participants, function($a , $b)
        {
            return $b->questionPoints - $a->questionPoints;
        });

        $this->Template->arrRanking = $participants;
    }

    private function processAnswers()
    {
        $message = 'Folgende Antworten wurden eingetragen:<ul>'."\n";
        $tmpl    = '<li><span class="question">%s</span> <span class="anwer">%s</span></li>'."\n";
        foreach($this->questions as $question)
        {
            $userAnswer = \Input::post($question->key);

            if($userAnswer != $question->emptyValue)
            {
                $this->Database->prepare("DELETE FROM tl_simpletipp_answer WHERE pid = ? AND member = ?")
                    ->execute($question->id, $this->User->id);

                $this->Database->prepare(
                    "INSERT INTO tl_simpletipp_answer (pid, member, answer) VALUES (?,?,?)")
                    ->execute($question->id, $this->User->id, $userAnswer);
                $message .= sprintf($tmpl, $question->question, $userAnswer);
            }
        }
        $message .= '</ul>'."\n\n";
        Simpletipp::addSimpletippMessage($message);

        $subject = 'Quiz - '.date('d.m.Y H:i:s').' '.$this->User->firstname.' '.$this->User->lastname;
        $content = strip_tags($message);

        // Send to user
        if ($this->User->simpletipp_email_confirmation == '1')
        {
            $email           = new \Email();
            $email->from     = $this->simpletipp->adminEmail;
            $email->fromName = $this->simpletipp->adminName;
            $email->subject  = $subject;
            $email->text     = $content;
            $email->replyTo($this->User->email);
            $email->sendTo($this->User->email);
        }

        // Send encoded to admin
        $email           = new \Email();
        $email->from     = $this->simpletipp->adminEmail;
        $email->fromName = $this->simpletipp->adminName;
        $email->subject  = $subject;
        $email->text     = base64_encode($content);
        $email->replyTo($this->User->email);
        $email->sendTo($GLOBALS['TL_ADMIN_EMAIL']);


        return true;
    }

} // END class SimpletippQuestions
