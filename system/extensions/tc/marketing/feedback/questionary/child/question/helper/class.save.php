<?php

class Ext_TC_Marketing_Feedback_Questionary_Child_Question_Helper_Save {
	
	/**
	 *
	 * @var MVC_Request 
	 */
	protected $_oRequest = null;
	
	/**
	 *
	 * @var Ext_TC_Marketing_Feedback_Questionary_Child
	 */
	protected $_oWDBasic = null;
	
	/**
	 *
	 * @var Ext_Gui2_Data 
	 */
	protected $_oDataClass = null;
	
	/**
	 *
	 * @var boolean 
	 */
	public $bSave = false;

    /**
     * @var bool
     */
    public $bShowSkipCheckBox = false;

	/**
	 *
	 * @var array
	 */
	protected $_aErrors = array();

	/**
	 *
	 * @var string 
	 */
	protected $_sTransaction = 'save_questionary_child_question';

	/**
	 * Konstruktor
     *
	 * @param WDBasic $oWDBasic
	 * @param MVC_Request $oRequest
	 * @param Ext_Gui2_Data $oDataClass
	 */
	public function __construct(WDBasic $oWDBasic, MVC_Request $oRequest, Ext_Gui2_Data $oDataClass) {
        $this->_oRequest = $oRequest;
        $this->_oWDBasic = $oWDBasic;
        $this->_oDataClass = $oDataClass;
    }

    /**
     * Prüft ob die gewählten Fragen dem Fragebogen
     * hinzugefügt werden dürfen
     *
     * @param array $aData
     * @param int $iQuestionnairId
     * @return bool
     */
    private function _isAllowedToAddQuestions(array $aData, $iQuestionnairId) {

        $aQuestions = array();
        // Laden der bisher vorhandenen Fragen
        $oQuestionnaire = Ext_TC_Marketing_Feedback_Questionary::getInstance($iQuestionnairId);
        $aQuestionnaireQuestionsRatings = $oQuestionnaire->getQuestionsRatings();
        // Daten aufbereiten
        foreach($aQuestionnaireQuestionsRatings as $aQuestionsRatings) {

        	if(
        		in_array($aQuestionsRatings['question']->id, $aData['questions']) &&
				$aQuestionsRatings['child']->id != $this->_oWDBasic->id
			) {
				$this->_aErrors[] = array(
					'type' => 'error',
					'message' => $this->_oDataClass->t('Eine Frage kann nicht doppelt zu einem Fragebogen hinzugefügt werden.')
				);
			}

            $aQuestions[] = array(
                'question_type' => $aQuestionsRatings['question']->question_type,
                'quantity_stars' => (int)$aQuestionsRatings['question']->quantity_stars,
                'rating_id' => (int)$aQuestionsRatings['rating']->id
            );
        }
        // Laden der neuen Fragen
        foreach($aData['questions'] as $newQuestionId) {
            $oQuestion = Ext_TC_Marketing_Feedback_Question::getInstance($newQuestionId);
            // Wurde eine rating_id übermittel?
            if(isset($aData['question_rating'])) {
                $iRatingId = (int)$aData['question_rating'][$newQuestionId];
            } else {
                $iRatingId =  0;
            }
            $aQuestions[] = array(
                'question_type' => $oQuestion->question_type,
                'quantity_stars' => (int)$oQuestion->quantity_stars,
                'rating_id' => $iRatingId
            );
        }
        // Überprüfen der Fragen
        $aHelper = array();
        foreach($aQuestions as $question) {
            if(
                $question['question_type'] === 'rating' ||
                $question['question_type'] === 'stars'
            ) {
                $isRating = $question['question_type'] === 'rating';
                $isStars = $question['question_type'] === 'stars';
                if(
                    isset($aHelper['rating']) ||
                    isset($aHelper['stars'])
                ) {
                    if(
                        !isset($aHelper['rating'][$question['rating_id']]) && $isRating ||
                        !isset($aHelper['stars'][$question['quantity_stars']]) && $isStars
                    ) {
                        // Will User trotzdem speichern?
                        if(!$this->_oRequest->get('ignore_errors')) {
                            $this->bShowSkipCheckBox = true;
                            $this->_aErrors[] = array(
                                'type'	  => 'hint',
                                'message' => $this->_oDataClass->t('Eine Vermischung der Bewertungsarten führt dazu, dass der Fragebogen nicht sinnvoll ausgewertet werden kann. Möchten Sie diese Frage trotzdem speichern?')
                            );
                            // Aus foreach aussteigen
                            break;
                        }
                    }
                }
                if($isRating) {
                    $aHelper['rating'][$question['rating_id']] = true;
                }
                if($isStars) {
                    $aHelper['stars'][$question['quantity_stars']] = true;
                }
                if(
                    array_key_exists('stars', $aHelper) &&
                    array_key_exists('rating', $aHelper)
                ) {
                    // Will User trotzdem speichern?
                    if(!$this->_oRequest->get('ignore_errors')) {
                        $this->bShowSkipCheckBox = true;
                        $this->_aErrors[] = array(
                            'type'	  => 'hint',
                            'message' => $this->_oDataClass->t('Eine Vermischung der Bewertungsarten führt dazu, dass der Fragebogen nicht sinnvoll ausgewertet werden kann. Möchten Sie diese Frage trotzdem speichern?')
                        );
                    }
                    // Aus foreach aussteigen
                    break;
                }
            }
        }
        $bRetVal = $this->bShowSkipCheckBox;
        return $bRetVal;
    }

