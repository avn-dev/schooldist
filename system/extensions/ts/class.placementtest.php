<?php

class Ext_TS_Placementtest {

    /**
     * @var int
     */
	protected $iInquiryId = 0;

    public $placementtestEntity = null;

	/**
	 * @var Ext_Thebing_Placementtests_Results
	 */
	protected $placementtestResult = null;

    /**
     * @var string
     */
    protected $sKey = "";

    /**
     * @var array
     */
    protected $_aMissingRequired = [];

    /**
     * @param string $sKey
     */
	public function __construct($sKey = '') {

		if (empty($sKey)) {
			throw new InvalidArgumentException('No key given for Ext_TS_Placementtest');
		}
		
		// Fallback für alte MD5-Keys (für did)
		if(strlen($sKey) === 32) {
			$inquiryId = Ext_TS_Inquiry::decodeInquiryMd5Hash($sKey);
			if($inquiryId) {
				$oPlacementtestResult = Ext_Thebing_Placementtests_Results::query()
					->where('inquiry_id', $inquiryId)
					->orderBy('id', 'DESC')
					->get()
					->first();
				if($oPlacementtestResult) {
					$sKey = $oPlacementtestResult->key;
				} else {
					
					$dNow = new \DateTime();

					$oPlacementtestResult = new Ext_Thebing_Placementtests_Results();
					$sKey = $oPlacementtestResult->getUniqueKey();

					$placementtest = \TsTuition\Entity\Placementtest::getInstance($school->default_placementtest_id);
					$oPlacementtestResult->active = 1;
					$oPlacementtestResult->inquiry_id = $inquiryId;
					$oPlacementtestResult->invited = $dNow->format('Y-m-d H:i:s');
					$oPlacementtestResult->key = $sKey;
					$oPlacementtestResult->level_id = 0;
					$oPlacementtestResult->placementtest_date = '0000-00-00';
					$oPlacementtestResult->placementtest_id = $placementtest->id;
					$oPlacementtestResult->courselanguage_id = $placementtest->courselanguage_id;
					$oPlacementtestResult->save();
					
				}
			}
		} else {
		
			$oPlacementtestRepository = Ext_Thebing_Placementtests_Results::getRepository();
			$oPlacementtestResult = $oPlacementtestRepository->getPlacementtestPerKey($sKey);
			
		}

		$this->placementtestResult = $oPlacementtestResult;
		$this->placementtestEntity = $oPlacementtestResult->getPlacementtest();
		$this->iInquiryId = $oPlacementtestResult->inquiry_id;
		$this->sKey = $sKey;
	}

    /**
     * PUBLIC: Inserts given data in database and returns 1 if
     * everything went okay.
     *
     * @param array $aResults
     * @return int
     */
	public function insertResults(array $aResults) {

		if(count($aResults) === 0) {
			return false;
		}

		$this->_aMissingRequired = $this->_checkRequired($aResults);

		if(count($this->_aMissingRequired) > 0) {
			return false;
		}

		$success = $this->_insertResults($aResults);

		if(!$success) {
			return false;
		}

		$this->placementtestResult->evaluateResult();
		$this->placementtestResult->save();

		\TsFrontend\Events\PlacementtestResult::dispatch($this->placementtestResult);

		return true;
	}

    /**
     * Returns missing required
     *
     * @return array
     */
	public function getMissingRequired(){
		return $this->_aMissingRequired;
	}

    /**
     * Inserts given data in database and returns the placementtestresult-id if
     * everything went okay.
     *
     * @param array $aResults
     * @return int
     */
	protected function _insertResults(array $aResults){

		if(count($aResults) == 0) {
			return false;
		}

		$now = new \DateTime();

		$this->placementtestResult->answered = $now->format('Y-m-d H:i:s');
		$this->placementtestResult->placementtest_date = $now->format('Y-m-d');
//		$this->placementtestResult->save();

		$questions = $this->placementtestEntity->getQuestions();

		//insert results
		foreach ($questions as $question) {

			$value = $aResults[$question->id];

			// Wenn es auch einen Wert zum eintragen gibt (Textfelder haben immer einen leeren String als Wert)
			// Oder wenn nichts geantwortet wurde muss "Immer bewerten" angetickt wurden sein, damit der Eintrag
			// hinzugefügt wird (damit man answer_is_right = 0 setzen kann)
			if (
				!empty($value) ||
				$question->always_evaluate == 1
			) {

				/** @var Ext_Thebing_Placementtests_Results_Details $detail */
				$detail = $this->placementtestResult->getJoinedObjectChild('details');

				$detail->question_id = $question->id;
				$detail->value = $value;

				// Auslagerung für Check
				$detail->evaluateAnswer();

				// Speichern, damit in der evaluateResult() (bzw. getAnswers()) mit den details gearbeitet werden kann
				// -> bCheckCache hinzuzufügen in die getJoinedObjectChilds() funktioniert nicht
				$detail->save();
			}
		}

		return true;
	}

