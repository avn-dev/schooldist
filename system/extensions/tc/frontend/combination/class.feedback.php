<?php

use Smarty\Smarty;

class Ext_TC_Frontend_Combination_Feedback extends Ext_TC_Frontend_Combination_Abstract {

	/**
	 * @var Ext_TA_Inquiry|Ext_TS_Inquiry
	 */
	protected $oInquiry;

	/**
	 * @var Ext_TC_Marketing_Feedback_Questionary
	 */
	protected $oQuestionary;

	/**
	 * @var Ext_TC_Marketing_Feedback_Questionary_Process
	 */
	protected $oQuestionaryProcess;

	/**
	 * @var array
	 */
	protected $aQuestionaryChilds;

	public function __construct(Ext_TC_Frontend_Combination $oCombination, Smarty $oSmarty = null) {
		parent::__construct($oCombination, $oSmarty);
		$this->oSession = \Core\Handler\SessionHandler::getInstance();
	}

	/**
	 * Standard Funktion um Variablen
	 * zu initialisieren
	 */
	public function initDefaultVars() {
		$this->_assign('actionUrl', $this->_oRequest->get('actionUrl'));
		$this->_assign('r', $this->_oRequest->get('r'));
	}

	/**
	 * Standard Funktion von Smarty
	 */
	protected function _default() {

		$this->oQuestionaryProcess = \Factory::executeStatic(\Ext_TC_Marketing_Feedback_Questionary_Process::class, 'getRepository')->findOneBy(
			array('link_key' => $this->_oRequest->get('r'))
		);

		if(!$this->oQuestionaryProcess) {
			$this->_assign('sError', $this->t('Keinen Fragebogen gefunden!'));
			$this->log('Internal error: No Ext_TC_Marketing_Feedback_Questionary_Process found', $this->_oRequest->getAll());
			return false;
		}

		// Beim ersten Aufruf des Feedback-Formulars muss der
		// Start-Timestamp gespeichert werden
		if(!$this->oQuestionaryProcess->started) {
			$this->oQuestionaryProcess->started = time();
			if($this->oQuestionaryProcess->validate()) {
				$this->oQuestionaryProcess->save();
			}
		}
		else if($this->oQuestionaryProcess->answered) {
			$this->_assign('sError', $this->t('Dieses Feedbackformular wurde bereits ausgefüllt!'));
			return false;
		}

		$this->oInquiry = Factory::getInstance('Ext_TC_Journey', $this->oQuestionaryProcess->journey_id)->getInquiry();
		$this->oQuestionary = Ext_TC_Marketing_Feedback_Questionary::getInstance($this->oQuestionaryProcess->questionary_id);

		try {
			$sCacheKey = 'FrontendFeedbackResult' . $this->_oRequest->get('r');
			$this->aQuestionaryChilds = WDCache::get($sCacheKey);
			$this->aQuestionaryChilds = null;
			if($this->aQuestionaryChilds === null) {
				$aParameters = array(
					$this->oInquiry,
					$this->oQuestionary,
					$this->_oCombination->getLanguage()
				);
				/** @var Ext_TC_Marketing_Feedback_Questionary_Generator $oQuestionaryGenerator */
				$oQuestionaryGenerator = Ext_TC_Factory::getObject('Ext_TC_Marketing_Feedback_Questionary_Generator', $aParameters);
				$this->aQuestionaryChilds = $oQuestionaryGenerator->generate();
				//WDCache::set($sCacheKey, (60*30), $this->aQuestionaryChilds, false, 'FrontendFeedbackResult');
			}
		}
		catch(Exception $ex) {
			__pout($ex);
			$this->_assign('sError', $this->t('Interner Fehler!'));
			$this->log('Internal error: Generating $oQuestionaryGenerator', [$ex->getMessage(), $this->oQuestionaryProcess->getData(), $ex]);
			return false;
		}

		if(count($this->aQuestionaryChilds) === 0) {
			$this->_assign('sError', $this->t('Es wurden keine Fragen gefunden!'));
		}

		// Beinhaltet alle bisherigen
		// Antwort-Werte
		$aAnswers = array();

		// Formular wird versucht zu speichern
		if($this->_oRequest->exists('save')) {
			try {
				DB::begin('saveFrontendFeedbackResult');
				$this->save();
				DB::commit('saveFrontendFeedbackResult');

				$this->_assign('sSuccess', $this->t('Erfolgreich!'));
				// Csrf muss nach dem speichern
				// gelöscht werden
				$this->unsetCsrf();

				// Event abschicken
				Factory::executeStatic(\TcFrontend\Events\FeedbackFormSaved::class, 'dispatch', [$this->oQuestionaryProcess]);
			}
			catch(FeedbackSaveException $ex) {
				DB::rollback('saveFrontendFeedbackResult');
				$this->_assign('sFormError', $ex->getMessage());
				// Holt sich alle bisher übermittelten
				// Antwort-Werte damit diese wieder
				// zugewiesen werden können
				$aAnswers = $this->getAnswers();
			}
			catch(Exception $e) {
				DB::rollback('saveFrontendFeedbackResult');
				$this->_assign('sError', $this->t('Interner Fehler!'));
				$this->log('Internal error while saving', [$e->getMessage(), $e->getTraceAsString(), $this->oQuestionaryProcess->getData()]);
			}
		}

		$this->_assign('sButtonLabel', $this->t('Speichern'));
		$this->_assign('sQuestionRequiredLabel', $this->t('Pflichtfragen'));
		$this->_assign('aQuestions', $this->aQuestionaryChilds);
		$this->_assign('aSession', ['sCsrf' => $this->getCsrf()]); // Deprecated
		$this->_assign('sCsrf', $this->getCsrf());
		$this->_assign('aAnswers', $aAnswers);

		// {extends} ohne Pfad ermöglichen
		$this->_oSmarty->setTemplateDir(Util::getDocumentRoot().'storage/tc/templates/frontend');

	}