    /**
     * Speichert letztendlich die Daten des Dialogs
     *
     * @param array $aData
     * @return bool
     */
    private function _saveData(array $aData) {

		$oQuestionGroup = $this->_oWDBasic->getQuestionGroup();
		$aGroupQuestions = $oQuestionGroup->getGroupQuestions();

		foreach($aData as $sField => $mValue) {

			if($sField == 'question_rating') {
				continue;
			}

			if($sField == 'parent_id') {
				$this->_oWDBasic->parent_id = $mValue;
			}
			else if($sField != 'questions') {
				$oQuestionGroup->$sField = $mValue;
			}
			else {
				$iPosition = 1;
				foreach($mValue as $iQuestionId) {

					$oGroupQuestion = $oQuestionGroup->getGroupQuestionByQuestionId($iQuestionId);
					if(!$oGroupQuestion) {
						$oGroupQuestion = $oQuestionGroup->generateGroupQuestion();
					} else {
						unset($aGroupQuestions[$oGroupQuestion->id]);
					}
					$oGroupQuestion->question_id = (int)$iQuestionId;
					$oGroupQuestion->position = $iPosition;

//					$iRating = 0;
//					if(isset($aData['question_rating'])) {
//						foreach($aData['question_rating'] as $iRatingQuestionId => $iRatingId) {
//							if($iRatingQuestionId == $iQuestionId) {
//								$iRating = $iRatingId;
//								break;
//							}
//						}
//					}
//					$oGroupQuestion->rating_id = (int)$iRating;

					++$iPosition;
				}
			}
		}

		// nicht mehr benutzte childs löschen
		foreach($aGroupQuestions as $oGroupQuestion) {
			$oQuestionGroup->removeGroupQuestionById($oGroupQuestion->id);
		}

        // speichern
        if(
            $this->bSave &&
            empty($this->_aErrors)
        ) {
            $mValidate = $this->_oWDBasic->validate();
            if($mValidate === true) {
                $this->_oWDBasic->save();
                $this->_commitTransaction();
                return true;
            } else {
                $aError = $this->_oDataClass->getErrorData($mValidate, $this->_oRequest->get('action'), 'error');
                $this->_setError($aError);
            }
        }

    }

	/**
	 * Speichert den Dialog
     *
	 * @return boolean
	 */
	public function save() {

        global $_VARS;

		$aSaveData = $this->_oRequest->input('question', null);

		if(!empty($aSaveData)) {
			
			$this->_startTransaction();
            $this->bShowSkipCheckBox = false;

			try {

				foreach($aSaveData as $iChildQuestionId => $aData) {

                    $bSkip = true;
                    if(isset($aData['questions'])) {
                        $bSkip = $this->_isAllowedToAddQuestions($aData, $_VARS['parent_gui_id'][0]);
                    }

                    if(!$bSkip) {
                        if($this->_saveData($aData) === true) {
                            return true;
                        }
                        // Es kann und darf nur ein Element geben!
                        break;
                    }

				}

			} catch (Exception $e) {
				__pout($e);
				$this->_rollbackTransaction();
			}
			
		}
		
		return false;
	}
	
	/**
	 * Liefert alle Fehler, die während dem Speichern aufgetreten sind
     *
	 * @return array
	 */
	public function getError() {
		return $this->_aErrors;
	}
	
	/**
	 * Setzt einen Fehler
     *
	 * @param array $aError
	 */
	protected function _setError($aError) {

		if(empty($this->_aErrors)) {
			$this->_aErrors[0] = $this->_oDataClass->t('Beim Speichern ist ein Fehler aufgetreten!');
		}

		$this->_aErrors[] = $aError;

	}
	
	/**
	 * Transaktion beginnen
	 */
	protected function _startTransaction() {
		DB::begin($this->_sTransaction);
	}
	
	/**
	 * Transaktion abschließen
	 */
	protected function _commitTransaction(){
		DB::commit($this->_sTransaction);
	}
	
	/**
	 * Transaktion rückgängig machen
	 */
	protected function _rollbackTransaction() {
		DB::rollback($this->_sTransaction);
	}
	
}