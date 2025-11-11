<?php

class Ext_TC_Gui2_Data extends Ext_Gui2_Data
{
	use \Communication\Traits\Gui2\WithCommunication;

	/**
	 * Section der Tabelle für die Flex2 benötigt
	 *
	 * @var null|string
	 */
	public $sSection = NULL;

	/**
	 * @var bool
	 */
	protected $bWriteFlexFields = true;

	/**
	 * @var array
	 */
	protected $_aDesignLoopObjects = array();

	/**
	 * @var bool
	 */
	protected $_bFormatFlexValues = true;

	/**
	 * Erzeugt HTML und Tabs für den "edit" Dialog
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		$this->aFlexFields = array();
		
		//Prüfen ob Design aktiv
		$bDesign = $this->_oGui->getOption('design');
		// wenn ja
		if($bDesign){

			// Dialog neu bauen 
			$oDialogData = $this->_oGui->getDesignDialog($aSelectedIds);
			
			// Zuweisen zur icon data damit sie auch beim speichern 100% korrekt vorhaden sind
			if(!empty($aSelectedIds)){
				$this->aIconData['edit']['dialog_data'] = $oDialogData;
			} else {
				$this->aIconData['new']['dialog_data'] = $oDialogData;
			}
			
		}

		// Daten generieren (über parent, damit Dialog Data benutzt wird)
		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

		return $aData;
	}
	

	
	public function getTranslations($sL10NDescription){

		$aData = parent::getTranslations($sL10NDescription);

		$aData['wrong_parent'] = $this->t('Das Element kann nicht in den gewünschten Bereich eingefügt werden.', 'Thebing » Core » Designer');
		
		return $aData;

	}
	
	/**
	 * Build Query Parts
	 * Insert all JOIN TABLE fpr all used DESIGN FILTER
	 * @param string $sSql
	 * @param array $aSql
	 * @param array $aSqlParts
	 * @param integer $iLimit 
	 */
	protected function _buildQueryParts(&$sSql, &$aSql, &$aSqlParts, &$iLimit) {
		
		$bDesign = $this->_oGui->getOption('design');
		
		// Wenn WDBasic Ableitung gesetzt wurde
		if(
			$this->_oGui->class_wdbasic != 'WDBasic' &&
			$bDesign === true
		) {

			if(
				is_null($this->oWDBasic) ||
				!($this->oWDBasic instanceof WDBasic)
			) {
				$this->_getWDBasicObject(array(0));
			}
			
			// Get all DESIGN Filter Elements from current GUI
			$aOptions		= $this->_oGui->getOption('design_filter');
			
			// Set for each Filter, his join table
			foreach((array)$aOptions as $aOption){
				$aJoinData =  array(
					$aOption['alias'] =>array(
								'table'=>'tc_gui2_designs_tabs_elements_values',
								'primary_key_field'=>'entry_id',
								'autoload'=>true,
								'check_active'=>false,
								'delete_check'=>false,
								'join_operator' => 'LEFT OUTER JOIN',
								'static_key_fields'=> array('element_id' => (int)$aOption['id'])
							)
				  );
				// add dynamic join table
				$this->oWDBasic->addJoinTable($aJoinData);
			}
			
			
		}
		// see parent
		parent::_buildQueryParts($sSql, $aSql, $aSqlParts, $iLimit);
	}
	
