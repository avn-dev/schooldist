<?php

class Ext_TC_Marketing_Feedback_Questionary_Child_Gui2_Data extends Ext_TC_Gui2_Data {

	/**
	 * Editier Dialog
     *
	 * @param Ext_Gui2 $oGui
     *
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog(Ext_Gui2 $oGui) {
		$oDialog = $oGui->createDialog($oGui->t('Eintrag editieren'), $oGui->t('Eintrag hinzufügen'));
		return $oDialog;
	}

	/**
	 * Dialog um Themen anzulegen
	 *
	 * @param Ext_Gui2 $oGui
	 *
	 * @return Ext_Gui2_Dialog
	 */
	public static function getHeadingDialog(Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Überschrift editieren'), $oGui->t('Überschrift hinzufügen'));

        $aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');
        $aHeadings	= Ext_TC_Util::getHeadingTypes();

        $oJoinedObjectContainer = $oDialog->createJoinedObjectContainer('heading', array('min' => 1, 'max' => 1));

        // Typ muss als erstes hier stehen, damit die JoinObjectContainer-Infos im JS verarbeitet werden können
        $oJoinedObjectContainer->setElement($oJoinedObjectContainer->createRow($oGui->t('Typ'), 'select', array(
            'db_alias' => 'tc_fqnch',
            'db_column' => 'type',
            'select_options' => $aHeadings,
            'required' => true
        )));

        $oJoinedObjectContainer->setElement($oDialog->createI18NRow($oGui->t('Überschrift'), array(
            'db_alias' => 'headings_tc_i18n',
            'db_column'=> 'heading',
            'i18n_parent_column' => 'topic_id',
            'joined_object_key' => 'heading',
            'required' => true
        ), $aLanguages));

		$oDialog->setElement($oJoinedObjectContainer);

		$oDialog->setElement($oDialog->createRow($oGui->t('Elternelement'), 'select', array(
			'db_alias' => 'tc_fqnc',
			'db_column' => 'parent_id',
			'selection' => new Ext_TC_Marketing_Feedback_Questionary_Child_Gui2_Selection_Parents()
		)));

		return $oDialog;
	}
	
	/**
	 * Dialog um Themen anzulegen
     *
	 * @param Ext_Gui2 $oGui
     *
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getQuestionDialog(Ext_Gui2 $oGui) {
		$oDialog = $oGui->createDialog($oGui->t('Frage editieren'), $oGui->t('Frage hinzufügen'));
		return $oDialog;
	}

    /**
     * Get edit dialog html
     *
     * @param Ext_Gui2_Dialog $oDialog
     * @param array $aSelectedIds
     * @param bool|string $sAdditional
     *
     * @throws UnexpectedValueException
     * @return array
     */
	public function getEditDialogHTML(&$oDialog, $aSelectedIds, $sAdditional = false) {

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

        // Bei den Childs MUSS additional immer vorhanden sein, falls nicht, additional aus vorhandenem Child setzen
        if(empty($sAdditional)) {
            if($this->oWDBasic->type != '') {
                $sAdditional = $this->oWDBasic->type;
            } else {
                throw new UnexpectedValueException('No type found!');
            }
        }

        // Der Fragendialog wird dynamisch aufgebaut
        if(
            $sAdditional == 'question' ||
            $this->oWDBasic->type == 'question'
        ) {
			$oDialog->aElements = array();
            $this->_setQuestionDialogElements($oDialog);
        }

		$aData = parent::getEditDialogHTML($oDialog, $aSelectedIds, $sAdditional);

		return $aData;
	}
	
