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

namespace Simpletipp;

/**
 * Class SimpletippQuestions
 *
 * @copyright  Martin Kozianka 2011-2014
 * @author     Martin Kozianka <martin@kozianka.de>
 * @package    Controller
 */
 
class SimpletippQuestions extends SimpletippModule {
    private $questions     = null;
    private $formId        = 'tl_simpletipp_questions';
	protected $strTemplate = 'simpletipp_questions_default';

	public function generate() {
		if (TL_MODE == 'BE') {
			$this->Template = new \BackendTemplate('be_wildcard');
			$this->Template->wildcard = '### SimpletippQuestions ###';
			$this->Template->wildcard .= '<br/>'.$this->headline;
			return $this->Template->parse();
		}

		$this->strTemplate = $this->simpletipp_template;
		
		return parent::generate();
	}
	
	protected function compile() {

        $result = $this->Database->prepare("SELECT * FROM tl_simpletipp_question"
            ." WHERE pid = ? ORDER BY sorting ASC")->execute($this->simpletipp->id);

		$this->questions = array();
		while($result->next()) {
			$q = new \stdClass;
            $q->id             = $result->id;
            $q->key            = "question_".$result->id;
			$q->question       = $result->question;
			$q->points         = $result->points;
            $q->answers        = unserialize($result->answers);
            $q->emptyValue     = '-';
            $q->arrUserAnswers = array(); //$result->userAnswer;

            $this->questions[$q->id] = $q;
		}

        if (count($this->questions > 0)) {
            $ids = implode(',', array_keys($this->questions));
            $result = $this->Database->execute("SELECT * FROM tl_simpletipp_answer WHERE pid IN(".$ids.")");
            while($result->next()) {
                $this->questions[$result->pid]->arrUserAnswers[$result->member] = $result->answer;
                if ($result->member == $this->simpletippUserId) {
                    $this->questions[$result->pid]->userAnswer = $result->answer;
                }
            }



        }

        $quizFinished = time() > $this->simpletipp->quizDeadline;

        // Die übergebenen Antworten eintragen
        if (!$quizFinished && $this->Input->post('FORM_SUBMIT') === $this->formId) {
            $this->processAnswers();
            $this->redirect($this->addToUrl(''));
        }

        $this->Template->finished   = $quizFinished;
        $this->Template->formId     = $this->formId;
        $this->Template->action     = ampersand(\Environment::get('request'));
        $this->Template->messages   = \Simpletipp::getSimpletippMessages();

        $this->Template->isPersonal = $this->isPersonal;
		$this->Template->questions  = $this->questions;

    }

    private function processAnswers() {
        $message = 'Folgende Antworten wurden eingetragen:<ul>';
        $tmpl    = '<li><span class="question">%s</span> <span class="anwer">%s</span></li>';
        foreach($this->questions as $question) {
            $userAnswer = \Input::post($question->key);

            if($userAnswer != $question->emptyValue) {
                $this->Database->prepare("DELETE FROM tl_simpletipp_answer WHERE pid = ? AND member = ?")
                    ->execute($question->id, $this->User->id);

                $this->Database->prepare(
                    "INSERT INTO tl_simpletipp_answer (pid, member, answer) VALUES (?,?,?)")
                    ->execute($question->id, $this->User->id, $userAnswer);
                $message .= sprintf($tmpl, $question->question, $userAnswer);
            }
        }
        $message .= '</ul>';
        \Simpletipp::addSimpletippMessage($message);
        return true;
    }

} // END class SimpletippQuestions


