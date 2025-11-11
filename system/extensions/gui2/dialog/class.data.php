<?php

/**
 * Dialog Data
 * 
 * Enthält Dialog-spezifische Methoden, die aus der Ext_Gui2_Data extrahiert wurden.
 *
 * @see Ext_Gui2_Data
 */

class Ext_Gui2_Dialog_Data {
	
	/**
	 * @var WDBasic
	 */
	protected $_oWDBasic;

	/**
	 * @var WDBasic
	 */
	protected $_sWDBasic;

	protected $_aInitData = array();

	public $bDebugmode = false;

	/**
	 * @var Ext_Gui2
	 */
	protected $_oGui;

	/**
	 * @var Ext_Gui2_Dialog
	 */
	protected $_oDialog;

	/**
	 * @var array
	 */
	protected $aFieldsJoinTables = array();

	/**
	 * @var Gui2\Handler\Upload[]
	 */
	protected $aSaveUploadedFiles = [];

	public function save($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true){
		$aTransfer = array();

		/**
		 * Switch standard actions
		 * Bei neuem Eintrag werden die Selected IDs zurückgesetzt
		 */
		switch($sAction) {
			case 'new':
				// Ein neuer Eintrag darf die eine ID vorselektiert haben
				$aSelectedIds = array();
			case 'edit':
				
				$aTransfer = $this->_oGui->getDataObject()->saveEditDialogDataPublic((array)$aSelectedIds, $aData, $bSave, array('action' => $sAction, 'additional' => $sAdditional));
				break;
		}

		return $aTransfer;

	}

	public function getHtml($sAction, $aSelectedIds, $sAdditional = false){

		$aData = $this->_oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash ?? null);

