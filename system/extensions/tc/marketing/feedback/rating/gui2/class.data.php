<?php

class Ext_TC_Marketing_Feedback_Rating_Gui2_Data extends Ext_TC_Gui2_Data {
	
	/**
	 * Dialog um Bewertungen anzulegen
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2_Dialog 
	 */
	public static function getDialog($oGui)
	{
		$oDialog = $oGui->createDialog($oGui->t('Skala "{name}" editieren'), $oGui->t('Skala anlegen'));
		$oDialog->save_as_new_button = true;

		return $oDialog;
	}

	/**
	 * Edit dialog
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 */
	protected function getEditDialogHTML(&$oDialog, $aSelectedIds, $sAdditional = false) {
		
		$oWDBasic = $this->oWDBasic;
		if(!$oWDBasic) {
			$oWDBasic = $this->_getWDBasicObject($aSelectedIds);
		}
		
		$oDialog->aElements = array();
		
		$oDialog->setElement($oDialog->createRow($this->t('Name'), 'input', array(
			'db_alias' => 'tc_fr',
			'db_column' => 'name',
			'required' => true
		)));
		
		$oDialog->setElement($oDialog->createRow($this->t('Anzahl d. Skalen'), 'input', array(
			'db_alias' => 'tc_fr',
			'db_column' => 'number_of_ratings',
			'style' => 'width: 30px;',
			'class' => 'number_of_ratings',
			'required' => true,
			'events' => array(
				array(
					'event' 	=> 'keyup',
					'function' 	=> 'loadRatingDataFields',
					'parameter'	=> 'aDialogData.id, 0'
				)
			)
		)));

		$oDialog->setElement($oDialog->createRow($this->t('Typ'), 'select', array(
			'db_alias' => 'tc_fr',
			'db_column' => 'type',
			'select_options' => array('asc' => $this->t('aufsteigend'), 'desc' => $this->t('absteigend')),
			'required' => true,
			'events' => array(
				array(
					'event' 	=> 'change',
					'function' 	=> 'loadRatingDataFields',
					'parameter'	=> 'aDialogData.id, 0'
				)
			)
		)));
		
		$aFields = $this->_getRatingDataFields($oWDBasic->number_of_ratings, $oDialog, $aSelectedIds);
		foreach($aFields as $oElement) {
			$oDialog->setElement($oElement);
		}
		
		$aData = parent::getEditDialogHTML($oDialog, $aSelectedIds, $sAdditional);
		
		return $aData;
	}

	/**
	 * Liefert alle Bewertungs-Elemente (h3, Rows, ...)
	 *
	 * @param $iNumberOfRatings
	 * @param $oDialog
	 * @param $aSelectedIds
	 * @return array
	 */
	protected function _getRatingDataFields($iNumberOfRatings, $oDialog, $aSelectedIds) {		
				
		$aElements = array();

		/** @var $oWDBasic Ext_TC_Marketing_Feedback_Rating */
		$oWDBasic = $this->oWDBasic;

		if($iNumberOfRatings > 0) {
			
			// Limit
			if($iNumberOfRatings > 50) {
				$iNumberOfRatings = 50;
			}
						
			$oDiv = new Ext_Gui2_Html_Div();
			$oDiv->id = 'rating_fields_'.$this->_oGui->hash;

			$aChilds = $oWDBasic->getChildElements();
			$iChilds = count($aChilds);
			
			$iCount = 1;
					
			// Überschrift
			$oH3 = new Ext_Gui2_Html_H4();
			$oH3->setElement($this->t('Skalen'));
			$aElements[] = $oH3;
			
			// Bereits vorhanden Childs hinzufügen
			foreach($aChilds as $oChild) {
				if($iCount <= $iNumberOfRatings) {
					$oReadLine = $this->_createRatingLine($iCount, $oChild->id, $aSelectedIds, $oChild);
					$aElements[] = $oReadLine;
				}
				++$iCount;
			}
			
			// neue Elemente haben eine negative ID
			$iNegativeId = -1;
			
			$iDiff = $iNumberOfRatings - $iChilds;
			
			// Neue Bewertungsfelder
			for($i = 0; $i < $iDiff; ++$i) {
				$oReadLine = $this->_createRatingLine($iCount, $iNegativeId, $aSelectedIds);
				$aElements[] = $oReadLine;
				
				--$iNegativeId;
				++$iCount;
			}			
						
		}	
		
		return $aElements;
	}