	/**
	 * Setzt die Elemente für den Dialog "Fragen"
     *
	 * @param Ext_Gui2_Dialog $oDialog
	 */
	protected function _setQuestionDialogElements(Ext_Gui2_Dialog &$oDialog) {
		
		$aTopics = Ext_TC_Marketing_Feedback_Topic::getSelectOptions();

		/** @var $oObject Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group */
		$oObject = $this->oWDBasic->getQuestionGroup();
		$aGroupQuestions = $oObject->getGroupQuestions();
		
		$aSelected = array();
		foreach($aGroupQuestions as $oGroupQuestion) {
			$aSelected[] = $oGroupQuestion->question_id;
		}
		
		$sTopicId = $this->_createFieldId('topic_id', $oObject);

		$oDialog->setElement($oDialog->createRow($this->t('Thema'), 'select', array(
			'select_options' => Ext_TC_Util::addEmptyItem($aTopics),
			'name' => $sTopicId,
			'id' => $sTopicId,
			'default_value' => $oObject->topic_id,
			'class' => 'topic_select',
			'required' => 1,
		)));

		$sQuestionId = $this->_createFieldId('questions', $oObject);

		/** @var $aSelectOptionsSwap Ext_TC_Marketing_Feedback_Question[] */
		$aSelectOptionsSwap = array();
		$aSelectOptions = array();
		$oTopic = $oObject->getTopic();
		$aQuestions = $oTopic->getAllocatedQuestions();
		foreach($aQuestions as $oQuestion) {
			$oGroupQuestion = Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group_Question::getRepository()->findOneBy(
				array('questionary_question_group_id' => $oObject->id, 'question_id' => $oQuestion->id)
			);
			$iPosition = (int)$oGroupQuestion->position;
			if($iPosition === 0) {
				// Muss ins Minus laufen damit keine Positionen
				// überschrieben werden da Position standardmäßig 0 ist
				$iPosition = count($aSelectOptionsSwap) * -1;
			}
			$aSelectOptionsSwap[$iPosition] = $oQuestion;
		}
		ksort($aSelectOptionsSwap);
		foreach($aSelectOptionsSwap as $oQuestion) {
			$aSelectOptions[$oQuestion->id] = $oQuestion->getQuestion();
		}

		$oDialog->setElement($oDialog->createRow($this->t('Auswahl'), 'select', array(
			'name' => $sQuestionId,
			'id' => $sQuestionId,
			'multiple' => 5, 
			'jquery_multiple' => 1,
			'select_options' => $aSelectOptions,
			'default_value' => $aSelected,
			'searchable' => 1,
			'sortable' => 1,
			'style' => 'height: 105px; width: 600px;'
		)));

        // Pflichtfragen
		$sRequiredQuestionId = $this->_createFieldId('required_questions', $oObject);
		$aRequiredOptions['name'] = $sRequiredQuestionId;
		if((bool)$oObject->required_questions) {
			$aRequiredOptions['default_value'] = true;
		}
        $oDialog->setElement($oDialog->createRow($this->t('Pflichtfragen'), 'checkbox', $aRequiredOptions));

		// Abhänigkeiten von
		$sParentQuestionChildId = $this->_createFieldId('parent_id', $oObject);
		$oDialog->setElement($oDialog->createRow($this->t('Elternelement'), 'select', array(
			'name' => $sParentQuestionChildId,
			'id' => $sParentQuestionChildId,
			'db_column' => 'parent_id',
			'selection' => new Ext_TC_Marketing_Feedback_Questionary_Child_Gui2_Selection_Parents(),
		)));

//		// Skalen
//		$oDiv = $oDialog->create('div');
//		$oDiv->id = 'rating_data';
//
//		if(
//			$oObject &&
//			count($oObject->getGroupQuestions()) > 0
//		) {
//			$aRows = $this->_createRatingLines($oObject, $oDialog, $aSelected);
//			foreach($aRows as $oRow) {
//				$oDiv->setElement($oRow);
//			}
//		}
//
//		$oDialog->setElement($oDiv);
	}

    /**
     * Erstellt für ein Feld die ID
     * @param $sField
     * @param WDBasic $oObject
     * @param string $sAdditional
     * @return string
     */
    protected function _createFieldId($sField, WDBasic $oObject, $sAdditional = '') {

		$sId = 'save[question]['.$oObject->id.']['.$sField.']'.$sAdditional;		
		return $sId;
	}

    /**
     * Generiert die Zeilen mit den Bewertungen
     *
     * @param Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group $oQuestionGroup
     * @param Ext_Gui2_Dialog $oDialog
     * @param array $aQuestions
     *
     * @return array
     */
//	protected function _createRatingLines(Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group $oQuestionGroup, Ext_Gui2_Dialog &$oDialog, $aQuestions) {
//
//		$aRows = array();
//
//		$aGroupQuestions = $oQuestionGroup->getGroupQuestions();
//
//		foreach($aQuestions as $iQuestion) {
//
//			$oGroupQuestion = null;
//			// Wenn die Bewertung für die Frage schon einmal gespeichert wurde
//			foreach($aGroupQuestions as $oTempGroupQuestion) {
//				if($oTempGroupQuestion->question_id == $iQuestion) {
//					$oGroupQuestion = $oTempGroupQuestion;
//					break;
//				}
//			}
//
//			$iDefault = 0;
//			if($oGroupQuestion) {
//				$oQuestion = $oGroupQuestion->getQuestion();
//				$iDefault = $oGroupQuestion->rating_id;
//			} else {
//				$oQuestion = Ext_TC_Marketing_Feedback_Question::getInstance($iQuestion);
//			}
//
//			if($oQuestion->question_type == 'rating') {
//
//				$oH3 = $oDialog->create('h4');
//				$oH3->setElement($oQuestion->getQuestion());
//				$aRows[] = $oH3;
//
//				$aRows[] = $oDialog->createRow($this->t('Skala'), 'select', array(
//					'name' => $this->_createFieldId('question_rating', $oQuestionGroup, '['.$oQuestion->id.']'),
//					'select_options' => Ext_TC_Marketing_Feedback_Rating::getSelectOptions(true),
//					'default_value' => $iDefault,
//					'required' => true
//				));
//
//			}
//
//		}
//
//		return $aRows;
//	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param boolean $bSave
	 * @param string $sAction
	 * @param boolean $bPrepareOpenDialog
     *
	 * @return array
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