	/**
	 * get the Data for the Dialog
	 * kick all DESIGN_ELEMENTS from save data and getting this elements seperate
	 * @param array $aSelectedIds
	 * @param array $aSaveData
     * @param mixed $sAdditional
	 * @return array
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		$aDesignElements = array();
		// Search for DESIGN ELEMENTS
		foreach((array)$aSaveData as $iKey => $aHookData){
			// Kick if found
			if($aHookData['db_column'] == 'DESIGN_ELEMENT'){
				$aDesignElements[] = $aHookData;
				unset($aSaveData[$iKey]);
			} else if(
				empty($aHookData['db_column']) && 
				empty($aHookData['db_alias'])
			){
				unset($aSaveData[$iKey]);
			}
		}

		// see parent
		$aFinalData = parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);

		// get all Values of the DESIGN ELEMENTS
		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = reset($aSelectedIds);

		foreach((array)$aDesignElements as $aHookData){
			
			$iId = (int)$aHookData['db_alias'];
			
			$oDesignElement				= Ext_TC_Gui2_Design_Tab_Element::getInstance($iId);
			$sValue						= $oDesignElement->getValue($iSelectedId);
			$aTemp						= $aHookData;
			$aTemp['value']				= $sValue;
			$aFinalData[] = $aTemp;						
		}

		// Loop Felder holen
		$aSpecialLoopData = $this->_oGui->getOption('special_save_data');

		// Loop Felder durchgehen
		foreach((array)$aSpecialLoopData as $sClass => $aLoops){
			// Loops durchlaufen
			foreach((array)$aLoops as $iLoop => $aFields){
				
				// -1 überspringen da dies der koppierbare ist
				if($iLoop == -1){
					continue;
				}
				
				$iObjectId = $iLoop;
				
				if($iObjectId <= 0){
					$iObjectId = 0; 
				}
				
				$oObject = Ext_TC_Factory::getInstance($sClass, $iObjectId);
			
				foreach((array)$aFields as $sElementHash => $aSaveData) {
					
					$sColumn					= $aSaveData['db_column'];
					$sAlias						= $aSaveData['db_alias'];
					$sAliasOriginal				= $aSaveData['db_alias_original'];
					
					if($sColumn != 'DESIGN_ELEMENT'){
						
						$sValue						= $oObject->$sColumn;
						
					} else {

						$oDependence = $aSaveData['dependence'];
	
						if($oDependence instanceof Ext_TC_Gui2_Designer_Config_Element_Dependence){
							$sLoopClass = $oDependence->getOwnClass();
						}
						
						$oDesignElement				= Ext_TC_Gui2_Design_Tab_Element::getInstance($sAliasOriginal);
						$sValue						= $oDesignElement->getValue($iSelectedId, $iLoop, $sLoopClass);
						
					}
					
					$sOriginalValue = $sValue;
					
					// Wenn eine Format Klasse angegeben ist
					if($aSaveData['format']){
						if(is_object($aSaveData['format'])){
							$oFormat = $aSaveData['format'];
						} else {
							$oFormat = new $aSaveData['format']();
						}
						$sValue = $oFormat->formatByValue($sValue);
					}

					$aTemp						= array();
					$aTemp['db_column']			= $aSaveData['db_column'];
					$aTemp['db_alias']			= $aSaveData['db_alias'];
					$aTemp['value']				= $sValue;

//					if($aSaveData['display_age']) {
//						$iCheckDateValue = Ext_Gui2_Util::checkDateValue($sOriginalValue);
//						// Wenn es ein Kalendar ist dann den Wochentag setzen
//						if($iCheckDateValue > 0) {
//							$aTemp['week_day'] = Ext_Gui2_Util::getWeekDay($iCheckDateValue, $sOriginalValue);
//							$iAge = Ext_Gui2_Util::getAge($iCheckDateValue, $sOriginalValue);
//							if($iAge > 0) { 
//								$aTemp['age'] = $this->_oGui->t('Alter').': '.$iAge;
//							}
//						}
//					}

					$aFinalData[] = $aTemp;
				}
				
			}
			
		}

		// Das hier funktioniert nur für den GUI-Designer da nur hier $this->aFlexFields befüllt wird über Ext_TC_Flexibility::getDialogRow().
		// In allen anderen Dialogen werden die Flex-Felder mit Value erst später gesetzt
		foreach((array)$this->aFlexFields as $aFlexField) {

			if($aFlexField['parent_id'] > 0) {
				continue;
			}

			// Prüfen, ob es sich um ein Feld handelt, das zu einem Unterobjekt zugeordnet werden muss
			$oFlexField = Ext_TC_Flexibility::getInstance($aFlexField['field_id']);
			$oSection = $oFlexField->getSection();

			$oWDBasic = $this->oWDBasic;
			// TODO: Was hat der TA-Kram hier zu suchen?
			if(
				$oSection->type == 'gui_designer' &&
				(
					$oFlexField->usage == 'student' ||
					$oFlexField->usage == 'student_inquiry'
				)
			) {

				$aFlexField['id'] = (int)$aFlexField['id'];
				
				// Gibt es schon ein Objekt
				if($aFlexField['id'] > 0) {

					if($oFlexField->usage == 'student') {

						$oWDBasic = Ext_TC_Contact::getInstance($aFlexField['id']);

					} elseif ($oFlexField->usage == 'student_inquiry') {

						preg_match("/flex_childs\[(.*)\]\[[0-9]+\]\[[0-9]+\]/", $aFlexField['name'], $aMatch);
						$sClassName = $aMatch[1];
						$sType = mb_strtolower(mb_substr($sClassName, mb_strrpos($sClassName, '_')+1));
						// Search InquiryContact

						if ($sType === 'additionalcontact') {
							$aCriteria = ['additional_contact' => 1];
						} else {
							$aCriteria = ['type' => $sType];
						}

						if(mb_strpos($sClassName, 'TA_Enquiry') !== false) {
							$oRepo = Ext_TA_Enquiry_EnquiryContact::getRepository();
							$oInquiryToContact = $oRepo->findOneBy(array_merge(['enquiry_id'=>$iSelectedId, 'contact_id'=>$aFlexField['id']], $aCriteria));
						} else {
							$oRepo = Ext_TA_Inquiry_InquiryContact::getRepository();
							$oInquiryToContact = $oRepo->findOneBy(array_merge(['inquiry_id'=>$iSelectedId, 'contact_id'=>$aFlexField['id']], $aCriteria));
						}

						$oWDBasic = $oInquiryToContact;
					}
				}
			}

			$bIgnoreVisible = ($oSection->type == 'gui_designer') ? true : false;

			$aSavedFlexValues = $this->getFlexEditData($oWDBasic, $oSection->type, $bIgnoreVisible);
			$mValue = $aSavedFlexValues[$oFlexField->getId()] ?? null;

			$aHookData = [
				'entity_id' => (!is_null($oWDBasic)) ? $oWDBasic->getId() : 0,
				'field_id' => $oFlexField->getId(),
				'value' => $mValue
			];

			System::wd()->executeHook('tc_edit_dialog_data_flexvalue', $aHookData);
			
			$mValue = $aHookData['value'];

			// Wiederholbare Flexfelder auf JoinedObject-Container aufteilen
			if($oFlexField->isRepeatableContainer()) {

				$aCombined = [];
				foreach($mValue as $iContainerIndex => $aContainerValues) {
					foreach($aContainerValues as $iChildFieldId => $mChildValue) {
						$oChildFlexField = Ext_TC_Flexibility::getInstance($iChildFieldId);
						if($oChildFlexField->isI18N()) {
							foreach($mChildValue as $sIso => $sIsoValue) {
								$aCombined[$iChildFieldId][$sIso][] = $sIsoValue;
							}
						} else {
							$aCombined[$iChildFieldId][] = $mChildValue;
						}
					}
				}

				foreach($aCombined as $iChildFieldId => $aChildValues) {

					$oChildFlexField = Ext_TC_Flexibility::getInstance($iChildFieldId);

					if($oChildFlexField->isI18N()) {

						foreach($aChildValues as $sIso => $aIsoValues) {

							$aTemp = array();
							$aTemp['id'] = $aFlexField['name'];
							$aTemp['additional_id'] = '['.$aFlexField['id'].']['.$iChildFieldId.']['.$sIso.']';
							$aTemp['joined_object_key']	= 'flex-'.$oFlexField->id;
							$aTemp['joined_object_min']	= 1;
							$aTemp['joined_object_max']	= 10;
							$aTemp['value']	= [];

							foreach ($aIsoValues as $iContainerIndex => $mContainerValue) {
								$aTemp['value'][] = [
									'id' => ($iContainerIndex + 1),
									'key' => ($iContainerIndex + 1),
									'value' => $mContainerValue,
									'index' => $iContainerIndex
								];
							}

							$aFinalData[] = $aTemp;
						}
					} else {
						$aTemp = array();
						$aTemp['id'] = $aFlexField['name'];
						$aTemp['additional_id'] = '['.$aFlexField['id'].']['.$iChildFieldId.']';
						// Einstellungen für JoinedObject-Container
						$aTemp['joined_object_key']	= 'flex-'.$oFlexField->id;
						$aTemp['joined_object_min']	= 1;
						$aTemp['joined_object_max']	= 10;
						$aTemp['value']	= [];

						foreach ($aChildValues as $iContainerIndex => $mContainerValue) {
							$aTemp['value'][] = [
								'id' => ($iContainerIndex + 1),
								'key' => ($iContainerIndex + 1),
								'value' => $mContainerValue,
								'index' => $iContainerIndex
							];
						}

						$aFinalData[] = $aTemp;
					}

				}


			} else {
				if(
					$oFlexField->isI18N() &&
					is_array($mValue)
				) {
					foreach($mValue as $sIso => $mIsoValue) {
						$aTemp = array();
						$aTemp['id']	= $aFlexField['name'] . '['.$sIso.']';
						$aTemp['value']	= $mIsoValue;
						$aFinalData[] = $aTemp;
					}
				} else {
					$aTemp = array();
					$aTemp['id']	= $aFlexField['name'];
					$aTemp['value']	= $mValue;
					$aFinalData[] = $aTemp;
				}
			}

		}

		return $aFinalData;

	}

    /**
     * saving the Edit dialog
     * kick all DESIGN_ELEMENTS from save data and saving this elements seperate
	 *
     * @param array $aSelectedIds
     * @param array $aSaveData
     * @param boolean $bSave
     * @param array|string $sAction Now with additional: array('action' => action, 'additional' => null)
     * @param bool $bPrepareOpenDialog
     * @throws Exception
     * @return array
     */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {

        global $_VARS;

		$aSelectedIds = (array)$aSelectedIds;

		$aDesignElements = [];
		if (isset($aSaveData['DESIGN_ELEMENT'])) {
			$aDesignElements = $aSaveData['DESIGN_ELEMENT'];
			unset($aSaveData['DESIGN_ELEMENT']);
		}

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		$sAdditional = '';
		$sActionOriginal = $sAction;
		if(is_array($sAction)) {
			$sAdditional = $sAction['additional'];
			$sAction = $sAction['action'];
		}

		$sIconKey = self::getIconKey($sAction, $sAdditional);
		$oDialog = $this->_getDialog($sIconKey);

		if(
			$bSave &&
			empty($sAdditional) &&
			$this->_oGui->getOption('design')
		) {

			$iSelectedId = $aTransfer['save_id'];

			$oSelf = $this->_getWDBasicObject(array($iSelectedId));

			/**
			 * Flexible Felder speichern
			 * @todo Redundanter Aufruf
			 */
			if(isset($_VARS['flex'])) {
				$sItemType = '';
				if($oSelf instanceof Ext_TC_Basic) {
					$sItemType = $oSelf->getEntityFlexType();
				}
				$aInfo = $this->saveEditDialogDataFlex($oDialog, $iSelectedId, (array)$_VARS['flex'], $sItemType);
			}

			#########################
			## DESIGNER FUNKTIONEN ##
			#########################
				
			$aIdFound = array();

			// Loop Felder holen
			$aSpecialLoopData = $this->_oGui->getOption('special_save_data');

			$aGroupedSpecialData = array();
			$aDependenceForDesignElements = array();
			$aNoValueFieldData = array();
			// Daten aufbereiten damit wir ordentliche Arrays haben!
			foreach((array)$aSpecialLoopData as $sClass => $aLoops){
				$aFields = reset($aLoops);
				
				foreach((array)$aFields as $sElementHash => $aElementData){

					$sColumn	= $aElementData['db_column'];
					$sAlias		= $aElementData['db_alias_original'];

					// DESIGN ELEMENTE überspringen die werden unten gespeichert
					if($sColumn != 'DESIGN_ELEMENT'){

						// Schauen ob eine ID übermittelt wurde, dann muss auch gespeichert werden wenn keine feld daten mitgeschickt wurden!
						// (kundensuche)
						if(
							$aSaveData['id'][$sAlias] &&
							$aSaveData['id'][$sAlias] > 0
						) {
							$aIdFound[$sClass][$iEntry] = true;
						}

						$aCurrentSaveData = $aSaveData[$sColumn][$sAlias];
						$oDependence = $aElementData['dependence'];
						
						foreach((array)$aCurrentSaveData as $iEntry => $sValue){
							$aGroupedSpecialData[$sClass][$iEntry][$sColumn]['column'] = $sColumn;
							$aGroupedSpecialData[$sClass][$iEntry][$sColumn]['alias'] = $sAlias;
							$aGroupedSpecialData[$sClass][$iEntry][$sColumn]['value'] = $sValue;
							$aGroupedSpecialData[$sClass][$iEntry][$sColumn]['element'] = $sAlias;
							$aGroupedSpecialData[$sClass][$iEntry][$sColumn]['format'] = $aElementData['format'];
							$aGroupedSpecialData[$sClass][$iEntry][$sColumn]['dependence'] = $oDependence;
						}

						// Wenn keine Save Daten da sind -> zb. multiselect ( ohne auswahl )
						// feld merken da wir dies nacher leer setzten müssen
						if(empty ($aCurrentSaveData)){
							$aNoValueFieldData[$sClass][$sColumn]['value']		= null; // Null nätig wegen jointable werten
							$aNoValueFieldData[$sClass][$sColumn]['element']	= $sAlias;
							$aNoValueFieldData[$sClass][$sColumn]['format']		= $aElementData['format'];
							$aNoValueFieldData[$sClass][$sColumn]['dependence'] = $oDependence;
						}

						if($oDependence instanceof Ext_TC_Gui2_Designer_Config_Element_Dependence) {

							switch($oDependence->getDependenceType()) {

								case 'jointable':
									
									$sDependenceKey = $oDependence->getDependenceKey();
									
									// Einträge zurücksetzen
									$oSelf->$sDependenceKey = array();

									break;
								case 'child':

									if (!empty($aMethodCall = $oDependence->getDependenceMethodCall())) {
										$aChilds = call_user_func_array([$oSelf, $aMethodCall[0]], [$aMethodCall[1]]);
									} else {
										$sKey = $oDependence->getDependenceKey();
										$aChilds = $oSelf->getJoinedObjectChilds($sKey);
									}

									foreach((array)$aChilds as $oChild) {
										$oChild->active = 0;
										$oChild->save();
									}

									break;
							}
						}

					} else {
						$aDependenceForDesignElements[$sAlias] =  $aElementData['dependence'];
					}
				}
			}

			$aDependenceJoinTables = array();
			$aDesignMapping = array();
			$this->_aDesignLoopObjects = array();
			// Aufbereitetes Array durchgehen
			foreach((array)$aGroupedSpecialData as $sClass => $aClassData) {

				// Klassendaten durchgehen
				foreach((array)$aClassData as $iEntry => $aEntryData) {

					// -1 überspringen da dies der koppierbare ist
					if($iEntry == -1) {
						continue;
					}

					$iObjectId = $iEntry;

					if($iObjectId <= 0){
						$iObjectId = 0;
					}

					$bAllEmpty = true;

					// Wenn ID dann immer speichern daher flag ändern
					if($aIdFound[$sClass][$iEntry] === true){
						$bAllEmpty = false;
					}

					// Object erzeigen
					$oObject = call_user_func(array($sClass, 'getInstance'), $iObjectId);

					$oDependence = false;

					// Felder durchgehen und speichern
					foreach((array)$aEntryData as $sColumn => $aValueData){
						
						//Nur setzten wenn es übermittelt wurde
						if(
							$aValueData['value'] !== false && 
							$aValueData['value'] !== null
						){
					
							// Value holen
							$sValue = $aValueData['value'];

							// ggf. Formatieren
							if($aValueData['format']){
								if(is_object($aValueData['format'])){
									$oFormat = $aValueData['format'];
								} else {
									$oFormat = new $aValueData['format']();
								}
								$sValue = $oFormat->convert($sValue);
							}

							if(!empty($sValue)){
								$bAllEmpty = false;
							}

							foreach((array)$aTransfer['data']['values'] as $iKey => $aValues){

								if(
									$aValues['db_column'] == $sColumn &&
									$aValues['db_alias'] == $aValueData['element'].']['.$iEntry
								){
									$aTransfer['data']['values'][$iKey]['value'] = $sValue;
								}

							}

							$oObject->$sColumn = $sValue;
							
						}

						if($aValueData['dependence']){
							$oDependence = $aValueData['dependence'];
						}
					}

					foreach((array)$aNoValueFieldData[$sClass] as $sColumn => $aNoValueField){
						if(!empty($sColumn)){
							
							$mValue = $aNoValueField['value'];
							
							if(
								$mValue !== null &&
								empty($aNoValueField['dependence'])
							) {
								$oObject->$sColumn = $mValue;
							}
							
							if(
								$aNoValueField['dependence'] &&
								!$oDependence
							) {
								$oDependence = $aNoValueField['dependence'];
							}

						}
					}

					if(!$bAllEmpty){

						// Abhänigkeit prüfen
						$oDependence = $oDependence;
						if($oDependence instanceof Ext_TC_Gui2_Designer_Config_Element_Dependence){
							switch($oDependence->getDependenceType()){
								// Wenn per JoinedChilds gearbeitet wird
								case 'child':
									// Alias bestimmen
									$sChildAlias	= $oDependence->getDependenceChildAlias();
									$oSelf			= $this->_getWDBasicObject(array($iSelectedId));

									$oObject->active = 1;
									
									// Child in Hauptobjekt setzen
									$oSelf->getJoinedObjectChild($sChildAlias, $oObject);
									
									break;
							}
						}

						// Validieren
						$mValidate = $oObject->validate();

						// ok?
						if($mValidate === true) {

							// Speichern
							$oObject->save();

							$this->_aDesignLoopObjects[$sClass][$iEntry] = $oObject;

							$aDesignMapping[$iEntry] = $oObject->id;

							$oDependence = $aValueData['dependence'];

							if($oDependence instanceof Ext_TC_Gui2_Designer_Config_Element_Dependence){
								switch($oDependence->getDependenceType()){
									case 'jointable':
										$sDependenceTable = $oDependence->getDependenceTable();
										$sFk = $oDependence->getDependenceFk();
										$sPk = $oDependence->getDependencePk();
										$aStatic = $oDependence->getDependenceStaticFields();
										$aDependenceJoinTables[$sClass][$sDependenceTable]['key'] = $oDependence->getDependenceKey();
										$aDependenceJoinTables[$sClass][$sDependenceTable]['pk'] = $sPk;
										$aDependenceJoinTables[$sClass][$sDependenceTable]['fk'] = $sFk;
										$aDependenceJoinTables[$sClass][$sDependenceTable]['static_fields'] = $aStatic;
										$aDependenceJoinTables[$sClass][$sDependenceTable]['id'][$oObject->id] = $oObject->id;
										break;
								}
							}

						} else {
							$aConvertedValidate = array();
							foreach($mValidate as $sErrorField=>$aError) {
								$aErrorFieldIdentifier = self::getFieldIdentifier($sErrorField);
								$sErrorFieldKey = $aEntryData[$aErrorFieldIdentifier['column']]['alias'].'.'.$aErrorFieldIdentifier['column'].'-'.$iEntry;
								$aConvertedValidate[$sErrorFieldKey] = $aError;
							}
							$aErrors = $this->_getErrorData($aConvertedValidate, $sActionOriginal, 'error');
							$aTransfer['error'] = array_merge($aTransfer['error'], $aErrors);
						}
					}
				}
			}

			if(empty($aErrors)) {

				// Abhängigkeiten speichern
				foreach($aDependenceJoinTables as $sClass => $aJoinTabels){
					foreach($aJoinTabels as $sTable => $aEntryData){
						$aJoinTableData = array($aEntryData['pk'] => $iSelectedId);
						if(!empty($aEntryData['static_fields'])){
							foreach ($aEntryData['static_fields'] as $sStaticField => $mStaticValue) {
								$aJoinTableData[$sStaticField] = $mStaticValue;
							}
						}

						// JoinTable setzen
						$sJoinTableKey = $aEntryData['key'];
						$oSelf->$sJoinTableKey = $aEntryData['id'];

					}
				}

				// Dynamische Elemente speichern
				foreach((array)$aDesignElements as $iElement => $mValue) {

					// Merken das es vorher kein Array war und in der ersten Ebene (z.b. Buchung) gespeichert wurde.
					$bValueIsArray = is_array($mValue);

					foreach((array)$mValue as $iAdditionalId => $sValue) {

						$oDesignElement	= Ext_TC_Gui2_Design_Tab_Element::getInstance((int)$iElement);

						if($oDesignElement->type == 'upload') {

							/**
							 * @todo REPARIEREN!!!
							 */
							$iParent = (int) reset($aSelectedIds);

							$sNamespace         = Ext_TC_Gui2_Design_Tab_Element::buildTCUploadNamespace($iElement, $iParent, $iAdditionalId);
							$iNewAdditionalId   = (int)$aDesignMapping[$iAdditionalId];
							$sNewNamespace      = Ext_TC_Gui2_Design_Tab_Element::buildTCUploadNamespace($iElement, $iParent, $iNewAdditionalId);
							Ext_TC_Uploader::updateEntry($iParent, $iAdditionalId, $iNewAdditionalId, $sNamespace, $sNewNamespace);

						} else {

							$sAdditionalClass = '';

							// Nur wenn der Wert vor dem konvertieren zu einem Array ((array)$mValue) ein Array war kann
							// es hier eine $sAdditionalClass geben (z.b. Booker)
							if ($bValueIsArray) {
								$iAdditionalId = (int)$aDesignMapping[$iAdditionalId];

								if($iAdditionalId > 0){
									$oDependence = $aDependenceForDesignElements[$iElement];
									if($oDependence instanceof Ext_TC_Gui2_Designer_Config_Element_Dependence) {
										$sAdditionalClass = $oDependence->getOwnClass();
									}
								}
							}

							$oDesignElement->setValue($iSelectedId, $sValue, $iAdditionalId, $sAdditionalClass);

							foreach((array)$aTransfer['data']['values'] as $iKey => $aValues) {

								if(
									$aValues['db_column'] == 'DESIGN_ELEMENT' &&
									$aValues['db_alias'] == ($iElement.']['.$iAdditionalId)
								) {
									$aTransfer['data']['values'][$iKey]['value'] = $sValue;
								}

							}
						}

					}

				}
				// Validieren
				$mValidate = $oSelf->validate();
				
				
				// ok?
				if($mValidate === true) {
					// Objekt speichern
					$oSelf->save();

					// Flexible Felder im GUI-Design-Dialog speichern
					if(isset($_VARS['flex_childs'])) {
						$aSaveFlex = array();

						// Multiselects ohne ausgewähltem Wert sind nicht in $_VARS drin
						// Analog zur Ext_Gui2_Dialog_Data::saveEdit() also leere Arrays setzen
						$aMultiselects = array_filter($oDialog->aFlexSaveData, function($aFlexField) {
							return ($aFlexField['type'] == 8);
						});
						// Für jedes Multiselect prüfen ob es einen Wert gibt und wenn nicht diesen auf ein leeres Array setzen
						foreach($aMultiselects as $aMultiselectFlexField) {
							
							$bFound = false;
							// Prüfen ob es für das Feld einen Wert in $_VARS gibt
							foreach($_VARS['flex_childs'] as $sClass => $aItems) {								
								if(
									strpos($aMultiselectFlexField['name'], 'flex_childs['.$sClass.']') === 0 &&
									isset($aItems[$aMultiselectFlexField['id']][$aMultiselectFlexField['field_id']])
								) {
									$bFound = true;
									break;
								}								
							}

							if(!$bFound) {
								// Wenn kein Wert gefunden wurde dann wurden alle Einträge des Multiselects abgewählt und wir müssen
								// ein leeres Array für das Feld setzen
								foreach($_VARS['flex_childs'] as $sClass => $aItems) {
									if(
										$this->_aDesignLoopObjects[$sClass][$aMultiselectFlexField['id']] &&
										strpos($aMultiselectFlexField['name'], 'flex_childs['.$sClass.']') === 0
									) {
										$_VARS['flex_childs'][$sClass][$aMultiselectFlexField['id']][$aMultiselectFlexField['field_id']] = [];
										break;
									}
								}
							}							
						}

						foreach($_VARS['flex_childs'] as $sClass=>$aItems) {
							if(is_array($aItems)) {

								foreach($aItems as $iItemKey=>$aItem) {

									// -1 überspringen da dies der kopierbare ist
									if($iItemKey == -1){
										continue;
									}

									$oEntity = $this->_aDesignLoopObjects[$sClass][$iItemKey];

									foreach($aItem as $iFieldId=>$mValue) {

										// Prüfen, ob es sich um ein Feld handelt, das zu einem Unterobjekt zugeordnet werden muss
										$oFlexField = Ext_TC_Flexibility::getInstance($iFieldId);
										$oSection = $oFlexField->getSection();
										if(
											$oSection->type == 'gui_designer' &&
											$oFlexField->usage == 'student_inquiry'
										) {

											$sClassName = $sClass;
											$sType = mb_strtolower(mb_substr($sClassName, mb_strrpos($sClassName, '_')+1));

											if ($sType === 'additionalcontact') {
												$aCriteria = ['additional_contact' => 1];
											} else {
												$aCriteria = ['type' => $sType];
											}

											if(mb_strpos($sClassName, 'TA_Enquiry') !== false) {
												$oRepo = Ext_TA_Enquiry_EnquiryContact::getRepository();
												$oThisEntity = $oRepo->findOneBy(array_merge(['enquiry_id'=>$iSelectedId, 'contact_id'=>(int)$oEntity->id], $aCriteria));
											} else {
												$oRepo = Ext_TA_Inquiry_InquiryContact::getRepository();
												$oThisEntity = $oRepo->findOneBy(array_merge(['inquiry_id'=>$iSelectedId, 'contact_id'=>(int)$oEntity->id], $aCriteria));
											}

										} else {

											$oThisEntity = $oEntity;

										}

										if($oThisEntity instanceof Ext_TC_Basic) {

											if($oFlexField->isRepeatableContainer()) {
												// JoinedObject-Container Struktur auflösen
												$mValue = self::groupFlexJoinedObjectContainerData($oDialog, (int)$iFieldId, (array)$mValue);
											}

											$oThisEntity->setFlexValue($iFieldId, $mValue);
											$oThisEntity->persist();
										} else {
											throw new Exception('No valid entity for class "'.$sClass.'"!');
										}

									}

								}
							}
						}
					}
				}
				if($bPrepareOpenDialog){
					// Da sich unser Loop bereiche verändern können müssen wir das HTML nochmal neu aufbauen lassen nachem es gespeichert wurde
					$aData = $this->prepareOpenDialog($sAction, $aSelectedIds, false, $sAdditional);
					$aTransfer['data'] = $aData;
				}

			}

		}

		return $aTransfer;

	}

