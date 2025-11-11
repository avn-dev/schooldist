<?php 
class Ext_TC_Config_Gui2_Data extends Ext_TC_Gui2_Data {
	
	/*
	 *  Baut den Query zusammen und ruft die Daten aus der DB ab
	 */
	public function getTableQueryData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $bSkipLimit=false) {

		$aResult = array();
	
		$aConfig = $this->_oGui->aConfigurations;
		$oConfig = Ext_TC_Factory::getInstance('Ext_TC_Config');

		foreach((array)$aConfig as $sConfig => $aData){

			$aConfigData = $oConfig->get($sConfig);
			$mValue = $aConfigData['value'];

			if ($aData['type'] == 'gui') {
				if (System::d('debugmode') == 2) {
					$mValue = 'Debug: '.json_encode($mValue);
				} else {
					$mValue = '...';
				}

			}

			// Value umformatieren für Selects
			elseif($aData['type'] == 'select') {

				$this->setPossibleSeletionData($aData);

				// Werte formatieren für Multiselect
				if($aData['multiple']) {

					if (is_string($mValue)) {
						$mValue = unserialize($mValue);
					}

					$sTemp = '';

					foreach((array)$mValue as $mSelected){
						$sTemp .= $aData['select_options'][$mSelected];
						$sTemp .= ', ';
					}
					$sTemp = rtrim($sTemp, ', ');
					$mValue = $sTemp;

				}

				// Werte formatieren für einzelnes Select mit select_options
				else if(
					is_array($aData['select_options']) &&
					$aData['multiple'] <= 1
				) {
					$mValue = $aData['select_options'][$mValue];
				}

			}

			if(!empty($aData['format'])) {
				$mValue = $this->executeFormat($aData['format'], $mValue);
			}
			
			$aResult['data'][] = array(
				'id'			=> $aConfigData['id'],
				'key'			=> $sConfig,
				'description'	=> $aData['description'],
				'value'			=> $mValue
			);
			
		}

		return $aResult;

	}

	/**
	 * Prüft, ob das Feld eine Selection hat und wenn dies der Fall ist, hole die Werte und setze sie
	 * @param array $aData
	 */
	protected function setPossibleSeletionData(&$aData)
	{
		if(
			!empty($aData['selection']) &&
			$aData['selection'] instanceof Ext_Gui2_View_Selection_Abstract
		) {
			$oWDBasic = null;
			$aData['select_options'] = $aData['selection']->getOptions(array(), array(), $oWDBasic);
		}
	}

	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {
		
		$iSelectedId = reset($aSelectedIds);

		$aKey = $this->_oGui->decodeId($aSelectedIds, 'key');
		$sKey = reset($aKey);
		
		$aConfigs = $this->_oGui->aConfigurations;
		$aConfig = $aConfigs[$sKey];

		$oConfig = Ext_TC_Factory::getInstance('Ext_TC_Config');
		$aConfigData = $oConfig->get($sKey);
		
		$mValue = $aConfigData['value'];

		$aData = array(
			'db_column' => $sKey,
			'db_alias' => ''
		);
		
		if(
			is_string($mValue) &&
			$aConfig['type'] == 'select' && 
			$aConfig['multiple']
		) {

			$mValue = unserialize($mValue);

		} elseif($aConfig['type'] == 'upload') {
			
			$aSaveData = reset($aSaveData);
			$aData['upload_path'] = $aSaveData['upload_path'];
			
		}

		$aData['value'] = $mValue;
				
		if(!empty($aConfig['selection'])) {

			if(
				$aConfig['selection'] instanceof Ext_Gui2_View_Selection_Interface
			) {
				$oObject = $aConfig['selection'];
			} else if(
				is_string($aConfig['selection'])
			) {
				$sTempSelection = 'Ext_Gui2_View_Selection_'.$aConfig['selection'];
				$oObject = new $sTempSelection();
			}

			if($oObject instanceof Ext_Gui2_View_Selection_Interface) {

				$aData['force_options_reload'] = 1;
				$aData['select_options'] = array();
				$aSelectOptions = $oObject->getOptions($aSelectedIds, $aConfig, $oWDBasic);
				foreach((array)$aSelectOptions as $mKey=>$mValue) {
					$aData['select_options'][] = array('value'=>$mKey, 'text'=>$mValue);
				}
	
			}

		}
		
		return array($aData);

	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {

		$oConfig = Ext_TC_Factory::getInstance('Ext_TC_Config');

		$aTransfer = array();
		$aTransfer['error'] = array();
		$aTransfer['dialog_id_tag'] = 'ID_';
		$aTransfer['save_id'] = reset($aSelectedIds);

		if(is_array($sAction)) {
			$sAdditional = $sAction['additional'];
			$sAction = $sAction['action'];
		}
		
		if($this->aIconData[$sAction]) {
			$oDialogData = $this->aIconData[$sAction]['dialog_data'];
		}

		$iSelectedId = reset($aSelectedIds);

		foreach((array)$oDialogData->aSaveData as $aSaveField) {
			
			$sKey = $aSaveField['db_column'];
			$mValue = $aSaveData[$sKey];

			$mCheck = $this->_saveEditFieldCheck($aSaveField, $mValue);

			if($mCheck === true) {

				// Upload
				if($aSaveField['upload'] == 1) {

					if(is_file($_FILES['save']['tmp_name'][$sKey])) {

						$aUploadFileData = [];
						$aUploadFileData[] = new \Illuminate\Http\UploadedFile($_FILES['save']['tmp_name'][$sKey], $_FILES['save']['name'][$sKey]);
						
						$sUploadPath = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getSecureDirectory', array(false));
						
						$aOption = array(
							'db_column' => $sKey,
							'upload' => 1,
							'upload_path' => $sUploadPath,
							'add_id_filename' => false
						);

						$oHandler = (new Gui2\Handler\Upload($aUploadFileData, $aOption, false))
							->setEntity($oConfig, false);

						$mValue = $oHandler->handle();

					}

				}

				// Wert setzen
				try {
					$oConfig->set($sKey, $mValue);
				} catch (\DomainException $e) {
					$aTransfer['error'][] = $e->getMessage();
				}


			} else {
				$aTransfer['error'][] = $mCheck;
			}

		}

		if(empty($aTransfer['error'])) {
			$aTransfer['action'] = 'saveDialogCallback';
			$oConfig->save();
		} else {
			$aTransfer['action'] = 'showError';
			array_unshift($aTransfer['error'], L10N::t('Fehler beim Speichern', Ext_Gui2::$sAllGuiListL10N));
		}

		$aData = $this->prepareOpenDialog($sAction, $aSelectedIds);
		$aTransfer['data'] = $aData;

		return $aTransfer;

	}

	/**
	 * @TODO Sollte entfernt werden, da $_aFormat *existiert*
	 * @deprecated
	 *
	 * Methode, um Felder Prüfungen unterziehen zu können
	 *
	 * @param array $aSaveField
	 * @param mixed $mValue
	 * @return mixed
	 */
	protected function _saveEditFieldCheck($aSaveField, $mValue) {

		$mReturn = true;
		$sDbColumn = $aSaveField['db_column'];

		// Prüfen, ob Server diese Timezone unterstützt
		// MySQL, als Beispiel, kann andere Timezones haben oder sie sind gar nicht eingerichtet
		if(
			$sDbColumn === 'standardtimezone' |
			$sDbColumn === 'availabletimezones'
		) {

			$aTimeZones = (array)$mValue;
			$sErrorTimezone = null;

			foreach($aTimeZones as $sTimezone) {
				$bTimezoneSet = Ext_TC_Util::setTimezone($sTimezone);
				if(!$bTimezoneSet) {
					$sErrorTimezone = $sTimezone;
					break;
				}
			}

			if(!is_null($sErrorTimezone)) {
				$mReturn = array(
					'type' => 'error',
					'input' => array('dbcolumn' => $sDbColumn),
					'message' => $this->_getErrorMessage('NOT_SUPPORTED_TIMEZONE', '', $sErrorTimezone)
				);
			}

		}

		return $mReturn;
	}

	/**
	 *
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @return array 
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {
		
		$oDialogData = $this->_oGui->createDialog($this->t('Einstellung bearbeiten'));
		$oDialogData->width = 950;
//		$oDialogData->access = array($this->_oGui->sRight, 'edit');
		
		$aKey = $this->_oGui->decodeId($aSelectedIds, 'key');
		$sKey = reset($aKey);

		$aConfigs = $this->_oGui->aConfigurations;
		$aConfig = $aConfigs[$sKey];
		
		$sType = $aConfig['type'];
		$sDescription = $aConfig['description'];
		
		unset($aConfig['type']);
		unset($aConfig['description']);
		
		$aConfig['db_column'] = $sKey;
		if(!isset ($aConfig['required'])){
			$aConfig['required'] = 1;
		}

		if ($sType === 'gui') {
			$oDialogData->setElement($aConfig['gui']);
		} elseif($sType == 'upload') {
			$sDirectory = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getSecureDirectory', array(false));
			$oRow = new Ext_Gui2_Dialog_Upload($this->_oGui, $sDescription, $oDialogData, $sKey, '', $sDirectory);
			$oRow->bAddId2Filename = false;
		} else {
			if (isset($aConfig['form_text'])) {
				$aConfig['input_div_elements'] = ['<span class="help-block">'.$aConfig['form_text'].'</span>'];
			}

			$oRow = $oDialogData->createRow($sDescription, $sType, $aConfig);
		}
		$oDialogData->setElement($oRow);
		
		$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		return $aData;

	}

	 protected function _getErrorMessage($sError, $sField='', $sLabel='', $sAction=null, $sAdditional=null) {

		switch($sError) {

			case 'NOT_SUPPORTED_TIMEZONE':
				$sMessage = 'Die gewählte Zeitzone "%s" wird nicht vom Server unterstützt.';
				$sMessage = $this->t($sMessage);
				$sMessage = sprintf($sMessage, $sLabel);
				break;

			default:
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
				break;
			
		}

		return $sMessage;
	}	
	
}