        // Bei neuen Objekten, muss der Type gesetzt werden
        if(
            $this->oWDBasic->id == 0 &&
            is_array($sAction) &&
            isset($sAction['additional'])
        ) {
            $this->oWDBasic->type = $sAction['additional'];
        }

        // Speichern von Fragen wird komplett individuell behandelt
		if($this->oWDBasic->type == 'question') {

			// Individuelles Speichern der Daten
            $aIndividualData = $this->_saveQuestionDialog($aSaveData, $bSave);
            // Normales Speichern aufrufen, damit der Dialog korrekt neu aufgerufen wird nach dem Speichern
            $aData = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

            // Füllen des return arrays
            if(!empty($aIndividualData['error'])) {
                $aData['error'] = $aIndividualData['error'];
                if(empty($aSelectedIds)) {
                    $aData['data']['id'] = 'ID_0';
                }
            }
            if($aIndividualData['show_skip_errors_checkbox']) {
                $aData['data']['show_skip_errors_checkbox'] = 1;
            }

		} else {
			$aData = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
		}

		return $aData;
	}

	/**
	 * Speichert den Dialog für die Fragen
     *
	 * @param array $aSaveData
	 * @param boolean $bSave
     *
	 * @return array
	 */
	protected function _saveQuestionDialog($aSaveData, $bSave) {

        global $_VARS;

		$aData = array();
		
		if(!is_array($aSaveData)) {
			$aSaveData = (array)$aSaveData;
		}

		$oRequest = new MVC_Request();
		$oRequest->add($aSaveData);
        $oRequest->add($_VARS);

		$oHelper = new Ext_TC_Marketing_Feedback_Questionary_Child_Question_Helper_Save($this->oWDBasic, $oRequest, $this);
		$oHelper->bSave = $bSave;

		$bSuccess	= $oHelper->save();
		$aErrors = $oHelper->getError();

		if(
			!$bSuccess &&
			empty($aErrors)
		) {
			$aErrors[] = $this->t('Beim Speichern ist ein Fehler aufgetreten!');
		}

        if($oHelper->bShowSkipCheckBox) {
            $aData['show_skip_errors_checkbox'] = 1;
        }
		$aData['error']	= $aErrors;

		return $aData;
	}

    /**
     * Handles the ajax request
     *
     * @param array $_VARS
     *
     * @return void
     */
	public function switchAjaxRequest($_VARS) {

        if(
            (
                $_VARS['task'] == 'openDialog' ||
                $_VARS['task'] == 'saveDialog'
            ) &&
            (
                $_VARS['action'] == 'new' ||
                $_VARS['action'] == 'edit'
            )
        ) {

            if(!$this->oWDBasic) {
                $this->_getWDBasicObject($_VARS['id']);
            }

            // Bei vorhandenen Childs, additional setzen
            if(
                $this->oWDBasic instanceof Ext_TC_Marketing_Feedback_Questionary_Child &&
                $this->oWDBasic->id > 0
            ) {
                $_VARS['additional'] = $this->oWDBasic->type;
            }

            $aActions = array('new', 'edit');
            foreach($aActions as $sAction) {
                // Falls Dialog-Objekt noch nicht im Objekt, Dialog-Objekte aufbauen
                if(!isset($this->aIconData[$sAction.'_'.$_VARS['additional']])) {
                    if($_VARS['additional'] == 'heading') {
                        $oDialog = self::getHeadingDialog($this->_oGui);
                        $this->aIconData[$sAction.'_'.$_VARS['additional']]['dialog_data'] = $oDialog;
                    } else {
                        $oDialog = self::getQuestionDialog($this->_oGui);
                        $this->aIconData[$sAction.'_'.$_VARS['additional']]['dialog_data'] = $oDialog;
                    }
                }
            }

        }

		if(
			$_VARS['task'] == 'saveDialog' ||
			$_VARS['task'] == 'createCopy'
		) {

			$_VARS['options_serialized'] = serialize(array('cloneable' => true));

			parent::switchAjaxRequest($_VARS);

			$oQuestionary = Ext_TC_Marketing_Feedback_Questionary::getInstance($_VARS['parent_gui_id'][0]);
			$aQuestionaryChilds = $oQuestionary->getChilds();

			$this->saveQuestionarySort($aQuestionaryChilds);

		}
		else if($_VARS['task'] == 'saveSort') {

			// Initiales sortieren der Items
			$this->_saveNewSort($_VARS['sortablebody_' . $this->_oGui->hash]);

			$oQuestionary = Ext_TC_Marketing_Feedback_Questionary::getInstance($_VARS['parent_gui_id'][0]);
			$aQuestionaryChilds = $oQuestionary->getChilds();

			$iParentId = 0;
			$iPosition = 1;
			$bParentChanged = false;
			// Abändern der ParentId und der Position für den
			// zu sortierenden Datensatz
			foreach($aQuestionaryChilds as $oQuestionaryChild) {
				if(
					$oQuestionaryChild->id == $_VARS['selected_id'] &&
					$oQuestionaryChild->id != $iParentId
				) {
					if(
						$oQuestionaryChild->parent_id == $iParentId &&
						$oQuestionaryChild->parent_id > 0
					) {
						$oQuestionaryChild->position = $iPosition;
					}
					$oQuestionaryChild->parent_id = $iParentId;
					$oQuestionaryChild->save();
					$bParentChanged = true;
				}
				$iParentId = $oQuestionaryChild->parent_id;
				++$iPosition;
			}
			// Man muss nur alle Childs neusortieren wenn
			// sich die ParentId verändert hat
			if($bParentChanged) {
				$this->saveQuestionarySort($aQuestionaryChilds);
			}

			$aTransfer['action'] = 'loadTable';

			echo json_encode($aTransfer);

		}
		else if($_VARS['action'] == 'loadTopicQuestions') {

			$aTransfer = array();
			$aTransfer['action'] = 'loadTopicQuestionsCallback';
			
			$iTopic = (int) $_VARS['topic_id'];
			
			$sTopicSelect = (string) $_VARS['select_id'];
			
			// Fragen des Themas holen
			$aReturn = array();
			if($iTopic > 0) {
				$oTopic = Ext_TC_Marketing_Feedback_Topic::getInstance($iTopic);
				$aQuestions = $oTopic->getAllocatedQuestions();
				foreach($aQuestions as $oQuestion) {
					$aReturn[] = array('value' => $oQuestion->id, 'text' => $oQuestion->getQuestion());
				}
			}
			
			$sDialog = 'ID_0';
			if(!empty($_VARS['id'])) {
				$sDialog = 'ID_'.implode('_', $_VARS['id']);
			}

			$aTransfer['data']['questions'] = $aReturn;
			$aTransfer['data']['id'] = $sDialog;
			$aTransfer['data']['select_id'] = $sTopicSelect;
			
			echo json_encode($aTransfer);

		}
//		else if($_VARS['action'] == 'loadRatingData') {
//
//			$iChild = 0;
//
//			$sDialog = 'ID_0';
//			if(!empty($_VARS['id'])) {
//				$sDialog = 'ID_'.implode('_', $_VARS['id']);
//				$iChild = (int) reset($_VARS['id']);
//			}
//
//			$oChild = Ext_TC_Marketing_Feedback_Questionary_Child::getInstance($iChild);
//
//			$sRows = '';
//			if(isset($_VARS['questions'])) {
//				$aQuestions = explode(',', $_VARS['questions']);
//                $oChildQuestion = $oChild->getObject('question');
//
//				$oDummyDialog = new Ext_Gui2_Dialog();
//
//				$aRows = $this->_createRatingLines($oChildQuestion, $oDummyDialog, $aQuestions);
//
//				foreach($aRows as $oRow) {
//					$sRows .= $oRow->generateHTML();
//				}
//			}
//
//			$aTransfer['action'] = 'loadRatingDataCallback';
//			$aTransfer['data']['rating_rows'] = $sRows;
//			$aTransfer['data']['id'] = $sDialog;
//
//			echo json_encode($aTransfer);
//
//		}
		else {
			parent::switchAjaxRequest($_VARS);
		}	
		
	}

	/**
	 * @param $sL10NDescription
	 * @return array
	 */
	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);
		$aData['delete_question'] = L10N::t('Wollen Sie den Eintrag wirklich löschen? Alle untergeordneten Elemente gehen ebenfalls verloren!',	Ext_Gui2::$sAllGuiListL10N);

		return $aData;
	}

	/**
	 * Komplettes neusortieren der Datensätze
	 * von Ebene 0 aus
	 *
	 * @param Ext_TC_Marketing_Feedback_Questionary_Child[] $aQuestionaryChilds
	 */
	private function saveQuestionarySort($aQuestionaryChilds) {

		$aPosition = array();
		foreach($aQuestionaryChilds as $oQuestionaryChild) {
			if($oQuestionaryChild->parent_id == 0) {
				$aPosition[] = $oQuestionaryChild->id;
				$aChilds = $oQuestionaryChild->getChilds();
				foreach($aChilds as $oChild) {
					$aPosition[] = $oChild->id;
				}
			}
		}
		$this->_saveNewSort($aPosition);

	}

}