	/**
	 * Generiert eine Bewertungs-Reihe
	 *
	 * @param $iCount
	 * @param $iId
	 * @param $aSelectedIds
	 * @param null $oChild
	 * @return Ext_Gui2_Html_Div
	 */
	protected function _createRatingLine($iCount, $iId, $aSelectedIds, $oChild = null) {
		
		$aElements = array();

		$aLanguages = (array)Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');
		
		$aI18NData = array();
		if($oChild) {
			// Übersetzungssprachen umkonvertieren
			$aTempI18NData = $oChild->childs_tc_i18n;
			foreach($aTempI18NData as $aData) {
				$aI18NData[$aData['language_iso']] = $aData['description'];
			}
		}

		// Beschreibung
		foreach($aLanguages as $aIso) {
			
			$sIso = $aIso['iso'];

			$oI18NInput = new Ext_Gui2_Html_Input();
			$oI18NInput->class = 'i18nInput form-control ratingDescription';
			$oI18NInput->id = $this->_createChildRatingId('description', $iId, $aSelectedIds, false, $sIso);
			$oI18NInput->name = $this->_createChildRatingId('description', $iId, $aSelectedIds, true, $sIso);
			$oI18NInput->title = $aIso['name'];

			// Wert setzen
			if(isset($aI18NData[$sIso])) {
				$oI18NInput->value = $aI18NData[$sIso];
			}

			$oLanguageContainerDiv = new Ext_Gui2_Html_Div();
			$oLanguageContainerDiv->id = 'i18n_container_name_languages_'.$sIso;
			$oLanguageContainerDiv->class = 'input-group';
			
			$oFlagDiv = new Ext_Gui2_Html_Span();
			$oFlagDiv->class = 'i18nFlag input-group-addon';
			
			$sFlagIcon = Util::getFlagIcon($sIso);

			$oFlagDiv->setElement('<img src="'.$sFlagIcon.'" alt="'.$sIso.'" title="'.$sIso.'" />');

			$oLanguageContainerDiv->setElement($oFlagDiv);
			$oLanguageContainerDiv->setElement($oI18NInput);
			
			$aElements['i18n'][] = $oLanguageContainerDiv;

		}

		// Berechnet das Rating anhand der
		// Sortierung
		$iRatingValue = $iCount;
		if($this->oWDBasic->type === 'desc') {
			$iRatingValue = ((int)$this->oWDBasic->number_of_ratings - (int)$iCount) + 1;
		}

		// Wert
		$oRatingContainerDiv = new Ext_Gui2_Html_Div();
		$oRatingContainerDiv->class = 'input-group';

		$oRating = new Ext_Gui2_Html_Input();
		$oRating->class	= 'form-control ratingValue';
		$oRating->id	= $this->_createChildRatingId('rating', $iId, $aSelectedIds);
		$oRating->name	= $this->_createChildRatingId('rating', $iId, $aSelectedIds, true);
		$oRating->title = 'Wert';
		$oRating->bReadOnly = true;
		$oRating->value = $iRatingValue;

		$oRatingLabel = new Ext_Gui2_Html_Span();
		$oRatingLabel->class = 'input-group-addon';
		$oRatingLabel->setElement($this->t('Wert'));

		$oRatingContainerDiv->setElement($oRatingLabel);
		$oRatingContainerDiv->setElement($oRating);
		
		$aElements['rating'][] = $oRatingContainerDiv;
		
		$sLabel = $this->t('Skala').' '.$iCount;
		
		$oRatingLine = $this->_createRow($sLabel, $aElements);
		
		return $oRatingLine;
	}