	/**
	 * speichert einen Dialog
	 * special saving for deativating Entry
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param mixed $sAdditional
	 * @param boolean $bSave
	 * @return array 
	 */
	protected function _saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true){
		global $_VARS;

		/*
		 * Switch standard actions
		 * Bei neuem Eintrag werden die Selected IDs zurückgesetzt
		 */
		switch($sAction) {
			
			case 'new':
				// Ein neuer Eintrag darf die eine ID vorselektiert haben
				$aSelectedIds = array();
			case 'edit':
				
				//Prüfen ob Design aktiv
				$bDesign = $this->_oGui->getOption('design');
				
				// wenn ja
				if($bDesign) {
					
					// Dialog neu bauen damit wir beim speichern die korrekten feld daten haben
					// leider überschreibt update_icons unserer aIconData array...
					// daher ist das nötig,anosnten speichert der Dialog nach dem Speichern nicht erneut
					$oDialogData = $this->_oGui->getDesignDialog($aSelectedIds);

					if(!empty($aSelectedIds)) {
						$this->aIconData['edit']['dialog_data'] = &$oDialogData;
					} else {
						$this->aIconData['new']['dialog_data'] = &$oDialogData;
					}

				}
				
				if(
					$sAdditional == 'deactivate'
				) {
					//Löschen des valid_until Wertes, siehe createDeactivateIcon in Ext_TC_Gui2_Bar
					if(
						isset($_VARS['reset']) &&
						$_VARS['reset'] == 1
					) {
						
						foreach($aData as $sDbColumn => &$mValue) {

							if(
								$sDbColumn == 'valid_until'
							) {
								if(
									//db_alias vorhanden
									is_array($mValue)
								) {
									$mKey = key($mValue);
									$mValue[$mKey] = '';
								} else {
									$mValue = '';
								}								
							}

						}
						
					}	
				}

				$aTransfer = $this->saveEditDialogData((array)$aSelectedIds, $aData, $bSave, array('action' => $sAction, 'additional' => $sAdditional));
				break;

			default:
				$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
				break;
		}
		
		return $aTransfer;
	}

	/**
	 * speichert einen Dialog und Flex daten
	 * special saving for deativating Entry
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param mixed $sAdditional
	 * @param boolean $bSave
	 * @return array 
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true){

		global $_VARS;
		
		$aFlexSaveErrors = array();
		
		if($bSave) {

			$sIconKey = self::getIconKey($sAction, $sAdditional);
			$oDialog = $this->_getDialog($sIconKey);

			if(isset($_VARS['flex'])) {
				// Prüfen der Flex felder
				$aFlexSaveErrors = $this->validateFlexFields((array)$_VARS['flex'], $oDialog);
			}

			// Prüfen ob gespeichert werden darf
			if(empty($aFlexSaveErrors)){

				// normale save Felder speichern
				$this->bWriteFlexFields = false;
				$aTransfer = $this->_saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
				$this->bWriteFlexFields = true;

				// Start Flex speichern
				switch($sAction){
					case 'edit':
					case 'new':
						$iSaveId = (int)$aTransfer['save_id'];
						if($iSaveId <= 0){
							$iSaveId = $aTransfer['data']['save_id'];
						}

						if(isset($_VARS['flex']))
						{
							if(!$this->oWDBasic){
								$this->_getWDBasicObject($aSelectedIds);
							}
							
							$sItemType = '';
							if($this->oWDBasic instanceof Ext_TC_Basic) {
								$sItemType = $this->oWDBasic->getEntityFlexType();
							}
							
							$aInfo = $this->saveEditDialogDataFlex($oDialog, $iSaveId, (array)$_VARS['flex'], $sItemType);

							// wenn infos zurückkommen wurde was gespeichert/geändert
							// dann müssen wir das in das WDBasic objekt setzen damit die
							// indexe aktuallisiert werden da ggf. das objekt an sicht nicht verändert uwrde
							// aber troztdem index änderungen vorkommen
							if(
								!empty($aInfo) &&
								method_exists($this->oWDBasic, 'updateIndexStack') === true
							) {
								$bInsert = true;
								if($sAction != 'new') {
									$bInsert = false;
								}
								$this->oWDBasic->updateIndexStack($bInsert, true);
							}
						}

						break;
				}

				// Wenn aSelectedIds == empty() (neuer Eintrag, dann Id auslesen um Flex2 Daten auch mit zu laden
				if(empty($aSelectedIds)){
					$aTempIds = explode('_', $aTransfer['data']['id']);
					$aSelectedIds = array($aTempIds[1]);
				}

				$aData = $aTransfer['data'];
				$aData = $this->setFlexData($oDialog, $aData, $aSelectedIds);
				$aTransfer['data'] = $aData;
			} else {
				// Fehler bei den Flexfeldern

				// Wenn $aSelectedIds == empty() (neuer Eintrag, dann ist die ID == 0)
				if(empty($aSelectedIds)){
					$aSelectedIds = array(0);
				}

				$aFlexSaveErrors = reset($aFlexSaveErrors);

				$aTransfer = array();
				$aTransfer['action'] = 'showFlexError';
				$aTransfer['error'] = $aFlexSaveErrors;
				$aTransfer['data']['id'] = 'ID_' . reset($aSelectedIds);
				$sTransactionToken = $this->_generateToken('ID_' . reset($aSelectedIds)); 
				$aTransfer['token'] = $sTransactionToken;

			}	
		}
		else
		{
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}

		return $aTransfer;

	}

	/**
	 * @inheritdoc
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true) {

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if($this->bWriteFlexFields) {
			// Wenn es kein Dialog-Objekt gibt, gibt es auch keine Flex-Felder (vorher wurde das immer ausgeführt, ohne Dialog-Objekt)
			$sIconKey = self::getIconKey($sIconAction, $sAdditional);
			if(!empty($this->aIconData[$sIconKey]['dialog_data'])) {
				$oDialog = $this->_getDialog($sIconKey);
				$aData = $this->setFlexData($oDialog, $aData, $aSelectedIds);
			}
		}

		return $aData;
	}

	/**
	 * {@inheritdoc}
	 */
	public function manipulateTableDataResultsByRef(&$aResult){

		if(!$this->_oGui->wdsearch_use_stack) {

			$aSections = $this->_oGui->getFlexSections();

			if(!empty($aSections)) {

				foreach($aSections as $aSection) {

					if(!isset($aSection['primary_key'])) {
						$aSection['primary_key'] = 'id';
					}

					$tableDataColumn = $aSection['data'] ?? $aSection['primary_key'];

					$aItems = array();
					foreach((array)$aResult['data'] as $iKey => $aTableDataRow) {
						if (
							isset($aTableDataRow[$tableDataColumn]) &&
							$aTableDataRow[$tableDataColumn] > 0
						) {
							$aItems[] = (int)$aTableDataRow[$tableDataColumn];
						}
					}

					$aFlexData = Ext_TC_Flexibility::getListData($aItems, $aSection['section'], true, $this->_bFormatFlexValues);

					foreach((array)$aResult['data'] as $iKey => $aTableDataRow) {
						
						if(empty($aFlexData[$aTableDataRow[$tableDataColumn]])) {
							continue;
						}
						
						foreach((array)$aFlexData[$aTableDataRow[$tableDataColumn]] as $iId => $sValue) {
							$aResult['data'][$iKey]['flex_' . $iId] = $sValue;
						}
					}

				}

			}

		}

	}
	
	/**
	 * Set All Flex 2 Data
	 * @param <array> $aData
	 * @param <array> $aSelectedIds
	 * @return <array> 
	 */
	public function setFlexData(\Gui2\Dialog\DialogInterface $oDialog, $aData, $aSelectedIds){

		$aSelectedIds = (array)$aSelectedIds;
		
		if(empty($aSelectedIds)){
			$aSelectedIds = array(0);
		}
		$iId = reset($aSelectedIds);
		
		// Wenn das Select auf "Eintrag wieder öffnen" steht im Dialog (action == new), dürfen 
		// NICHT anhand der ursprünglichen ID Flex Felder geladen werden! #1864
		if($aData['action'] == 'new'){
			$iId = 0;
		}
	
		// Wenn Tabs angegeben sind
		if(!empty($aData['tabs'])) {
			// Flex Felder ergänzen
			foreach((array)$aData['tabs'] as $iTabNum => $aTab){

				if(!empty($aTab['options']['section'])){

					// Flexfelder hinzufügen
					$aData['tabs'][$iTabNum]['html'] .= $this->getFlexEditDataHTML($oDialog, $aTab['options']['section'], $iId, $aTab['readonly'] ?? null, $aTab['disabled'] ?? null);
				}

				// Prüfen ob User das Recht für diesen Tab hat
				if(isset($aTab['options']['access']) && !empty($aTab['options']['access'])){

					$bHasRight = Access::getInstance()->hasRight($aTab['options']['access']);
					if(!$bHasRight){
						// .. ansonsten unsetten
						unset($aData['tabs'][$iTabNum]);
					}
				}
			}

			$aData['tabs'] = (array)$aData['tabs'];
			// Keys des Arrays wieder neu setzen ohne zu sortieren
			$aData['tabs'] = array_merge($aData['tabs']);

		} else {

			if(!empty($aData['options']['section'])){
				// Flexfelder hinzufügen
				$aData['html'] .= $this->getFlexEditDataHTML($oDialog, $aData['options']['section'], $iId, $aData['readonly'], $aData['disabled']);
			}

		}

		return $aData;
	}
	
	 public function validateFlexFields(array $aFlexFieldSaveData, Ext_Gui2_Dialog $oDialog = null){
		 
		$aErrors = array();

		foreach($aFlexFieldSaveData as $iObjectId => $aFields){
			
			foreach((array)$aFields as $iId => $mValue){

				$sSql = "SELECT
							`kfsf`.*
						FROM
							`tc_flex_sections_fields` AS `kfsf`
						WHERE
							`kfsf`.`id` = :fieldId
						LIMIT 1
						";
				$aSql['fieldId']	= (int)$iId;

				$aFieldData = DB::getQueryRow($sSql,$aSql);

				if($aFieldData['type'] == Ext_TC_Flexibility::TYPE_REPEATABLE) {

					if($oDialog === null) {
						throw new \Exception('No dialog given for validation of repeatable container!');
					}

					$aContainers = self::groupFlexJoinedObjectContainerData($oDialog, $iId, $mValue);

					foreach($aContainers as $iContainerIndex => $aContainer) {

						$aContainerErrors = $this->validateFlexFields([$iObjectId => $aContainer], $oDialog);

						if(!empty($aContainerErrors)) {
							foreach($aContainerErrors as $m => $aObjectErrors) {
								foreach($aObjectErrors as $aError) {
									// TODO schöner wäre es die field_id flexibel in der _validateFlexField() zu setzen
									// ID des Feldes umschreiben ][....][
									$aError['field_id'] = $iId.']['.$iContainerIndex.'][flex-'.$iId.']['.$iObjectId.']['.$aError['field_id'];
									$aErrors[$m][] = $aError;
								}
							}
						}
					}

				} else {
					if(
						$aFieldData['i18n'] == 1 &&
						is_array($mValue)
					) {
						foreach($mValue as $mIsoValue) {
							$aError = $this->_validateFlexField($aFieldData, $mIsoValue);
							$aErrors = array_merge($aErrors, $aError);
						}
					} else {
						$aError = $this->_validateFlexField($aFieldData, $mValue);
						$aErrors = array_merge($aErrors, $aError);
					}
				}
			}
		}

		return $aErrors;

	 }

	 public function _validateFlexField($aFieldData, $mValue) {
		 
		 $aErrors = array();
		 
		 if(is_scalar($mValue)) {
			$mValue = trim($mValue);
		 }

		 $mFlexFieldId = (int)$aFieldData['id'];

		 $validateBy = $aFieldData['validate_by'];

		// Prüfen ob gespeichert werden darf
		if(
			$aFieldData['type'] == '0' &&
			$validateBy != '0' &&
			$validateBy != '' &&
			$mValue !== '' &&
			!is_null($mValue)
		){

			$oValidator = new WDValidate();
			// Werte von Datumsvalidierungsfeldern müssen erst in das DB-Datumsformat formatiert werden, da damit die WDValidate
			// arbeitet -> und im Frontend sollten ja bestenfalls keine Datumswerte im DB-Format angegeben werden müssen.
			if (
				$validateBy === 'DATE_PAST' ||
				$validateBy === 'DATE_FUTURE'
			) {
				$dateFormat = \Factory::getObject('Ext_TC_Gui2_Format_Date');
				// Nur %O funktioniert nicht wirklich, wenn das als Datumsformat angegeben ist.
				$mValue = $dateFormat->convert($mValue);
			}

			$oValidator->value = $mValue;
			$oValidator->check = $aFieldData['validate_by'];
			$oValidator->parameter = $aFieldData['regex'];
			$bCheck = (bool)$oValidator->execute();

			if (!$bCheck) {
				$sErrorMessage = empty($aFieldData['error'])
					? $this->getErrorMessage('INVALID_'.$aFieldData['validate_by'], $mFlexFieldId, $aFieldData['title'])
					: $aFieldData['error'];

				$aError = array();
				$aError['title']	= $aFieldData['title'];
				$aError['message']	= $sErrorMessage;
				$aError['field_id'] = $mFlexFieldId;

				$aErrors[$iObjectId][] = $aError;
			}
		} else if (
			$aFieldData['required'] == 1 &&
			(
				(
					$aFieldData['type'] == 8 &&
					empty($mValue)
				) ||
				(
					$aFieldData['type'] != 8 &&
					(
						$mValue === '' ||
						is_null($mValue)
					)
				)
			)			
		) {
			$aError				= array();
			$sErrorRequired		= $this->getTranslation('field_required');
			$sErrorRequired		= sprintf($sErrorRequired, $aFieldData['title']);
			$aError['message']	= $sErrorRequired;
			$aError['field_id'] = $mFlexFieldId;

			$aErrors[$iObjectId][] = $aError;
		}
		 
		// Maximale Zeichenlänge überprüfen
		if(in_array($aFieldData['type'], [0, 1, 6])) {
			$iMaxLength = (int) $aFieldData['max_length'];
		
			if(
				$iMaxLength > 0 &&
				mb_strlen($mValue) > $iMaxLength
			) {
			   $sErrorMaxLength		= L10N::t('Der Wert für das Feld "%s" ist zu lang. Das Feld ist auf "%s" Zeichen begrenzt.');

			   $aError	= [
				   'message' => sprintf($sErrorMaxLength, $aFieldData['title'], $aFieldData['max_length']),
				   'field_id' => $mFlexFieldId
			   ];

			   $aErrors[$iObjectId][] = $aError;
		   }
		}
			
		return $aErrors;
	 }

	/*
	 * Methode speichert die Flex2 Felder
	 */
	protected function saveEditDialogDataFlex(Ext_Gui2_Dialog $oDialog, $iId, array $aSaveDataFlex, $sItemType = ''){

		if($iId <= 0){
			return false;
		}

		$aInfoTotal = array();

		foreach($aSaveDataFlex as $aFields) {

			foreach($oDialog->aFlexSaveData as $aFlexField) {

				if($aFlexField['parent_id'] > 0) {
					continue;
				}

				if(
					!isset($aFields[$aFlexField['field_id']]) &&
					$aFlexField['type'] == 8
				) {

					// Multiselects ohne ausgewählten Wert sind nicht in $_VARS drin
					// Analog zur Ext_Gui2_Dialog_Data::saveEdit() also leere Arrays setzen
					$aFields[$aFlexField['field_id']] = [];

				} else if($aFlexField['type'] == \Ext_TC_Flexibility::TYPE_REPEATABLE) {

					// Flex-Felder in JoinedObject-Containern auflösen und in die normale Struktur umschreiben
					$aFields[$aFlexField['field_id']] = self::groupFlexJoinedObjectContainerData($oDialog, (int)$aFlexField['field_id'], (array)$aFields[$aFlexField['field_id']]);

				}
			}

			$aInfo = Ext_TC_Flexibility::saveData($aFields, $iId, $sItemType);
			$aInfoTotal	= array_merge($aInfoTotal, $aInfo);
		}
		
		return $aInfoTotal;
	}

	public static function groupFlexJoinedObjectContainerData(\Ext_Gui2_Dialog $oDialog, int $iFieldId, array $aFieldSaveData): array {

		if(empty($aFieldSaveData)) {
			return [];
		}

		$aChildFields = collect($oDialog->aFlexSaveData)
			->filter(function ($aField) use ($iFieldId) {
				return ((int)$aField['parent_id'] === $iFieldId);
			})
			->mapWithKeys(function($aField) {
				// Aufgrund des Loops können die Felder mehrfach in $oDialog->aFlexSaveData vorkommen
				return [$aField['field_id'] => $aField];
			});

		$aCombined = [];
		foreach($aFieldSaveData as $iIndex => $aContainers) {
			foreach($aContainers as $aContainer) {
				foreach($aContainer as $aRowData) {

					foreach($aChildFields as $aChildField) {
						if(!isset($aRowData[$aChildField['field_id']])) {
							// Sichergehen das der Wert da ist damit die Indizes der Container stimmen (nicht ausgewählte MS
							// sind beispielsweise nicht in $aFieldSaveData vorhanden)
							if($aChildField['type'] == Ext_TC_Flexibility::TYPE_MULTISELECT) {
								$aRowData[$aChildField['field_id']] = [];
							} else {
								$aRowData[$aChildField['field_id']] = "";
							}
						}
					}

					$aCombined[] = $aRowData;
				}
			}
		}

		return $aCombined;
	}

	/**
	 * Methode liefert das HTML pro Tab für die Flex2
	 *
	 * Parameter $bIgnoreVisible und $aLanguages entfernt, da diese nicht verwendet wurden
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array|string $mSection
	 * @param int|WDBasic $mId Objekt überschreibt $this->>oWDBasic
	 * @param int $iReadOnly
	 * @param int $iDisabled
	 * @param string $sFieldIdentifier
	 * @param array $aSavedFlexData
	 * @return string
	 */
	public function getFlexEditDataHTML(Ext_Gui2_Dialog $oDialog, $mSection, $mId, $iReadOnly = 0, $iDisabled = 1, $sFieldIdentifier = 'flex') {

		if(empty($mSection)) {
			return '';
		}

		$sHTML = '';
		$sLang = Factory::executeStatic('Ext_TC_Util', 'getInterfaceLanguage');

		foreach((array)$mSection as $sSection) {

			// Angabe mit usage
			if(is_array($sSection)) {
				$aUsage = $sSection[1];
				$sSection = $sSection[0];
			}

			// Hole Felder aus DB
			$aFields = Ext_TC_Flexibility::getFields($sSection, false, $aUsage);

			// Hole gespeicherte Daten aus DB
			$aSavedFlexData = $this->getFlexEditData($mId, $sSection, true);

			$sHTML .=  '<div class="dialog-flex-fields" data-section="'.$sSection.'">';

			//für alle Felder
			foreach((array)$aFields as $oField) {

				// Value ermitteln falls vorhanden
				$mValue = '';
				if (isset($aSavedFlexData[$oField->id])) {
					$mValue = $aSavedFlexData[$oField->id];
				}

				$sHTML .= $this->generateFlexEditDataField($oDialog, $oField->aData, $mId, $mValue, $sLang, $iReadOnly, $sFieldIdentifier, [], $iDisabled);

			}

			$sHTML .= '</div>';

		}
		
		return $sHTML;
	}

	public function generateFlexEditDataField(Ext_Gui2_Dialog $oDialog, $mField, $iId, $mValue, $sLang, $iReadOnly, $sFieldIdentifierPrefix='flex', $aLanguages = array(), $iDisabled = 1, $iLoopIndex = 0) {

		if($mField instanceof Ext_TC_Flexibility) {
			$aField = $mField->aData;
		} else {
			$aField = $mField;
		}

		if($iId instanceof WDBasic) {
			$iId = $iId->id;
		}
		
		$sReadOnly		= '';
		$sReadOnlyClass = '';

		// Gibt an ob Felder in dieser Section editierbar sind
		if($iReadOnly == 1) {
			$sReadOnly		= ' readonly = "readonly" ';
			
			if(
				$iDisabled == 1 || 
				is_null($iDisabled)
			) {
				$sReadOnly .= ' disabled = "disabled" ';
			}
			
			$sReadOnlyClass = ' readonly ';
		}		

		if($aField['type'] != 3) { // keine Überschrift

			if(
				empty($aLanguages) &&
				($aField['i18n'] == 1 || $aField['type'] == \Ext_TC_Flexibility::TYPE_REPEATABLE)
			) {
				$aObjectLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getLanguages');
				$aLanguages = array_keys($aObjectLanguages);

			}

			if(
				$aField['required'] == 1 && 
				$aField['type'] != 2
			) {
				// hightlite Pflichtfelder - keine checkboxen
				$sStar = ' *';
				$sRequiredClass = ' required ';
			} else {
				$sStar = '';
				$sRequiredClass = '';
			}

			$sRowKey = \Gui2\Service\InfoIcon\Hashing::encode($this->_oGui, $oDialog->sDialogIDTag, 'individual.'.$aField['id']);
			
			$sHTML = '<div class="GUIDialogRow form-group form-group-sm" data-row-key="'.$sRowKey.'">';
				$sHTML .= '<label class="GUIDialogRowLabelDiv control-label col-sm-'.(($oDialog->bSmallLabels)?2:3).'">' . $aField['title'] . $sStar . '</label>';
				$sHTML .= '<div class="GUIDialogRowInputDiv'.($aField['i18n'] ? ' i18n-fields ' : ' ').'col-sm-'.(($oDialog->bSmallLabels)?10:9).'">';

				$sName = $sFieldIdentifierPrefix.'[' . $iId . '][' . $aField['id'] . ']';
				$sDataAttributes = 'data-flex-id="'.$aField['id'].'"';

				// TODO $this->aFlexFields entfernen (steht mittlerweile im Dialog, damit man die Infos auch beim Save hat)
				$this->aFlexFields[$sName] = array(
					'name' => $sName,
					'id' => $iId,
					'field_id' => $aField['id'],
					'type' => $aField['type'],
					'i18n' => $aField['i18n'],
					'parent_id' => $aField['parent_id'],
					'loop_index' => $iLoopIndex,
				);

				$oDialog->aFlexSaveData[$sName] = $this->aFlexFields[$sName];

				switch($aField['type']){
					case 0: // Text
						if($aField['i18n'] == 1) {
							foreach($aLanguages as $sIso) {

								$sValue = '';
								if(
									is_array($mValue) &&
									$mValue[$sIso]
								) {
									$sValue = $mValue[$sIso];
								}
								
								$sI18N = $sName . '['.$sIso.']';

								$sHTML .= '<div class="input-group">';
									$sHTML .= '<span class="input-group-addon"><img src="'.Ext_TC_Util::getFlagIcon($sIso, '/admin/media/spacer.gif').'" class="flag" /></span>';
									$sHTML .= '<input id="'.$sI18N.'" name="'.$sI18N.'" '.$sDataAttributes.' type="text" class="i18nInput txt form-control input-sm ' . $sReadOnlyClass . $sRequiredClass . '" value="'.\Util::getEscapedString($sValue).'" ' . $sReadOnly . ' />';
								$sHTML .= '</div>';

							}
						} else {
							$sHTML .= '<input id="'.$sName.'" name="'.$sName.'" '.$sDataAttributes.' type="text" class="txt form-control input-sm ' . $sReadOnlyClass . $sRequiredClass . '" value="' .\Util::getEscapedString($mValue). '" ' . $sReadOnly . ' />';
						}
						break;
					case 1: // Textarea
					case 6: // Textarea - HTML
						
						$sClass = '';
						if($aField['type'] == 6) {
							$sClass .= ' GuiDialogHtmlEditor advanced ';
						}
						
						if($aField['i18n'] == 1) {
							foreach($aLanguages as $sIso) {
								
								$sValue = '';
								if(
									is_array($mValue) &&
									$mValue[$sIso]
								) {
									$sValue = $mValue[$sIso];
								}
								
								$sHTML .= '<div class="input-group">';

									$sHTML .= '<span class="i18nFlag input-group-addon">';
										$sHTML .= '<img src="'.Ext_TC_Util::getFlagIcon($sIso, '/admin/media/spacer.gif').'" alt="'.$sIso.'">';
									$sHTML .= '</span>';

									$sI18N = $sName . '['.$sIso.']';

									$sHTML .= '<textarea id="'.$sI18N.'" name="'.$sI18N.'" '.$sDataAttributes.' class="i18nInput txt form-control input-sm ' . $sClass . $sReadOnlyClass . $sRequiredClass . '" ' . $sReadOnly . ' >' . \Util::getEscapedString($sValue). '</textarea>';
									
										//if($aField['type'] == 6) {
											// Redundanz mit Ext_Gui2_Dialog::createI18NRow()
										//	$sHTML .= '<div class="i18nFlag" style="top: 10px; left: -20px;">';
										//} else {
										//	$sHTML .= '<div class="i18nFlag">';
										//}

										//$sHTML .= '<img src="'.Ext_TC_Util::getFlagIcon($sIso, '/admin/media/spacer.gif').'" />';
									//$sHTML .= '</div>';
									//$sHTML .= '<div class="divCleaner"></div>';
								$sHTML .= '</div>';
							}
						} else {
							$sHTML .= '<textarea id="'.$sName.'" name="'.$sName.'" '.$sDataAttributes.' class="txt form-control input-sm ' . $sClass . $sReadOnlyClass . $sRequiredClass . '" ' . $sReadOnly . ' >' .\Util::getEscapedString($mValue). '</textarea>';
						}

						break;
					case 2: // Checkbox
						$sChecked = '';
						if($mValue == 1){
							$sChecked = 'checked="checked"';
						}

						// Hiddenfeld, damit immer ein Wert übermittelt wird
						$sHTML .= '<input name="'.$sName.'" type="hidden" value="0" ' . $sReadOnly . ' />';
						$sHTML .= '<input id="'.$sName.'" name="'.$sName.'" '.$sDataAttributes.' class="txt ' . $sReadOnlyClass . '" type="checkbox" value="1" ' . $sReadOnly . ' ' . $sChecked . '/>';
						break;
					case 4: // Datum
						$sHTML .= '<div class="GUIDialogRowCalendarDiv input-group date">';
							$sHTML .= '<div class="input-group-addon calendar_img"><i class="fa fa-calendar"></i></div>';
							$sHTML .= '<input class="txt form-control input-sm calendar_input ' . $sReadOnlyClass . $sRequiredClass . '" type="text" name="'.$sName.'" value="' . $mValue . '" id="'.$sName.'" '.$sDataAttributes.' ' . $sReadOnly . ' autocomplete="off" />';
						$sHTML .= '</div>';
						break;
					case 5: // Select
					case 8: // Multiselect
					case 7: // Yes/No

						if(
							$aField['type'] == 5 ||
							$aField['type'] == 8	
						) {
							$aOptions = Ext_TC_Flexibility::getOptions($aField['id'], $sLang);
						} else {
							$aOptions = Ext_TC_Util::getYesNoArray(false);
						}

						if($aField['type'] == 8) {
							$sHTML .= '<select id="'.$sName.'" name="'.$sName.'[]" '.$sDataAttributes.' multiple size="5" class="jQm jQmsearch txt form-control input-sm ' . $sReadOnlyClass . $sRequiredClass . '" ' . $sReadOnly . ' >';
							if(!is_array($mValue)) {
								$aValue = json_decode($mValue, true);
							} else {
								$aValue = $mValue;
							}
						} else {
							$sHTML .= '<select id="'.$sName.'" name="'.$sName.'"  '.$sDataAttributes.' class="txt form-control input-sm ' . $sReadOnlyClass . $sRequiredClass . '" ' . $sReadOnly . ' >';
							$aOptions = Ext_TC_Util::addEmptyItem($aOptions, L10N::t('Leerer Flex Eintrag'));
							$aValue = array($mValue);
						}

						foreach((array)$aOptions as $iKey => $sTitle){
							$sSelected = '';
							if(!empty($aValue)) {
								if(in_array($iKey, $aValue)) {
									$sSelected = 'selected="selected"';
								}
							}
							$sHTML .= '<option value="'.$iKey.'" '.$sSelected.'>'.$sTitle.'</option>';
						}
						$sHTML .= '</select>';

						break;

					case \Ext_TC_Flexibility::TYPE_REPEATABLE:

						// TODO - dadurch das die Felder hier nicht ins JS gesetzt werden funktionieren hier so Einstellungen
						// wie min/max nicht. Das wird alles schon in der getEditDialogData() gemacht nur das funktioniert
						// aktuell nur für den Gui-Designer

						$aChildFields = \Ext_TC_Flexibility::getRepository()->findBy(['parent_id' => $aField['id']]);

						if(!empty($aChildFields)) {

							$iContainers = count((array)$mValue);
							if($iContainers === 0) $iContainers = 1;

							// Unterstrich geht nicht da sonst das removeJoinedObjectContainer wegen der Nummer im Key nicht funktioniert
							$sJoinedObjectKey = 'flex-'.$aField['id'];

							$sHTML .= '<div class="GUIDialogJoinedObjectContainer clearfix" id="joinedobjectcontainer_'.$sJoinedObjectKey.'">';

								for($i = 0; $i < $iContainers; ++$i) {
									$sHTML .= '<div class="GUIDialogJoinedObjectContainerRow clearfix" id="row_joinedobjectcontainer_'.$sJoinedObjectKey.'_'.$i.'">';

									$aContainerValues = $mValue[$i] ?? [];

									foreach($aChildFields as $oChildField) {
										$mContainerValue = $aContainerValues[$oChildField->id] ?? '';
										// Der $sFieldIdentifierPrefix muss leider so verschachtelt sein damit das JS die Container-ID richtig aktualisiert
										$sHTML .= $this->generateFlexEditDataField($oDialog, $oChildField, $iId, $mContainerValue, $sLang, $iReadOnly, $sFieldIdentifierPrefix.'['. $iId.']['. $aField['id'].']['.$i.']['.$sJoinedObjectKey.']', $aLanguages, $iDisabled, $i);
									}

									$sHTML .= '<button class="btn btn-sm btn-default remove_joinedobjectcontainer" style="'.(($i === 0) ? "display:none" : "").'" id="remove_joinedobjectcontainer_'.$sJoinedObjectKey.'_'.$i.'"><i class="fa fa-minus-circle" title=""></i> '.L10N::t('Einstellung löschen').'</button>';

									$sHTML .= '</div>';
								}

							$sHTML .= '<div class="add-btn-container form-inline form-group-sm"><button class="btn btn-sm btn-primary btn-default add_joinedobjectcontainer" id="add_joinedobjectcontainer_flex-'.$aField['id'].'"><i class="fa fa-plus-circle" title=""></i> '.L10N::t('Einstellung hinzufügen').'</button></div>';

							$sHTML .= '</div>';
						}

						break;
				}
				$sHTML .= '</div>';
			$sHTML .= '</div>';
		} else { // Überschrift
			$sHTML = '<h4 class="sc_h3">' . $aField['title'] . '</h4>';
		}
		
		return $sHTML;
	}

	protected function fillDialogInfoIconMessageBag(Gui2\DTO\InfoIconMessageBag $oMessageBag) {
	
		$sSql = "
			SELECT
				`id`,
				`description`
			FROM
				`tc_flex_sections_fields`
			WHERE
				`active` = 1 AND
				`description` != ''
		";

		// @todo nach Section filtern
		$aFlexFieldsWithDescription = (array) \DB::getQueryData($sSql);		
		
		foreach($aFlexFieldsWithDescription as $aFlexFieldData) {
			$oMessageBag->addText('individual.'.$aFlexFieldData['id'], $aFlexFieldData['description']);
		}
		
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function prepareColumnListByRef(&$aColumnList){

		parent::prepareColumnListByRef($aColumnList);

		$aSections = array_column($this->_oGui->getFlexSections(true), 'section');

//		$aSections = array();
//		if(!empty($this->_oGui->_aConfig['sSection'])) {
//			$aSections[] = $this->_oGui->_aConfig['sSection'];
//		}
//
//		if(!empty($this->_oGui->_aConfig['additional_sections'])) {
//			foreach($this->_oGui->_aConfig['additional_sections'] as $aSection) {
//				if(
//					empty($aSection['set']) ||
//					in_array($this->_oGui->set, $aSection['set'])
//				) {
//					$aSections[] = $aSection['section'];
//				}
//			}
//		}

		if(!empty($aSections)) {
			
			$sFrontendLanguage = Factory::executeStatic('Ext_TC_Util', 'getInterfaceLanguage');
			$sBackendLanguage = System::d('systemlanguage');;

			$bHasColumnGroup = $this->_oGui->getOption('gui_has_column_group');
			$oColFlexGroup = null;
			if($bHasColumnGroup) {
				$oColFlexGroup = $this->_oGui->createColumnGroup();
				$oColFlexGroup->title = L10N::t('Individuelle Felder', Ext_Gui2::$sAllGuiListL10N);
			}

			$aFlexFields = Ext_TC_Flexibility::getSectionFieldData($aSections, false, true);
			$aIndexFields = array();

			if($this->_oGui->checkWDSearch()) {
				$iSortable	= 1;
				$oGenerator	= new Ext_Gui2_Index_Generator($this->_oGui->wdsearch_index);
				$aFields = $oGenerator->getFields();

				foreach($aFields as $aField) {
					$aIndexFields[$aField['column']] = $aField;
				}
			} else {
				$iSortable = 0;
			}

			foreach((array)$aFlexFields as $aField) {

				if($aField['type'] != 3) {
					
					$sFlexColumn = 'flex_' . $aField['id'];
					$sSelectColumn = $sFlexColumn;
					
					if(
						isset($aIndexFields[$sFlexColumn]) &&
						isset($aIndexFields[$sFlexColumn]['sortable_column'])
					) {
						$sFlexColumn = $aIndexFields[$sFlexColumn]['sortable_column'];
					}
					
					// Keine überschriften in Flex spalten zur auswahl
					$oColFlex = new Ext_Gui2_Head();
					$oColFlex->db_column = $sFlexColumn;
					$oColFlex->select_column = $sSelectColumn;
					$oColFlex->title = $aField['title'];
					$oColFlex->width = 120;// NUR Zahlen!!
					$oColFlex->width_resize	= false;
					$oColFlex->sortable	= $iSortable;
					$oColFlex->default = false;

					$sFormat = $this->_oGui->calendar_format;
				
					$sInterfaceLanguage = $sFrontendLanguage;

					if($aField['type'] == 4) {
						// Date
						$oColFlex->format = 'text';
						$oColFlex->width = Ext_TC_Util::getTableColumnWidth('date');
						$oColFlex->format = new $sFormat();
					} elseif($aField['type'] == 2) {
						// Checkbox
						$oColFlex->format = new Ext_TC_Gui2_Format_YesNo();
						$sInterfaceLanguage = $sBackendLanguage;
					} elseif($aField['type'] == 7) {
						// Ja/Nein
						$oColFlex->format = new Ext_TC_Flexible_Gui2_Format_YesNo();
						$sInterfaceLanguage = $sBackendLanguage;
					} elseif($aField['type'] == 8) {
						$aOptions = Ext_TC_Flexibility::getOptions($aField['id'], $sInterfaceLanguage);

						$oColFlex->format = new Ext_TC_Gui2_Format_Multiselect($aOptions, ', ', 'json');

					} elseif(
						$aField['type'] == 0 ||
                        $aField['type'] == 1 ||
                        $aField['type'] == 6
                    ) {
						$oColFlex->width = Ext_TC_Util::getTableColumnWidth('comment');
                        $iCount = 40;
                        if($this->_oGui->wdsearch_use_stack) {
                            $sFlexColumn .= '_original';
                        }
						$oColFlex->format = new Ext_Gui2_View_Format_ToolTip($sFlexColumn, true, $iCount);
					}

					if(
						(
							$aField['type'] == 5 ||
							//$aField['type'] == 2 ||
							//$aField['type'] == 7 ||
							(
								(
									$aField['type'] == 0 ||
									$aField['type'] == 1 ||
									$aField['type'] == 6
								) &&
								$aField['i18n'] == 1
							)
						)	
						&&
						$this->_oGui->checkWDSearch()
					) {
						$oColFlex->db_column = $oColFlex->db_column.'_'.$sInterfaceLanguage;
						$oColFlex->select_column = $oColFlex->select_column.'_'.$sInterfaceLanguage;
					}

					// Ja/Nein kann sofort formatiert werden und muss nicht im Index stehen
					if(
						$this->_oGui->checkWDSearch() &&
						in_array($aField['type'], [2, 7])
					) {
						$oColFlex->post_format = $oColFlex->format;
					}

					if($bHasColumnGroup) {
						$oColFlex->group = $oColFlexGroup;
					}

					$aColumnList[] = $oColFlex;

				}

			}

		 }

	 }
	
	/**
	 * Methode liefert die gespeicherten Flex2 Werte pro Tab
	 *
	 * @param int|WDBasic $mEntity
	 * @param string $sSection
	 * @param bool $bIgnoreVisible
	 * @return array
	 */
	protected function getFlexEditData($mEntity, $sSection, $bIgnoreVisible = false) {

		/* @var \Ext_TC_Basic $oEntity */
		if($mEntity instanceof WDBasic) {
			$oEntity = $mEntity;
		} else {
			if(!$this->oWDBasic) {
				$this->_getWDBasicObject(array($mEntity));
			}
			$oEntity = $this->oWDBasic;
		}

		$aResult = $oEntity->getFlexibilityValues($sSection, $bIgnoreVisible);
		$aBack	= array();

		foreach((array)$aResult as $iKey => $aField) {

			if($aField['parent_id'] > 0) {
				continue;
			}

			$cFormatDate = function($mDate) {
				$oFormat = new $this->_oGui->calendar_format();
				$oDummy = null;
				$aResultData = array();
				return $oFormat->format($mDate, $oDummy, $aResultData);
			};

			if($aField['type'] == Ext_TC_Flexibility::TYPE_REPEATABLE) {

				$aChildFields = array_filter($aResult, function($aChildField) use ($aField) {
					return ((int)$aChildField['parent_id'] === (int)$aField['field_id']);
				});

				$aCombined = [];
				foreach($aChildFields as $aChildField) {
					$mValue = json_decode($aChildField['value'], true);

					foreach($mValue as $iContainerIndex => $mContainerValue) {

						if($aChildField['type'] == Ext_TC_Flexibility::TYPE_DATE) {
							$mContainerValue = $cFormatDate($mContainerValue);
						}

						if($aChildField['i18n'] == 1 && !empty($aChildField['language_iso'])) {
							$aCombined[$iContainerIndex][$aChildField['field_id']][$aChildField['language_iso']] = $mContainerValue;
						} else {
							$aCombined[$iContainerIndex][$aChildField['field_id']] = $mContainerValue;
						}
					}

				}

				$aBack[$aField['field_id']] = $aCombined;

			} else {

				if($aField['type'] == 4) {
					$aField['value'] = $cFormatDate($aField['value']);
				}

				if($aField['type'] == 8) {
					$aField['value'] = json_decode($aField['value'], true);
				}

				if($aField['i18n'] == 1) {
					$aBack[$aField['field_id']][$aField['language_iso']] = $aField['value'];
				} else {
					/*
					 * Hier werden auch Werte mit language_iso eingetragen
					 * Der letzte Wert aus dem Ergebnis wird hier geschrieben, da der Wert überschrieben wird für jeden
					 * vorkommende language_iso.
					 */
					$aBack[$aField['field_id']] = $aField['value'];
				}

			}

		}
		
		$aTransfer = [
			'parent_ids' => $this->_aParentGuiIds,
			'row_id' => $oEntity->id,
			'section' => $sSection,
			'values' => &$aBack
		];

		\System::wd()->executeHook('tc_flex_data', $aTransfer);

		return $aBack;
	}
	## END

	/**
	 * WRAPPER Ajax Request verarbeiten
	 * @param $_VARS
	 */
	public function switchAjaxRequest($_VARS) {
		
		//Nummernformat in der Schulsoftware abhängig von der Schule, deshalb ne Factory dazu bereit stellen
		$aTemp = $this->getNumberFormat();

		if (
			$_VARS['task'] === 'request' &&
			$_VARS['action'] === 'communication'
		) {
			$modelClass = $this->_oGui->getOption('communication_model_class', $this->_oGui->class_wdbasic);
			$additional = [];

			if ($this->_oGui->checkEncode()) {
				$encodedIdColumn = (!empty($column = $this->_oGui->getOption('communication_encode_model_id', $this->_oGui->encode_data_id_field)))
					? $column
					: 'id';

				$ids = $this->_oGui->decodeId($_VARS['id'], $encodedIdColumn);
				// Wird bei Massenkommunikation zu viel und wird aktuell nicht verwendet
				//$additional['gui2_encoded'] = $this->_oGui->decodeId($_VARS['id']);
			} else {
				$ids = $_VARS['id'];
			}

			$notifiables = \Factory::executeStatic($modelClass, 'query')->findOrFail($ids);

			$access = ($_VARS['additional'])
				? $this->readCommunicationAccessFromIconData($_VARS['additional'])
				: null;

			$this->openCommunication($notifiables, application: $_VARS['additional'] ?? null, access: $access, additional: $additional);

		} else {
			$aTransfer = $this->_switchAjaxRequest($_VARS);
		}

		if(!isset($aTransfer['number_format'])) {
			$aTransfer['number_format'] = $aTemp;
		}

		echo Util::encodeJson($aTransfer);
	}

    /**
	 * Liefert das Nummernformat
	 * @global array $system_data
	 * @return array
	 */
	public function getNumberFormat() {
		global $system_data;

		// Währungsformatierung
		$aTemp = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getNumberFormatData', array($system_data['number_format'] ?? null));

		return $aTemp;

	}

	public function getInterfaceLanguage(){

		$sLanguage = Ext_TC_System::getInterfaceLanguage();

		return $sLanguage;
	}


	protected function _getSeparatorForExport(){
			
		$oCurrentUser = System::getCurrentUser();
		$sSeperator = $oCurrentUser->getSeparatorForExport();

		return $sSeperator;
	}
	
	/**
	 * Liefert den Export-Charset
	 * @return string 
	 */
	protected function _getCharsetForExport(){
		
		$oCurrentUser = System::getCurrentUser();
		$sCharset = $oCurrentUser->getCharsetForExport();

		return $sCharset;
	}

	public function getWDBasicObject($aSelectedIds) {

		$oEntity = $this->_getWDBasicObject($aSelectedIds);
		
		return $oEntity;
	}

	/**
	 * Fehler-Dialog generieren
	 *
	 * Aus der Ext_Thebing_Gui2_Data hierhin verschoben!
	 *
	 * @param string $sHtml
	 * @param string $sDialogTitle
	 * @param string $sDialogIdTag
	 * @param string $sDialogId
	 * @return Ext_Gui2_Dialog
	 */
	public function getErrorDialog($sHtml, $sDialogTitle = '', $sDialogIdTag=null, $sDialogId=null){

		if($sDialogTitle == '' || is_null($sDialogTitle)) {
			$sDialogTitle = L10N::t('Ein Fehler ist aufgetreten!');
		}

		if(!$sDialogIdTag) {
			$sDialogIdTag = 'ERROR_';
		}

		$sErrorTitle = L10N::t('Fehler');

		$oErrorDialog = $this->_oGui->createDialog($sDialogTitle,$sErrorTitle,$sErrorTitle);

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->setElement($sHtml);

		$oErrorDialog->setElement($oDiv);
		$oErrorDialog->save_button = false;
		$oErrorDialog->sDialogIDTag = $sDialogIdTag;

		$oErrorDialog->width = 500;
		$oErrorDialog->height = 300;

		if($sDialogId) {
			$oErrorDialog->id = $sDialogIdTag;
		}

		return $oErrorDialog;

	}
	
	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @return bool
	 */
	public function checkDialogAccess(&$oDialog, $aSelectedIds) {
		global $_VARS;
		
		$bAccess = parent::checkDialogAccess($oDialog, $aSelectedIds);

		if(
			$bAccess === true &&
			$_VARS['action'] === 'edit'
		) {
			$oAccess = Access::getInstance();
		
			$aAccess = $this->_oGui->access;
			
			if(
				$oAccess instanceof Access_Backend &&
				$oAccess->checkValidAccess() === true &&
				!empty($aAccess) &&
				is_array($aAccess) &&
				count($aAccess) == 2
			) {
				$sArea = $aAccess[0];
				$bEdit = $oAccess->hasRight(array($sArea, 'edit')); 
				$bEditOwn = $oAccess->hasRight(array($sArea, 'edit_own')); 
				
				if(
					!$bEdit &&
					$bEditOwn
				) {
					$bAccess = $this->checkCreditorEditDialogAccess($oDialog, $aSelectedIds);
				}
			}
		}
		
		return $bAccess;
	}
	
	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aSelectedIds
	 * @return boolean
	 */
	protected function checkCreditorEditDialogAccess(Ext_Gui2_Dialog $oDialog, array $aSelectedIds) {
		
		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		if(
			$this->oWDBasic &&
			$this->oWDBasic->getId() > 0
		) {			
			$aData = $this->oWDBasic->getData();
			
			if(
				isset($aData['creator_id']) &&
				$aData['creator_id'] > 0 &&
				$aData['creator_id'] != Ext_TC_System::getCurrentUser()->getId()
			) {
				$oDialog->bReadOnly = true;
				return false;
			}
		}		
		
		$oDialog->bReadOnly = false;
		
		return true;
	}
	
	public function setParentGuiWherePartByRef(&$aSqlParts, &$aSql) {

		parent::setParentGuiWherePartByRef($aSqlParts, $aSql);

		// Falls man Flex-Felder in der Suche hat, muss die Flex-Tabelle pro verwendetem Filter gejoint werden.
		if(!$this->_oGui->checkWDSearch()){

			foreach($this->_oGui->aFlexJoins as $sFilterId=>$aFlexJoin) {

				if(
					$this->_aFilter[$sFilterId] !== '' &&
					$this->_aFilter[$sFilterId] != 'xNullx'
				) {
					$sPrimaryKeyAlias = (!empty($aFlexJoin['primary_key_alias'])) ? $aFlexJoin['primary_key_alias'] : $this->_oGui->query_id_alias;
					$sPrimaryKey = (!empty($aFlexJoin['primary_key'])) ? $aFlexJoin['primary_key'] : $this->_oGui->query_id_column;

					$aSqlParts['from'] .= " LEFT JOIN 
						`tc_flex_sections_fields_values` `flex_".(int)$aFlexJoin['field_id']."` ON 
							`flex_".(int)$aFlexJoin['field_id']."`.`field_id` = ".(int)$aFlexJoin['field_id']." AND 
							`".DB::escapeQueryString($sPrimaryKeyAlias)."`.`".DB::escapeQueryString($sPrimaryKey)."` = `flex_".(int)$aFlexJoin['field_id']."`.`item_id` 
					";
				}

			}
		}
		
	}
	
}