    /**
     * Orders the results so that they can be inserted easily
     * Returns empty array if something went wrong
     * array(
     * 			idQuestion = :idQuestion,
     * 			idAnswer = :idAnswer,
     * 			value = :value(can be answer id)
     * 		)
     *
     * @param array $aResults
     * @return array
     */
	private function _orderResults(array $aResults) {

		$aReturn = array();
		foreach($aResults as $key1=>$aQuestion) {
			$question = Ext_Thebing_Placementtests_Question::getInstance($key1);

			// Weil hier sonst nur eine Antwort zurück gegeben wird
			if (
				$question->type != $question::TYPE_CHECKBOX &&
				$question->type != $question::TYPE_MULTISELECT
			) {

				foreach ($aQuestion as $key2 => $strAnswer) {

					$aReturn[$question->id]['idQuestion'] = $key1;

					if (array_key_exists(0, $aQuestion)) {

						//multiple select box
						$aReturn[$question->id]['idAnswer'] = $strAnswer;

					} else {

						//all other
						$aReturn[$question->id]['idAnswer'] = $key2;

					}

					$aReturn[$question->id]['value'] = $strAnswer;

				}
			} else {
				$aReturn[$question->id] = $aQuestion;
			}
		}
		return $aReturn;
	}

    /**
     * @param array $aResults
     * @return array
     */
	private function _checkRequired(array $aResults) {

		$questions = $this->placementtestEntity->getQuestions();

		$aMissingRequired = [];

		foreach ($questions as $question) {

			// Wenn die Frage eine Pflichtfrage ist
			if ($question->optional == 0) {

				$value = $aResults[$question->id];

				if (empty($value)) {
					$aMissingRequired[$question->id] = 1;
				}
			}
		}

		return $aMissingRequired;
	}
	
	/**
	 * Prüft ob der Test schon abgeschickt wurde oder noch nicht
	 * @return bool
	 */
	public function checkIfCommitted() {

		$result = Ext_Thebing_Placementtests_Results::query()
			->where('inquiry_id', $this->iInquiryId)
			->where('placementtest_id', $this->placementtestEntity->id)
			->where('answered', 0)
			->first();

		return $result === null;

	}

    /**
     * Prüft den Schlüssel
     *
     * @return bool
     */
	public function checkKey() {

		if ($this->iInquiryId == 0) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Speichert den Zeitpunkt ab, wann die Buchung den Test gestartet hat
	 *
	 * @throws Exception
	 */
	public function saveStartDate() {

		$oPlacementTestResultsRepository = Ext_Thebing_Placementtests_Results::getRepository();
		/** @var Ext_Thebing_Placementtests_Results $oPlacementTestResult */
		$oPlacementTestResult = $oPlacementTestResultsRepository->getPlacementtestPerKey($this->sKey);

		if(
			$oPlacementTestResult->started === false ||
			$oPlacementTestResult->started === '0000-00-00 00:00:00'
		) {
			$oPlacementTestResult->started = (new DateTime())->format('Y-m-d H:i:s');
			$oPlacementTestResult->save();
		}

	}

	public static function compareUserAnswerWithCorrectAnswer($questionInputType, $userAnswerArray, $correctAnswerArray) {

		// Keine Textareas, weil diese nicht automatisch bewertet werden können
		switch($questionInputType) {
			case Ext_Thebing_Placementtests_Question::TYPE_CHECKBOX:
			case Ext_Thebing_Placementtests_Question::TYPE_MULTISELECT:
				// Falsche Antworten geben "Minus-Prozente"
				// Checkbox und Multiselect kann man im Endeffekt gleich benutzen

				// Fallback für kaputtes Format
				if(is_scalar($userAnswerArray)) {
					$userAnswerArray = [$userAnswerArray];
				}

				$amountOfAnswersGiven = count($userAnswerArray);
				$amountOfCorrectAnswersExist = count($correctAnswerArray);

				$amountOfCorrectAnswersGiven = 0;

				foreach ($userAnswerArray as $userAnswer) {
					foreach ($correctAnswerArray as $correctAnswer) {
						if ($userAnswer == $correctAnswer->id) {
							$amountOfCorrectAnswersGiven++;
						}
					}
				}


				// Wenn alle eingetragenen Antworten richtig sind
				// (-> keine falschen Antwortmöglichkeiten- und alle richtigen Antworten sind angeklickt wurden)
				if (
					$amountOfAnswersGiven == $amountOfCorrectAnswersGiven &&
					$amountOfCorrectAnswersGiven == $amountOfCorrectAnswersExist
				) {
					return 100;
				} else {
					return 0;
				}

			case Ext_Thebing_Placementtests_Question::TYPE_TEXT:
				$bestPercent = 0;
				foreach ($correctAnswerArray as $correctAnswer) {
					similar_text(mb_strtolower($userAnswerArray), mb_strtolower($correctAnswer->text), $percent);
					if ($percent > $bestPercent) {
						$bestPercent = $percent;
					}
				}

				return $bestPercent;
			case Ext_Thebing_Placementtests_Question::TYPE_SELECT:
				foreach ($correctAnswerArray as $correctAnswer) {
					if ($userAnswerArray == $correctAnswer->id) {
						return 100;
					}
				}

				return 0;
		}

	}
}