	/**
	 * Speichert das Feedbackformular
	 *
	 * @return bool
	 * @throws FeedbackSaveException
	 */
	protected function save() {

		// Prüft den csrf Code auf Richtigkeit
		if($this->getCsrf() !== $this->_oRequest->get('csrf')) {
			$this->log('Internal error: CSRF token invalid', [$this->getCsrf(), $this->_oRequest->get('csrf'), $this->_oRequest->getAll(), $this->oQuestionaryProcess->getData()]);
			throw new FeedbackSaveException($this->t('Interner Fehler!'));
		}

		// Liefert alle Antworten
		$answers = $this->getAnswers();

		$iTotalStatisfactionValue = 0;
		$iTotalStatisfactionQuestionQty = 0;
		// Prüft ob alle Pflichtfragen beantwortet wurden
		foreach($this->aQuestionaryChilds as $aQuestionaryChild) {
			// Überschriften sind für das Abspeichern
			// nicht interessant und können übersprungen werden
			if(isset($aQuestionaryChild['heading'])) {
				continue;
			}
			$aQuestion = $aQuestionaryChild;
			// Alle Frage-Informationen beziehen wir über die Datenbank
			$oQuestionGroupQuestion = Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group_Question::getInstance($aQuestion['questionGroupQuestionId']);
			$oQuestion = Ext_TC_Marketing_Feedback_Question::getInstance($aQuestion['questionId']);
			$sQuestionType = $oQuestion->question_type;
			// Da jede Frage mehrere Spalten haben kann
			// müssen hier alle Spalten durchlaufen werden
			foreach($aQuestion['columns'] as $column) {
				$answer = $answers[$aQuestion['questionGroupQuestionId']][$column['dependencyId']];
				if($answer !== null) {
					$answer = trim($answer);
				}
				// Prüft ob Frage eine Pflichtfragen ist
				if((bool)$aQuestion['questionRequired'] && ($answer === '' || $answer === null)) {
					throw new FeedbackSaveException($this->t('Bitte Felder ausfüllen!'));
				}
				// Sobald Frage keine Pflichtfrage ist, kann geprüft werden
				// ob die Frage überhaupt beantwortet wurde
				else if($answer === '' || $answer === null) {
					continue;
				}
				// Fügt die übermittelten Antwort-Werte
				// dem Parent Objekt hinzu
				$this->addRelationData(
					$this->oQuestionaryProcess,
					$aQuestion['questionGroupQuestionId'],
					$column['dependencyId'],
					$answer,
					$sQuestionType
				);
				// Falls die aktuelle Frage eine Gesamtzufriedenheit ist
				// wird hier der Wert berechnet
				$bIsTotalSatisFaction = (bool)$oQuestion->overall_satisfaction;
				if($bIsTotalSatisFaction && $answer !== null) {
					switch($sQuestionType) {
						case 'stars':
							// Ein Stern soll als 0% gewertet werden
							$answer = ($answer - 1) * (100 / ($oQuestion->quantity_stars - 1));
							break;
						case 'rating':
							$answer = ($answer - 1) * (100 / ($oQuestion->getRating()->getMaxValue() - 1));
							break;
					}
					$iTotalStatisfactionValue += (int)$answer;
					++$iTotalStatisfactionQuestionQty;
				}
			}
		}
		// Speichert Gesamtinformationen zu den Antworten
		$this->oQuestionaryProcess->answered = time();
		if($iTotalStatisfactionQuestionQty > 0) {
			$this->oQuestionaryProcess->overall_satisfaction =  $iTotalStatisfactionValue / $iTotalStatisfactionQuestionQty;
		} else {
			$this->oQuestionaryProcess->overall_satisfaction = null;
		}

		$mValidate = $this->oQuestionaryProcess->validate();
		if($mValidate === true) {
			$this->oQuestionaryProcess->save();
		} else {
			$this->log('Internal error: $this->oQuestionaryProcess::validate() failed', [$mValidate, $this->oQuestionaryProcess->getData()]);
			throw new FeedbackSaveException($this->t('Interner Fehler!'));
		}

		return true;
	}