	/**
	 * Generiert die ID für ein Bewertungsfeld
	 *
	 * @param $sColumn
	 * @param $iId
	 * @param $aSelectedIds
	 * @param bool $bForSave
	 * @param string $sIso
	 * @return string
	 */
	protected function _createChildRatingId($sColumn, $iId, $aSelectedIds, $bForSave = false, $sIso = '') {
		$sId = '[child]['.$iId.']['.$sColumn.']';
		
		if(!empty($sIso)) {
			$sId .= '['.$sIso.']';
		}
		
		$sDialog = 'ID_'.implode('_', $aSelectedIds);
		if(empty($aSelectedIds)) {
			$sDialog = 'ID_0';
		}
		
		if(!$bForSave) {
			$sId = '['.$this->_oGui->hash.']['.$sDialog.']' . $sId;
		}
		
		return 'save' . $sId;
	}
	
	/**
	 * Generiert ein Dialog Row mit den übergebenen Feldern
	 *
	 * @param string $sLabel
	 * @param array $aElements
	 * @return \Ext_Gui2_Html_Div
	 */
	protected function _createRow($sLabel, $aElements) {
		
		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = 'GUIDialogRow GUIDialogMultiRow';
		
		$oLabel = new Ext_Gui2_Html_Div();
		$oLabel->class = 'GUIDialogRowLabelDiv';

		$oLabel->setElement($sLabel);

		$oDiv->setElement($oLabel);		
		
		$aCols = [
			'i18n' => 9,
			'rating' => 3
		];

		$oInnerDiv = new Ext_Gui2_Html_Div();
		$oInnerDiv->class = 'GUIDialogRowInputDiv';

		$oGridRow = new Ext_Gui2_Html_Div();
		$oGridRow->class = 'grid-row';

		foreach($aElements as $sKey=>$aInnerElements) {
			$oCol = new Ext_Gui2_Html_Div();
			$oCol->class = 'col-md-'.$aCols[$sKey];

			if ($sKey == 'i18n') {
				$oCol->class .= ' i18n-fields';
			}

			foreach($aInnerElements as $oInnerElement) {
				$oCol->setElement($oInnerElement);
			}
			$oGridRow->setElement($oCol);
		}

		$oInnerDiv->setElement($oGridRow);
		$oDiv->setElement($oInnerDiv);


		return $oDiv;
	}	
	
	/**
	 * Speichert den Editier-Dialog
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param boolean $bSave
	 * @param string $sAction
	 * @param boolean $bPrepareOpenDialog
	 * @return array
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		if($bSave === false) {
			return $aTransfer;
		}

		global $_VARS;

		$bSaveAsNewEntry = false;
		if(isset($_VARS['save_as_new_from'])) {
			$bSaveAsNewEntry = true;
		}

		/** @var $oWDBasic Ext_TC_Marketing_Feedback_Rating */
		$oWDBasic = $this->oWDBasic;
		if(!$oWDBasic) {
			$oWDBasic = $this->_getWDBasicObject($aSelectedIds);
		}

		$aError = array();