		return $aData;

	}

	public function getWDBasic() {
		
		$bIssetWDBasic = $this->issetWDBasic();
		
		if($bIssetWDBasic) {
			 return $this->_sWDBasic;
		}
		
		$sWDBasic = $this->_oGui->class_wdbasic;
		return $sWDBasic;

	}
	
	public function issetWDBasic() {
		
		if(
			!empty($this->_sWDBasic) &&	
			class_exists($this->_sWDBasic)
		) {
			return true;
		}
		
		return false;
		
	}
	
	public function setWDBasic($sWDBasicName, $aInitData = array()) {
		
		$this->_sWDBasic = $sWDBasicName;
		$this->_aInitData = $aInitData;

		$this->resetWDBasicObject();
		
		$bIssetWDBasic = $this->issetWDBasic();

		return $bIssetWDBasic;
	}	

	public function addInitData($sKey, $mValue) {
		$this->_aInitData[$sKey] = $mValue;
	}
	
	public function setWDBasicObject($oWDBasic) {
		if(is_a($oWDBasic, $this->_sWDBasic)) {
			$this->_oWDBasic = $oWDBasic;	
		} else {
			throw new Exception('Object is not instance of '.$this->_sWDBasic.'!');
		}
	}

	public function resetWDBasicObject() {
		$this->_oWDBasic = null;
	}
	
	public function setDialogObject($oDialog) {
		
		$this->_oDialog = $oDialog;
		
	}
	
	public function setGuiObject($oGui) {
		$this->_oGui = $oGui;
	}

	public function getGui() {
		return $this->_oGui;
	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveData
	 * @param bool $bSave
	 * @param string $sAction
	 * @param bool $bPrepareOpenDialog
	 * @return array
	 * @throws Exception
	 */
	public function saveEdit(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		$this->aSaveUploadedFiles = [];

		$aSaveData = (array)$aSaveData;
		
		$sAdditional = '';

		if(is_array($sAction)) {
			$sAdditional = $sAction['additional'];
			$sAction = $sAction['action'];
		}

		$aRealSelectedIds = (array)$this->_oGui->decodeId($aSelectedIds, $this->_oGui->query_id_column);
		$aOldSelectedIds = $aSelectedIds;

		$bSuccess = false;
		$sDialogIDTag = 'ID_';

		$bResetJoinedObjectContainerIds = false;
		$aJoinedObjectCopyIds = array();
		$aJoinedObjectChilds = array();
		$aJoinedObjectContainerObjectLoaded = [];

		if($this->_oDialog->sDialogIDTag) {
			$sDialogIDTag = $this->_oDialog->sDialogIDTag;
		}

		$iID = reset($aRealSelectedIds);
		$iSelectedId = (int)$iID;

		// Objekt nur neu holen wenn noch nicht vorhanden
		if(
			is_null($this->_oWDBasic) ||
			!($this->_oWDBasic instanceof WDBasic)
		) {
			$this->_getWDBasicObject($aOldSelectedIds);
		}

		$bNewObject = false;
		if($this->_oWDBasic->id == 0) {
			$bNewObject = true;
		}
		
		// TODO Hier wird nichts validiert, was jegliche eingebaute Validierung hinfällig macht
		if(isset($_VARS['clonedata'])) {

			$oCopy = $this->_oWDBasic->createCopy();

			// Neue IDs
			$aJoinedObjectCopyIds = $this->_oWDBasic->getJoinedObjectCopyIds();
			
			$this->_oWDBasic = $oCopy;
			$iSelectedId = (int)$this->_oWDBasic->id;
			
			// IDs von JoinedObjektContainern löschen
			$bResetJoinedObjectContainerIds = true;

		}

		$oWDBasic = &$this->_oWDBasic;
		/** @var WDBasic $oWDBasic */

		if($this->bDebugmode){
			__pout('WDBasic Object');
			__pout($oWDBasic);
		}

		// Set the where data
		if(!empty($this->_aInitData)) {
			foreach((array)$this->_aInitData as $sField=>$mValue) {
				$aFieldIdentifier = Ext_Gui2_Data::getFieldIdentifier($sField);
				$sAlias = $aFieldIdentifier['alias'];
				$sColumn = $aFieldIdentifier['column'];
				// Wenn ein Operator gesetzt ist (=)
				if(!is_array($mValue)) {
					$oWDBasic->getJoinedObject($sAlias)->$sColumn = $mValue;
				}
			}
		}

		if($this->bDebugmode){
			__pout($aSaveData);
		}

		$aTemp = array();

		// JoinedObject Array vorbereiten
		$aJoinedObjectContainer = array();

		if(is_array($aSaveData['joined_object_container_hidden'] ?? null)) {

			foreach($aSaveData['joined_object_container_hidden'] as $iObjectKey => $aJoins) {

				foreach($aJoins as $sJoinKey => $iValue) {

					$iNewId = $iObjectKey;
					
					// Bei "Als neuen Eintrag speichern" die IDs zurücksetzen
					if($bResetJoinedObjectContainerIds === true) {
						if(isset($aJoinedObjectCopyIds[$sJoinKey][$iObjectKey])) {
							$iNewId = (int)$aJoinedObjectCopyIds[$sJoinKey][$iObjectKey];
						}
					}

					$aJoinedObjectContainer[$sJoinKey][] = array(
						'id' => $iNewId,
						'original_id' => $iObjectKey,
						'active' => $iValue
					);
					
					$aTemp[] = $iObjectKey;

				}

			}

		}

		$aUploadDataCache = array();
		$aUploadDeleteFiles = array();
		$aMovedUploads = array();

		// Alle verwendeten JoinTables ($aOption['jointable']) müssen nach Verwendung gemerkt werden
		$this->aFieldsJoinTables = array();
		
		// Hier merken, in welchen Containern welche Objekte Parent/Child sind
		$aJoinedContainerObjects = array();

		foreach((array)$this->_oDialog->aSaveData as $aOption) {

			/*
			 * Multiselects: Leere Multiselects werden nicht in $_VARS mitgeschickt
			 * Passiert für Flex-Felder in Ext_TC_Gui2_Data::saveEditDialogDataFlex()
			 * @todo Das ist doof, weil das auch der Fall ist, wenn das Feld deaktiviert ist, aber Werte vorhanden sind!
			 */
			if(
				!empty($aOption['multiple']) &&
				!isset($aSaveData[$aOption['db_column']])
			) {
				$aSaveData[$aOption['db_column']] = array();
			}

			$sColumn = $aOption['db_column'];
			$sAlias = $aOption['db_alias'];
			$sFieldIdentifier = Ext_Gui2_Data::setFieldIdentifier($sColumn, $sAlias);

			$this->_oDialog->aLabelCache[$sFieldIdentifier] = $aOption['label'];

			if(
				(
					!array_key_exists($sColumn, $aSaveData) ||
					$sColumn == ''
				) &&
				!isset($aOption['upload'])
			) {
				continue;
			}

			if(empty($sAlias)) {
				$aValues = $aSaveData[$sColumn];
			} else if(!empty($aOption['i18n'])) {
				$aValues = $aSaveData[$sColumn][$sAlias][$aOption['i18n_language']] ?? [];
			} else {
				$aValues = null;
				if(isset($aSaveData[$sColumn][$sAlias])) {
					$aValues = $aSaveData[$sColumn][$sAlias];
				}
			}

			// TODO: Ich habe hier die foreach über die Values entfernt. Wozu war die foreach gut?
			$mValue = $aValues;

			if(!$sAlias) {
				$sAlias = '';
			}

			if(isset($aOption['format'])) {

				$oObject = null;

				if($aOption['format'] instanceof Ext_Gui2_View_Format_Interface) {
					$oObject = $aOption['format'];
				} elseif(is_string($aOption['format'])) {
					$sTempView = 'Ext_Gui2_View_Format_'.$aOption['format'];
					$oObject = new $sTempView();
				}

				// Wert immer formatieren, auch wenn er leer ist (wichtig für Felder mit NULL-Werten)
				if($oObject) {

					// Joined Object Values
					if(!empty($aOption['joined_object_key'])) {
						if (is_array($mValue)) {
							foreach ($mValue as &$aJoin) {
								if (is_array($aJoin)) {
									foreach ($aJoin as &$mJoinValue) {
										if(
											// z.b. Multi-Rows
											isset($aOption['jointable']) &&
											$aOption['jointable'] === true &&
											is_array($mJoinValue)
										) {
											foreach ($mJoinValue as &$mJoinTableValue) {
												$mJoinTableValue = $oObject->convert($mJoinTableValue);
											}
										} else {
											$mJoinValue = $oObject->convert($mJoinValue);
										}
									}
								}
							}
						}
					} else if(
						// z.b. Multi-Rows
						isset($aOption['jointable']) &&
						$aOption['jointable'] === true &&
						is_array($mValue)
					) {
						foreach ($mValue as &$mJoinTableValue) {
							$mJoinTableValue = $oObject->convert($mJoinTableValue);
						}
					} else {
						$mValue = $oObject->convert($mValue);
					}

				}
			}

			// Keine skalaren Werte bei joinedobjects zulassen
			if(
				!empty($aOption['joined_object_key']) &&
				!is_array($mValue)
			) {
				$mValue = array();
			}

			// Wenn JoinedObject-Container
			if(!empty($aOption['joined_object_key'])) {

				// Referenzen löschen, da $aJoin schon weiter oben mit Referenz benutzt wird
				unset($aJoin);
				unset($mJoinValue);

				foreach((array)$aJoinedObjectContainer[$aOption['joined_object_key']] as $aElement) {

					$iElementId	= $aElement['id'];
					$iOriginalElementId	= $aElement['original_id'];					
					$iActive = $aElement['active'];
					$mJoinValue	= $mValue[$iOriginalElementId][$aOption['joined_object_key']];

					$aJoinedObjectConfig = $oWDBasic->getJoinedObjectConfig($aOption['joined_object_key']);
					$aAliasJoinedObjectConfig = $oWDBasic->getJoinedObjectConfig($aOption['db_alias']);
					$aAliasJoinTableData = $oWDBasic->getJoinTable($aOption['db_alias']);

					if(
						!isset($aJoinedObjectChilds[$aOption['joined_object_key']]) &&
						$aJoinedObjectConfig['type'] == 'child'
					) {
						$aJoinedObjectChilds[$aOption['joined_object_key']] = array();
					} else if(
						empty($aJoinedObjectConfig) &&
						$iElementId <= 0
					) {
						$iElementId = $iElementId - 1;
					}

//					if($iActive) {

						// Neues Element
						if($iElementId < 0) {
							$iChildId = 0;
						} else {
							$iChildId = $iElementId;
						}
						
						// Schauen ob es für den Alias evt eine JoinTableObject gibt
						// wenn ja nehme den ersten Eintrag damit wir auch felder auf jointableobject setzten können
						// wenn die Zwischentabelle nur eine 1 - 1 Beziehung ist
						if(
							empty($aAliasJoinedObjectConfig) &&
							!empty($aAliasJoinTableData) && 
							!empty($aAliasJoinTableData['class']) &&
							$aOption['db_alias'] != $aOption['joined_object_key']
						){
							
							$aSubChilds = $oWDBasic->getJoinTableObjects($aOption['db_alias']);
							if(!empty($aSubChilds)) {
								$oSubChild = reset($aSubChilds);
							} else {
								$oSubChild = $oWDBasic->getJoinTableObject($aOption['db_alias'], -1);
							}
							
							if($oSubChild->hasActiveField()) {
								$oSubChild->active = 1;
							}
							
						} else {
							$oSubChild = $oWDBasic;
						}

					if($iActive) {

						// Beim ersten Aufruf dieses Typs die Einträge zurücksetzen, damit nur gespeichert wird was auch übermittelt wurde
						if(!isset($aJoinedObjectContainerObjectLoaded[$aOption['joined_object_key']])) {
							if($aJoinedObjectConfig['type'] == 'child') {
								$oSubChild->cleanJoinedObjectChilds($aOption['joined_object_key']);
							} elseif(empty($aJoinedObjectConfig)) {
								$oAddedFrom = $oSubChild->getJoinedObject($aOption['db_alias']);
								$oAddedFrom->{$aOption['joined_object_key']} = [];
							}
							$aJoinedObjectContainerObjectLoaded[$aOption['joined_object_key']] = true;
						}
						
						if(
							!isset($aJoinedObjectChilds[$aOption['joined_object_key']][$iElementId]) &&
							$aJoinedObjectConfig['type'] == 'child'
						) {
							$aJoinedObjectChilds[$aOption['joined_object_key']][$iElementId] = $oSubChild->getJoinedObjectChild($aOption['joined_object_key'], $iChildId, $iElementId);
						}

						if($aJoinedObjectConfig['type'] == 'child') {
							$oAddedFrom = $aJoinedObjectChilds[$aOption['joined_object_key']][$iElementId];
							$oChild = $oAddedFrom->getJoinedObject($sAlias);
							if($oChild === $oAddedFrom) {
								$oAddedFrom = $oSubChild;
							}
							$sContainerType = 'joined_object_child';
						} else if(empty($aJoinedObjectConfig)) {
							$oAddedFrom = $oSubChild->getJoinedObject($aOption['db_alias']);
							$oChild = $oAddedFrom->getJoinTableObject($aOption['joined_object_key'], $iElementId);
							$sContainerType = 'joined_table_object';
						} else {
							$oAddedFrom = $oSubChild->getJoinedObject($aOption['db_alias']);
							$oChild = $oAddedFrom->getJoinedObject($aOption['joined_object_key']);
							$sContainerType = 'joined_object';
						}

						$this->setFieldValue($oChild, $sColumn, $mJoinValue, $sAlias, $aOption, $aElement);

						$sContainerKey = $aOption['joined_object_key'];
						
						if(!isset($aJoinedContainerObjects[$sContainerKey])) {
							//Parent & Child merken für den ContainerSaveHandler
							$aJoinedContainerObjects[$sContainerKey] = array(
								'parent' => $oAddedFrom,
								'container_type' => $sContainerType,
								'container_options'	=> $aOption,
							);
						}
						
						if(!isset($aJoinedContainerObjects[$sContainerKey]['childs'])) {
							$aJoinedContainerObjects[$sContainerKey]['childs'] = array();
						}
						
						$aJoinedContainerObjects[$sContainerKey]['childs'][$iElementId] = $oChild;
						
					} else {
						// Hat aktuell nur Auswirkung in VueDialogData, denn ansonsten lässt sich der erste Eintrag nicht löschen (z.B. Payment Conditions in School)
						if ($aJoinedObjectConfig['type'] == 'child') {
							$oSubChild->cleanJoinedObjectChilds($aOption['joined_object_key']);
						}
					}
	
				}

			} else {
				$this->setFieldValue($oWDBasic, $sColumn, $mValue, $sAlias, $aOption);
			}

		}

		if($bSave) {

			/**
			* Falls SaveHandler definiert sind für die Container, dann ausführen
			*/
			foreach($aJoinedContainerObjects as $sContainerKey => $aJoinedContainerObject) {

				//Container laden
				$oContainer = $this->_oDialog->getJoinedObjectContainer($sContainerKey);

				$oContainerSaveHandler = $oContainer->getSaveHandler();

				if(
					is_object($oContainerSaveHandler) &&
					$oContainerSaveHandler instanceof Ext_Gui2_Dialog_Container_Save_Handler_Abstract
				) {

					//Falls ein SaveHandler für den Container definiert wurde
					$oParent = $aJoinedContainerObject['parent'];

					$oContainerSaveHandler->setParentObject($oParent);
					$oContainerSaveHandler->setContainerType($aJoinedContainerObject['container_type']);
					$oContainerSaveHandler->setContainerOptions($aJoinedContainerObject['container_options']);

					foreach($aJoinedContainerObject['childs'] as $iElementId => $oChild)  {

						//Kind Objekt setzen
						$oContainerSaveHandler->setChildObject($oChild);

//						//Parent Objekt setzen
//						$oContainerSaveHandler->setParentObject($oParent);
//
//						//Typ setzen ob JoinedObjectChild,JoinTableObject oder JoinedObject
//						$oContainerSaveHandler->setContainerType($aJoinedContainerObject['container_type']);
//
//						//Container Optionen, für Informationen wie joinkeys
//						$oContainerSaveHandler->setContainerOptions($aJoinedContainerObject['container_options']);
//
						//Element Optionen, für Informationen wie child id
						$oContainerSaveHandler->setElementId($iElementId);

						//Aktionen ausführen
						$oContainerSaveHandler->handle();

					}

				}

			}

		}
		
		if($this->bDebugmode) {
			__pout($oWDBasic);
		}

		if($bSave) {

			// Elternverknüpfung abspeichern!
			$this->setForeignKey($oWDBasic);

			try {
				$mValidate = $oWDBasic->validate();
			} catch (\Core\Exception\Entity\ValidationException $e) {
				$mValidate = [$e];
			}

			try {
				$mValidateParents = $oWDBasic->validateParents();
			} catch (\Core\Exception\Entity\ValidationException $e) {
				$mValidateParents = [$e];
			}

			// Wenn es Eltern-Objekte mit Fehler gibt, dann Fehler weitergeben
			if(
				$mValidateParents !== true &&
				is_array($mValidateParents)
			) {
				if(is_array($mValidate)) {
					$mValidate = array_merge((array)$mValidate, $mValidateParents);
				} else {
					$mValidate = $mValidateParents;
				}
			}

			if($this->bDebugmode) {
				__pout('WDBasic Validate:');
				__pout($mValidate);
			}

			if(isset($_VARS['ignore_errors']) && $_VARS['ignore_errors'] == 1) {
				$mValidateHints	= true;
			} else {
				$mValidateHints	= $oWDBasic->checkIgnoringErrors();
			}

			$bSuccess = false;

			if(
				$mValidate === true &&
				$mValidateHints === true
			) {

				// Eventuelle Eltern-Objekte speichern
				// @TODO: Wieso passiert das überhaupt?
				$oWDBasic->saveParents();

				try {
					// Objekt speichern
					$bSuccess = $oWDBasic->lock()->save();
				} catch (\Core\Exception\Entity\EntityLockedException $e) {
					$bSuccess = [$e];
				}

				/*
				 * Objekt in Instanz-Cache packen, klappt nur wenn getInstance 
				 * nicht angeleitet ist
				 */
				$sClassName = get_class($oWDBasic);
				$sClassName::setInstance($oWDBasic);

				// Wenn null dann wurde die save abgeleitet aber kein Return wert definiert!
				// dadurch gehen unteranderen GUI Dialoge nicht korrekt!
				if($bSuccess === null) {
					Util::handleErrorMessage('Die WDBasic ('.get_class($oWDBasic).') hat die Save Methode abgeleitet OHNE Returnwert!');
				}

				// Wenn save nicht true zurückgibt und keine Instanz von WDBasic ist
				if(
					$bSuccess !== true &&
					!$bSuccess instanceof WDBasic
				) {

					// Wenn die Rückgabe ein Array ist, dann Fehlermeldungen zuordnen
					if(is_array($bSuccess)) {
						$mValidate = $bSuccess;
					} else {
						$mValidate = array();
					}
					$bSuccess = false;
					$aSelectedIds = $aOldSelectedIds;

				} else {

					$iIdColumn = $this->_oGui->query_id_column;
					
					if($this->_oGui->checkEncode()) {

						$aEncodeFields = (array)$this->_oGui->encode_data;
						$aEncodeData = array($iIdColumn => $oWDBasic->$iIdColumn);

						// Alle angegebenen Infos zur genierieten ID hinzufügen
						foreach($aEncodeFields as $sColumn) {

							// Wenn ID dann überspringe es, da ID automatisch selectiert wird
							if(
								$sColumn == $iIdColumn ||
								!is_string($sColumn)
							) {
								continue;
							}

							$sValue = $oWDBasic->$sColumn;
							$aEncodeData[$sColumn] = $sValue;

						}

						$iEncodedId = $this->_oGui->encodeData($aEncodeData);
						$aSelectedIds = array($iEncodedId);

						if($this->bDebugmode){
							__pout('WDBasic encode data:');
							__pout($aEncodeData);
							__pout($iEncodedId);
						}

					} else {
						$aSelectedIds = array($oWDBasic->$iIdColumn);
					}
				}

				if($this->bDebugmode){
					__pout('WDBasic Save:');
					__pout($bSuccess);
				}

				if($bSuccess) {

					if(!empty($this->aSaveUploadedFiles)) {

						foreach($this->aSaveUploadedFiles as $oSaveUploadedFile) {
							$oSaveUploadedFile->handle();
						}
						$oPersister = \WDBasic_Persister::getInstance();
						$oPersister->save();
						
						$this->aSaveUploadedFiles = [];
						
					}

				}

				if(
					$bSuccess &&
					$this->_oGui->checkWDSearch() &&
                    !$this->_oGui->wdsearch_use_stack
				) {
					$iIdColumn = $this->_oGui->query_id_column;
					$this->_oGui->getDataObject()->writeWDSearchIndexChange('_uid', $oWDBasic->$iIdColumn);
				}

			}

			// Sortierung bei neuem Eintrag immer neu speichern, damit es kein position = 0 gibt #8330
			if(
				$bSuccess &&
				$bNewObject &&
				$this->_oGui->row_sortable
			) {

				$aIds = [];
				$aTableData = $this->_oGui->getTableData();

				foreach($aTableData['body'] as $aRow) {
					if($aRow['id'] != $this->_oWDBasic->id) {
						$aIds[] = $aRow['id'];
					}
				}

				if(
					get_class($this->_oWDBasic) == $this->_oGui->class_wdbasic ||
					$this->_oWDBasic instanceof $this->_oGui->class_wdbasic
				) {
					$aIds[] = $this->_oWDBasic->id;
				}
				
				$this->_oGui->getDataObject()->saveNewSort($aIds);
			}
			
		}
		
		if(
			$bSave &&
			!$bSuccess &&
			$this->_oGui->getDataObject()->getSaveAsNewOption()
		) {
			$aSelectedIds = $this->_oGui->getDataObject()->getSaveAsNewOption();
		}

		if(
			$this->_oGui->getDataObject()->getOpenNewDialogOption() && 
			$bSave && 
			$bSuccess
		) {

			$this->_oWDBasic = null;

			// Abwärtskompatibilität
			$this->_oGui->getDataObject()->oWDBasic = $this->_oWDBasic;
			
			$aData = $this->_oGui->getDataObject()->prepareOpenDialog('new', array(), false, $sAdditional, true);
			$aData['id'] = $sDialogIDTag . '0';

		} else {

			// Wenn kein Fehler -> Token neu generieren
			$bSaveSuccess = true;
			if(!empty($aErrorsAll)) {
				$bSaveSuccess = false;
			}
			
			// Abwärtskompatibilität
			$this->_oGui->getDataObject()->oWDBasic = $this->_oWDBasic;

			$aData = array();
			if($bPrepareOpenDialog) {
				$aData = $this->_oGui->getDataObject()->prepareOpenDialog($sAction, $aSelectedIds, false, $sAdditional, $bSaveSuccess);
			}

			$aData['id'] = $sDialogIDTag.'0';
			if(!empty($aSelectedIds)) {
				$aData['id'] = $sDialogIDTag.implode('_', (array)$aSelectedIds);
			}

		}

		$aErrorsAll = array();

		if($bSave && !$bSuccess) {
			$aErrors = $this->_oGui->getDataObject()->getErrorData($mValidate, array('action'=>$sAction, 'additional'=>$sAdditional), 'error', true);
			$aErrorsHints = $this->_oGui->getDataObject()->getErrorData($mValidateHints, array('action'=>$sAction, 'additional'=>$sAdditional), 'hint', true);
			if(!empty($aData) && empty($aErrors)) {
				$aData['show_skip_errors_checkbox'] = 1;
			}
			$aErrorsAll	= array_merge($aErrors, $aErrorsHints);
		}

		// Alte Upload-Dateien löschen
		if(empty($aErrorsAll)) {
			foreach($aUploadDeleteFiles as $sUploadDeleteFile) {
				if(is_file($sUploadDeleteFile)) {
					unlink($sUploadDeleteFile);
				}
			}
		}

		// Aufgelaufene Fehler ergänzen
		Error_Handler::mergeErrors($aErrorsAll, $this->_oGui->gui_description);

		$aTransfer = array();
		$aTransfer['action'] = 'saveDialogCallback';
		$aTransfer['dialog_id_tag']	= $sDialogIDTag;
		$aTransfer['error'] = $aErrorsAll;
		$aTransfer['data'] = $aData;
		$aTransfer['save_id'] = reset($aSelectedIds);

		// Wenn encode an ist muss die ID des Dialoges aktualisiert werden
		if(
			$this->_oGui->checkEncode() ||
			(empty($aErrors) && $this->_oGui->getDataObject()->getSaveAsNewOption()) ||
			(empty($aErrors) && $this->_oGui->getDataObject()->getOpenNewDialogOption())
		) {
			if($this->_oGui->getDataObject()->getSaveAsNewOption()) {
				// Read cached current ID(s) - old ID(s)
				$aOldSelectedIds = $this->_oGui->getDataObject()->getSaveAsNewOption();
			}
			if($this->_oGui->getDataObject()->getOpenNewDialogOption()) {
				// Reset this setting
				$this->_oGui->getDataObject()->setOpenNewDialogOption(false);
			}
			$aTransfer['data']['force_new_dialog'] = true;
			$aTransfer['data']['old_id'] = $aTransfer['dialog_id_tag'].implode('_', (array)$aOldSelectedIds);
		}

		return $aTransfer;
	}

	/**
	 * @param WDBasic $oEntity
	 * @param string $sColumn
	 * @param string|int|float|array $mValue
	 * @param string $sAlias
	 * @param array $aOption
	 * @param array $aElement
	 */
	private function setFieldValue(WDBasic $oEntity, $sColumn, $mValue, $sAlias, array $aOption, array $aElement=null) {

		// Wenn GUI-Value-Handling übersprungen werden soll, in dieser Methode einfach nichts machen
		if(!empty($aOption['skip_value_handling'])) {
			return;
		}

		if(isset($aOption['i18n']) && $aOption['i18n'] == 1) {
			
			// Join Table alias zusammenbauen
			$sLanguageJoinTable = $sAlias;
			// Daten aus der Zwischentabelle holen
			$aLanguageData = $oEntity->$sLanguageJoinTable;
			// Default entry
			$aTemp = array(
					'language_iso' => $aOption['i18n_language'],
					$aOption['i18n_parent_column'] => $oEntity->id
				);
			// Durchlaufen und den aktuellen eintrag ( falls vorhanden ) unsetten
			foreach((array)$aLanguageData as $iLKey => $aLangData){
				if($aLangData['language_iso'] == $aOption['i18n_language']){
					$aTemp = $aLangData;
					unset($aLanguageData[$iLKey]);
				}
			}

			// Jetzt den aktuellen Part wieder ergänzen
			$aTemp[$sColumn] = $mValue;
			$aLanguageData[] = $aTemp;
			$oEntity->$sLanguageJoinTable = $aLanguageData;

		} elseif(
			isset($aOption['jointable']) &&
			$aOption['jointable'] === true
		) {
			
			$sJoinTableKey = get_class($oEntity).'.'.$sAlias;

			// Beim ersten Aufruf leeren
			if(!isset($this->aFieldsJoinTables[$sJoinTableKey])) {
				$oEntity->$sAlias = array();
				$this->aFieldsJoinTables[$sJoinTableKey] = true;
			}

			$aJoinTableData = $oEntity->$sAlias;

			$aValues = (array)$mValue;
			
			foreach($aValues as $iJoinTableKey=>$sValue) {
				$aJoinTableData[$iJoinTableKey][$sColumn] = $sValue;
			}
			
			$oEntity->$sAlias = $aJoinTableData;

		} else {

//			$oObject = $oEntity->getJoinedObject($sAlias);
			$oObject = $this->getWDBasicJoinedObject($oEntity, $aOption);

			if(!empty($aOption['upload'])) {

				$mValue = $this->setUploadFieldValue($sColumn, $sAlias, $aOption, $oObject, $mValue, $aElement);

			}

			$oObject->$sColumn = $mValue;
		}

	}

	/**
	 * @param string $sColumn
	 * @param array $aOption
	 * @param $oObject
	 * @param mixed $mValue
	 * @param array $aElement
	 * @return mixed $mValue
	 */
	private function setUploadFieldValue(string $sColumn, $sAlias, array $aOption, $oObject, $mValue, array $aElement=null) {

		$oRequest = $this->_oGui->getRequest();

		// Neue Uploads
		$aFiles = $oRequest->file('save');

		$aDelete = $oRequest->input('delete');

		$aFiles = $aFiles[$sColumn];
		$aDelete = $aDelete[$sColumn];

		if(!empty($sAlias)) {
			$aFiles = $aFiles[$sAlias];
			$aDelete = $aDelete[$sAlias];
		}

		if(!empty($aOption['joined_object_key'])) {
			$aFiles = $aFiles[$aElement['original_id']][$aOption['joined_object_key']];
			$aDelete = $aDelete[$aElement['original_id']][$aOption['joined_object_key']];
		}

		$aDelete = (array)$aDelete;

		if(
			!empty($aFiles) &&
			$aFiles instanceof \Illuminate\Http\UploadedFile
		) {
			$aFiles = [$aFiles];
		}

		if(!is_array($aFiles)) {
			$aFiles = [];
		}
		
		$oUpload = new Gui2\Handler\Upload($aFiles, $aOption, (bool)$aOption['multiple']);
		$oUpload->setEntity($oObject);
		$oUpload->setColumn($sColumn);
		$oUpload->setDelete($aDelete);
		$oUpload->setCurrent($mValue);

		$this->aSaveUploadedFiles[] = $oUpload;

		if(!empty($aFiles)) {

			if($aOption['multiple'] == 0) {

				$aFiles = [end($aFiles)];
				$mValue = $aFiles[0]->getClientOriginalName();

			} else {

				$mValue = (array)$mValue;
				foreach($aFiles as $oFile) {
					$mValue[] = $oFile->getClientOriginalName();
				}

			}

		}

		return $mValue;
	}
	
	public function getEdit($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		$aOriginalSelectedIds	= (array)$aSelectedIds;
		$aSelectedIds	= (array)$this->_oGui->decodeId((array)$aSelectedIds, $this->_oGui->query_id_column);
		$iSelectedId	= (int)reset($aSelectedIds);

		$aData = array();

		if(!empty($this->_oGui->_aTableData['table'])){
			if(is_string($this->_oGui->_aTableData['table'])){

				// Objekt nur neu holen wenn noch nicht vorhanden
				if(
					is_null($this->_oWDBasic) ||
					!($this->_oWDBasic instanceof WDBasic)
				) {
					$this->_getWDBasicObject($aOriginalSelectedIds);
				}

				$oWDBasic = &$this->_oWDBasic;

				// Set the where data
				if(!empty($this->_aInitData)) {
					foreach((array)$this->_aInitData as $sField=>$mValue) {
						$aFieldIdentifier = Ext_Gui2_Data::getFieldIdentifier($sField);
						$sAlias = $aFieldIdentifier['alias'];
						$sColumn = $aFieldIdentifier['column'];
						// Wenn ein Operator gesetzt ist (=)
						if(!is_array($mValue)) {
							$oWDBasic->getJoinedObject($sAlias)->$sColumn = $mValue;
						}
					}
				}

				// Elternverknüpfung abspeichern!
				$this->setForeignKey($oWDBasic);

				foreach((array)$aSaveData as $aOption) {
					
					$aTemp = $this->getEditFieldValue($aSelectedIds, $oWDBasic, $aOption);
					
					$aData[] = $aTemp;

				}

			} else {
				throw new Exception("Sorry, please overwrite the getEditDialogData Methode for complex dialog content");
			}
		} else {
			throw new Exception("Sorry, please set the Table Data");
		}
		
		if($this->bDebugmode){
			__pout($aData);
		}

		// Abwärtskompatibilität
		$this->_oGui->getDataObject()->oWDBasic = $this->_oWDBasic;

		return $aData;
	}

	public function getEditFieldValue(array $aSelectedIds, WDBasic $oWDBasic, array $aOption): array {

		$sValue = '';
		$aTemp = array();
		$aJoinedObjectChildsCache = array();

		if(!empty($aOption['joined_object_key'])) {

			$aJoinedObjectConfig		= $oWDBasic->getJoinedObjectConfig($aOption['joined_object_key']);
			$aAliasJoinedObjectConfig	= $oWDBasic->getJoinedObjectConfig($aOption['db_alias']);
			$aAliasJoinTableData		= $oWDBasic->getJoinTable($aOption['db_alias']);

			// Schauen ob es für den Alias evt eine JoinTableObject gibt
			// wenn ja nehme den ersten Eintrag damit wir auch felder auf jointableobject setzten können
			// wenn die Zwischentabelle nur eine 1 - 1 Beziehung ist
			if(
				empty($aAliasJoinedObjectConfig) &&
				!empty($aAliasJoinTableData) && 
				!empty($aAliasJoinTableData['class']) &&
				$aOption['db_alias'] != $aOption['joined_object_key']
			) {

				$aSubChilds = $oWDBasic->getJoinTableObjects($aOption['db_alias']);
				if(!empty($aSubChilds)) {
					$oSubChild = reset($aSubChilds);
				} else {
					$oSubChild = $oWDBasic->getJoinTableObject($aOption['db_alias'], -1);
				}

			} else {
				$oSubChild = $oWDBasic;
			}

			if($aJoinedObjectConfig['type'] == 'child') {
				$aChilds = (array)$oSubChild->getJoinedObjectChilds($aOption['joined_object_key'], true);
			} elseif(!empty($aJoinedObjectConfig)) {
				$aChilds = [$this->getWDBasicJoinedObject($oSubChild, $aOption)->getJoinedObject($aOption['joined_object_key'])];
			} else {
				$aChilds = (array)$this->getWDBasicJoinedObject($oSubChild, $aOption)->getJoinTableObjects($aOption['joined_object_key']);
			}

			$sValue = array();
			$iChildIndex = 0;

			foreach($aChilds as $iChildKey=>$oChild) {
				if($oChild->id > 0) {
					$iChildKey = $oChild->id;
				}

				$sJoinedObjectValue = '';
				if($aOption['i18n'] == 1) {

					$sJoinTable = $aOption['db_alias'];
					$aLanguageData = $oChild->$sJoinTable;

					foreach((array)$aLanguageData as $aLangData){
						if($aOption['i18n_language'] == $aLangData['language_iso']){
							$sJoinedObjectValue =  $aLangData[$aOption['db_column']];
							break;
						}
					}

				} elseif(
					isset($aOption['jointable']) &&
					$aOption['jointable'] === true
				) {
					$sJoinedObjectValue = $this->getEditJoinTableValues($oChild, $aOption['db_alias'], $aOption['db_column']);
				} else {
					$sJoinedObjectValue = $this->getWDBasicJoinedObject($oChild, $aOption)->{$aOption['db_column']};
				}

				$sValue[$iChildIndex] = array(
					'id'=>$oChild->id,
					'value'=>$sJoinedObjectValue,
					'key'=>$iChildKey,
					'index'=>$iChildIndex
				);

				$aJoinedObjectChildsCache[$iChildIndex] = $oChild;
				$iChildIndex++;
			}

		} elseif(isset($aOption['i18n']) && $aOption['i18n'] == 1) {

			if(empty($aOption['skip_value_handling'])) {
				$sJoinTable = $aOption['db_alias'];
				$aLanguageData = $oWDBasic->$sJoinTable;
				$sValue = "";
				foreach((array)$aLanguageData as $aLangData) {
					if($aOption['i18n_language'] == $aLangData['language_iso']){
						$sValue =  $aLangData[$aOption['db_column']];
						break;
					}
				}
			}

		} else {

			if(empty($aOption['skip_value_handling'])) {

				if(
					isset($aOption['jointable']) &&
					$aOption['jointable'] === true
				) {
					$sValue = $this->getEditJoinTableValues($oWDBasic, $aOption['db_alias'], $aOption['db_column']);
				} else {
					$sValue = $this->getWDBasicJoinedObject($oWDBasic, $aOption)->{$aOption['db_column']};
				}

			} else {
				if(isset($aOption['value'])) {
					// Wenn Wert vorhanden ist: Übernehmen
					// Es kann z.B. sein, dass die getEditSaveDialogData überschrieben wurde, die Werte aber vor dem parent-Aufruf gesetzt werden
					$sValue = $aOption['value'];
				}
			}

			// Checkboxen und Kalendar mit default Value
			if(
				(
					$aOption['element'] == 'checkbox' ||
					$aOption['element'] == 'calendar'
				) &&
				!empty($aOption['default_value']) &&
				$sValue == ''
			) {
				$sValue = $aOption['default_value'];
			}

		}

		if($aOption['element'] == 'autocomplete') {
			if($aOption['autocomplete'] instanceof Ext_Gui2_View_Autocomplete_Abstract) {
				$aTemp['autocomplete_label'] = $aOption['autocomplete']->getOption($aOption, $sValue);
			}
		}

		if(!empty($aOption['joined_object_key'])) {
			foreach ($sValue as &$aJoinValue) {
				if(
					// z.b. Multi-Rows
					isset($aOption['jointable']) &&
					$aOption['jointable'] === true &&
					is_array($aJoinValue['value'])
				) {
					foreach ($aJoinValue['value'] as &$mJoinTableValue) {
						$mJoinTableValue = Ext_Gui2_Data::executeFormat($aOption['format'] ?? null, $mJoinTableValue);
					}
				} else {
					$aJoinValue['value'] = Ext_Gui2_Data::executeFormat($aOption['format'] ?? null, $aJoinValue['value']);
				}
			}
		} else if(
			// z.b. Multi-Rows
			isset($aOption['jointable']) &&
			$aOption['jointable'] === true &&
			is_array($sValue)
		) {
			foreach ($sValue as &$mJoinTableValue) {
				$mJoinTableValue = Ext_Gui2_Data::executeFormat($aOption['format'] ?? null, $mJoinTableValue);
			}
		} else {
			$sValue = Ext_Gui2_Data::executeFormat($aOption['format'] ?? null, $sValue);
		}

		$sAlias = $aOption['db_alias'] ?? null;
		if(isset($aOption['i18n']) && $aOption['i18n'] == 1) {
			$sAlias .= ']['.$aOption['i18n_language'];
		}

		$aTemp['db_alias'] = (string)$sAlias;
		$aTemp['db_column'] = (string)$aOption['db_column'];
		$aTemp['id'] = (string)($aOption['id'] ?? ''); // Wert muss LEER sein, wenn es keine ID gibt
		$aTemp['joined_object_key']	= (string)($aOption['joined_object_key'] ?? '');
		$aTemp['joined_object_min']	= (int)($aOption['joined_object_min'] ?? 0);
		$aTemp['joined_object_max']	= (int)($aOption['joined_object_max'] ?? 0);
		$aTemp['joined_object_no_confirm'] = ($aOption['joined_object_no_confirm'] ?? null);
		$aTemp['jointable'] = $aOption['jointable'] ?? null;
		$aTemp['multi_rows'] = $aOption['multi_rows'] ?? null;
		$aTemp['value'] = $sValue;
		// Wenn vorhanden dann sind wir bei einem Select
		// da bei einer Selection sonst nichtsmehr ausgewählt wäre da der normale "default wert"
		// direkt in die options geschrieben wird und die Selection das überschreibt
		// muss hier der korrekte default wert genommen werden damit das weiterhin klappt
		if(isset($aOption['default_value'])) {
			$aTemp['default_value'] = $aOption['default_value'];
		} else {
			$aTemp['default_value'] = $aOption['value'] ?? null;
		}
		$aTemp['required'] = (int)($aOption['required'] ?? 0);
		$aTemp['select_options'] = array();
		$aTemp['force_options_reload'] = 0;							
		$aTemp['dependency_visibility'] = array();

		if(isset($aOption['dependency_visibility'])) {
			$aTemp['dependency_visibility'] = $aOption['dependency_visibility'];
		}

		if(isset($aOption['upload']) && $aOption['upload'] == 1) {
			$aTemp['upload_path'] = $aOption['upload_path'];
			$aTemp['no_cache'] = $aOption['no_cache'];
		}

		if(
			isset($aOption['selection_gui']) &&
			isset($aOption['selection_settings']) &&
			$aOption['selection_gui'] instanceof Ext_Gui2
		) {

			$sSelectionGuiAdditional = $aTemp['db_column'];
			if(!empty($aTemp['db_alias'])) {
				$sSelectionGuiAdditional .= '-'.$aTemp['db_alias'];
			}

			$aTemp['selection_gui'] = array(
				'hash' => $aOption['selection_gui']->hash,
				'additional' => $sSelectionGuiAdditional
			);

			$aTemp['force_options_reload'] = 1;

			$aSelectOptions = array();
			if(isset($aOption['selection_settings']['static_elements'])){
				$aSelectOptions = (array)$aOption['selection_settings']['static_elements'];
			}

			$aTemp['select_options'] = $aSelectOptions;

			$aTableData = $aOption['selection_gui']->_oData->getTableQueryData(array(), array(), array(), true);

			foreach((array)$aTableData['data'] as $aItemData) {
				$sOptionText = $aItemData[$aOption['selection_settings']['text_column']];

				if(isset($aOption['selection_settings']['text_format'])) {
					$sOptionText = Ext_Gui2_Data::executeFormat($aOption['selection_settings']['text_format'], $sOptionText, 'format', $oColumn, $aItemData);
				}

				$aTemp['select_options'][] = array('value'=>$aItemData[$aOption['selection_settings']['value_column']], 'text'=>$sOptionText);
			}

		}

		// Select Optionen aus Selection Klasse holen
		if(!empty($aOption['selection'])) {

			if(
				$aOption['selection'] instanceof Ext_Gui2_View_Selection_Interface
			) {
				$oObject = $aOption['selection'];
			} else if(
				is_string($aOption['selection'])
			) {
				$sTempSelection = 'Ext_Gui2_View_Selection_'.$aOption['selection'];
				$oObject = new $sTempSelection();
			}

			if($oObject instanceof Ext_Gui2_View_Selection_Interface) {

				// Gui-Objekt übergeben
				$oObject->setGui($this->_oGui);

				if(isset($aOption['dependency'])) {
					$aTemp['has_dependency'] = 1; // Für »Unbekannt«
				}

				if(
					isset($aOption['always_add_unknown_entries']) &&
					$aOption['always_add_unknown_entries'] == true
				) {
					$aTemp['always_add_unknown_entries'] = 1; // »Unbekannt«-Einträge auch bei Dependencies anzeigen
				}

				$aTemp['force_options_reload'] = 1;
				$aTemp['select_options'] = array();

				// Wenn man einen vorhandenen Eintrag auswählt und dann einen neuen erzeugt, sind noch die alten Werte im Objekt
				if ($oObject instanceof Ext_Gui2_View_Selection_Abstract) {
					$oObject->resetJoinedObject();
				}

				// Wenn es ein Joined Object ist und Werte vorhanden sind
				if(
					!empty($aOption['joined_object_key']) &&
					!empty($sValue)
				) {

					$oObject->sJoinedObjectKey = $aOption['joined_object_key'];

					$aTemp['joined_object_options'] = 1;
					foreach($sValue as $iKey=>$aItem) {
						$oObject->iJoinedObjectId	= $aItem['id'];
						$oObject->iJoinedObjectKey	= $aItem['key'];
						$oObject->oJoinedObject		= $aJoinedObjectChildsCache[$iKey];
						$aSelectOptions				= $oObject->getOptions($aSelectedIds, $aOption, $oWDBasic);
						$aSelectOptions				= $oObject->prepareOptionsForGui($aSelectOptions);
						$aTemp['select_options'][$aItem['key']] = $aSelectOptions;
					}

				} else {

					$aSelectOptions = $oObject->getOptions($aSelectedIds, $aOption, $oWDBasic);
					$aSelectOptions = $oObject->prepareOptionsForGui($aSelectOptions);
					$aTemp['select_options'] = $aSelectOptions;

					if(
						empty($aTemp['value']) &&
						method_exists($oObject, 'getDefaultValue')
					) {
						$aTemp['value'] = $oObject->getDefaultValue($oWDBasic);
					}

				}

			}

		}

		$this->_oGui->getDataObject()->modifiyEditDialogDataRow($aTemp);

					
		return $aTemp;
	}

	public function getWDBasicObject($aSelectedIds) {
		return $this->_getWDBasicObject($aSelectedIds);
	}

	/**
	 * Redundanz mit \Ext_Gui2_Data::_getWDBasicObject?
	 *
	 * @see \Ext_Gui2_Data::_getWDBasicObject()
	 */
	protected function _getWDBasicObject($aSelectedIds) {

		if($this->_oGui->encode_data_id_field != false) {
			$sIdField = $this->_oGui->encode_data_id_field;
		} else {
			$sIdField = $this->_oGui->query_id_column;
		}

		$aSelectedIds	= (array)$this->_oGui->decodeId($aSelectedIds, $sIdField);
		$iSelectedId	= (int)reset($aSelectedIds);
		$this->_oWDBasic = call_user_func(array($this->_sWDBasic, 'getInstance'), (int)$iSelectedId);

		$this->setForeignKey($this->_oWDBasic);

		return $this->_oWDBasic;

	}

	protected function getWDBasicJoinedObject(WDBasic $oEntity, array $aSaveField): WDBasic {

		return $oEntity->getJoinedObject($aSaveField['db_alias']);

	}

	public function getErrorData($aErrorData, $sType, $bShowTitle) {

		if(!is_array($aErrorData)){
			return array();
		}
		
		if(is_object($this->_oDialog)){
			$aLabelCache	= $this->_oDialog->aLabelCache;
			if(empty($aLabelCache)) {
				foreach((array)$this->_oDialog->aSaveData as $aOption) {

					$sColumn = $aOption['db_column'];
					$sAlias = $aOption['db_alias'];
					$sFieldIdentifier = Ext_Gui2_Data::setFieldIdentifier($sColumn, $sAlias);

					$this->_oDialog->aLabelCache[$sFieldIdentifier] = $aOption['label'];
				}
				$aLabelCache	= $this->_oDialog->aLabelCache;
			}
		}

		$aErrors = array();

		if(
			!empty($aErrorData) && 
			$sType == 'error' &&
			$bShowTitle === true
		) {
			$aErrors[] = L10N::t('Fehler beim Speichern', Ext_Gui2::$sAllGuiListL10N);
		}
 
		foreach((array)$aErrorData as $sField=>$aError) {
			$aError = is_object($aError) ? [$aError] : (array)$aError; // Alte Logik: Alles wird iteriert
			foreach($aError as $sError) {
				if(array_key_exists($sField, $aLabelCache)) {
					$sLabel = $aLabelCache[$sField];
				} else {
					$sLabel = '';
				}

				/*
				 * Das hier ist eigentlich falsch. Die getErrorMessage muss direkt auf $this aufgerufen werden. 
				 * Das kann aber nicht so easy geändert werden, da die _getErrorMessage() im Data-Objekt schon sehr oft 
				 * abgeleitet ist.
				 */
				$sMessage = $this->_oGui->getDataObject()->getErrorMessage($sError, $sField, $sLabel, $this->_oDialog->action, $this->_oDialog->additional);
				
				/**
				 * Daher hier der Fallback!
				 */
				if($sMessage === $sError) {
					$sMessage = $this->getErrorMessage($sError, $sField, $sLabel, $this->_oDialog->action, $this->_oDialog->additional);
				}
				
				$aFieldIdentifier = Ext_Gui2_Data::getFieldIdentifier($sField);
				$aErrors[] = array(
					'type'			=> $sType, 
					'id'			=> $aFieldIdentifier['id'], 
					'identifier'	=> $aFieldIdentifier['identifier'], 
					'input'			=> array(
						'dbcolumn'		=> $aFieldIdentifier['column'], 
						'dbalias'		=> $aFieldIdentifier['alias']
					), 
					'message'		=> $sMessage,
					'error_id'		=> $aFieldIdentifier['error_id'],
				);
			}
		}

		return $aErrors;

	}
	
	public function getErrorMessage($sError, $sField, $sLabel='') {

		$sMessage = '';

		$oGui2Data = $this->_oGui->getDataObject();
		$sGui2DataClass = get_class($oGui2Data);
		
		$sMessage = $sGui2DataClass::convertErrorKeyToMessage($sError);

		$sMessage = L10N::t($sMessage, Ext_Gui2::$sAllGuiListL10N);
		if(!empty($sLabel)){
			$sMessage = sprintf($sMessage, $sLabel);
		}

		return $sMessage;

	}
	
	protected function setForeignKey(WDBasic $oWDBasic) {
		$this->_oGui->getDataObject()->setForeignKey($oWDBasic);
	}

	/**
	 * @param WDBasic $oWDBasic
	 * @param string $sDbAlias
	 * @param string $sDbColumn
	 * @return array
	 */
	protected function getEditJoinTableValues(WDBasic $oWDBasic, $sDbAlias, $sDbColumn) {

		$aValues = [];
		$aJoinTableData = array_values($oWDBasic->{$sDbAlias});
		foreach($aJoinTableData as $iJoinTableItem => $aJoinTableItem) {
			$aValues[$iJoinTableItem] = $aJoinTableItem[$sDbColumn];
		}

		return $aValues;

	}
	
}