	/**
	 * @param Ext_TC_Marketing_Feedback_Questionary_Process $oQuestionaryProcess
	 * @param $iQuestionGroupQuestionId
	 * @param $iDependencyId
	 * @param $sAnswer
	 * @param $sQuestionType
	 * @return bool
	 */
	protected function addRelationData(Ext_TC_Marketing_Feedback_Questionary_Process $oQuestionaryProcess, $iQuestionGroupQuestionId, $iDependencyId, $sAnswer, $sQuestionType) {

		$oQuestionaryProcessResult = $oQuestionaryProcess->getJoinedObjectProcessResult();
		$oQuestionaryProcessResult->questionary_question_group_question_id = $iQuestionGroupQuestionId;
		$oQuestionaryProcessResult->dependency_id = $iDependencyId;

		// Filtern bevor die Antwort
		// in die Datenbank geschrieben wird
		if($sQuestionType === 'textfield')
			$oQuestionaryProcessResult->answer = filter_var($sAnswer, FILTER_SANITIZE_STRING);
		else {
			$oQuestionaryProcessResult->answer = filter_var($sAnswer, FILTER_SANITIZE_NUMBER_INT);
		}

	}

	/**
	 * Gibt den Csrf Wert zurück
	 *
	 * @return string
	 */
	private function getCsrf() {
		if(!$this->oSession->has('feedback_form_csrf')) {
			$this->oSession->set('feedback_form_csrf', Ext_TC_Util::generateRandomString(32));
		}

		return $this->oSession->get('feedback_form_csrf');
	}

	/**
	 * Löscht den aktuellen Csrf Wert
	 */
	private function unsetCsrf() {
		$this->oSession->remove('feedback_form_csrf');
	}

	/**
	 * Gibt alle Antworten zurück
	 *
	 * @return mixed
	 */
	private function getAnswers() {
		$aAnswers = $this->_oRequest->input('question');
		return $aAnswers;
	}

	/**
	 * Generiert ein DependencyObject anhand des Typen
	 *
	 * @param $sType
	 * @param $iId
	 * @return stdClass
	 * @throws InvalidArgumentException
	 */
	protected function getDependencyObject($sType, $iId) {

		switch($sType) {
			case 'transfer':
				$oDependencyObject = new stdClass();
				$oDependencyObject->iTypeId = $iId;
				break;
			default:
				throw new InvalidArgumentException('Invalid Dependency-Object Type');
		}

		return $oDependencyObject;
	}

	/**
	 * @return bool|void
	 */
	protected function executeInitializeData() {

		WDCache::deleteGroup('FrontendFeedbackResult');

		return true;
	}
	
}

class FeedbackSaveException extends Exception {}