		// Speichern
		DB::begin('save_rating_dialog_data');
		try {

			// Name
			if(isset($aSaveData['name']['tc_fr'])) {
				$oWDBasic->name = (string)$aSaveData['name']['tc_fr'];
			}

			// Anzahl d. Bewertungen
			$iNumberOfRatings = 0;
			if(isset($aSaveData['number_of_ratings']['tc_fr'])) {
				$iNumberOfRatings = (int)$aSaveData['number_of_ratings']['tc_fr'];
				$oWDBasic->number_of_ratings = $aSaveData['number_of_ratings']['tc_fr'];
			}

			// Wenn Bewertungen angegeben wurden
			if($iNumberOfRatings > 0) {

				$aSavedChilds = $oWDBasic->getChildElements();

				if(isset($aSaveData['child'])) {

					$iCount = 1;
					foreach((array)$aSaveData['child'] as $iKey => $aData) {

						/*
						 * Wenn mehr Einträge übermittelt wurden als insgesamt vorhanden sein sollen die
						 * überschüssigen nicht beachten (z.B. wenn die Anzahl verringert wird)
						 */
						if($iCount > $iNumberOfRatings) {
							continue;
						}

						if(
							$iKey > 0 && 
							$bSaveAsNewEntry == false
						) {
							// Bereits vorhandene Bewertung
							$oChild = $oWDBasic->getJoinedObjectChild('childs', $iKey);
							unset($aSavedChilds[$iKey]);
						} else {
							// Neue Bewertung
							$oChild = $oWDBasic->getJoinedObjectChild('childs', 0);
						}

						$aI18NData = array();						
						foreach((array) $aData['description'] as $sIso => $sDescription) {
							$aI18NData[] = array(
								'language_iso' => (string)$sIso,
								'description' => (string)$sDescription
							);
						}

						$oChild->childs_tc_i18n = $aI18NData;

						// Berechnet das Rating anhand der Sortierung
						$iRatingValue = $iCount;
						if($this->oWDBasic->type === 'desc') {
							$iRatingValue = ((int)$this->oWDBasic->number_of_ratings - (int)$iCount) + 1;
						}
						$oChild->rating	= $iRatingValue;

						// Child-Validierung
						$mChildValidate = $oChild->validate();
						if(is_array($mChildValidate)) {

							// Erste Fehlermeldung?
							if(empty($aError[0])) {
								$aError[0] = $this->t('Es ist ein Fehler aufgetreten.');
							}

							$aChildErrors	= $this->_getErrorData($mChildValidate, 'edit', 'error');
							foreach((array)$aChildErrors as $iErrorKey => $aChildError) {
								if(is_array($aChildError)) {
									$aChildErrors[$iErrorKey]['error_id'] = '[child]['.$iKey.'][rating]';
								}
							}

							$aError[] = $aChildErrors[1];

						}

						$iCount++;

					}

					// Falls es Bewertungen gibt, die nicht mehr gebraucht werden, diese löschen
					foreach(array_keys($aSavedChilds) as $iChildId) {
						$oWDBasic->removeJoinedObjectChildByKey('childs', $iChildId);
					}

				}

			} else {

				// Wenn Anzahl d. Bewertungen "0" ist, alle alten Bewertungen löschen
				$oWDBasic->cleanJoinedObjectChilds('childs');

			}

			if( 
				$bSave === true &&
				empty($aError)
			) {
				$mValidate = $oWDBasic->validate();
				if($mValidate === true) {
					$oWDBasic->save();
					DB::commit('save_rating_dialog_data');
				} else {
					$aError	= $this->_getErrorData($mValidate, 'edit', 'error');
					DB::rollback('save_rating_dialog_data');
				}
			}

		} catch(Exception $e) {

			__pout($e);
			$aError[0] = $this->t('Es ist ein Fehler aufgetreten.');
			$aError[1] = $this->t('Beim Speichern des Angebotes ist ein Fehler aufgetreten!');
			DB::rollback('save_rating_dialog_data');

		}

		// Mögliche Fehlermeldungen an den Dialog übergeben
		$aTransfer['error'] = $aError;

		// Es können sich noch Daten geändert haben, deswegen Dialog neu aufbauen
		$sAdditional = '';
		if(is_array($sAction)) {
			$sAdditional = $sAction['additional'];
			$sAction = $sAction['action'];
		}
		$aTransfer['data'] = $this->prepareOpenDialog($sAction, $aSelectedIds, false, $sAdditional);

		return $aTransfer;

	}

}
