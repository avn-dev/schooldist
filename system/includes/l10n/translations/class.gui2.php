<?php

class L10N_Translations_Gui2 extends Ext_Gui2_Data {

	public function  switchAjaxRequest($_VARS) {

		if(
			$_VARS['task'] == 'request' &&
			$_VARS['action'] == 'set_use'
		) {

			foreach((array)$_VARS['id'] as $iId) {
				$oTranslation = L10N_BackendTranslations::getInstance((int)$iId);
				if($oTranslation->use == 1) {
					$oTranslation->use = 0;
				} else {
					$oTranslation->use = 1;
				}
				$oTranslation->save();
			}

			$aTransfer = array();
			$aTransfer['action'] 	= 'loadTable';
			$aTransfer['error'] 	= array();

			echo json_encode($aTransfer);
			$this->_oGui->save();
			die();

		} else {
			parent::switchAjaxRequest($_VARS);
		}
	}

	public function requestDeeplTranslation($aData) {
		
		$aSelectedIds = (array) $aData['id'];
		
		$aBackendLanguages = \System::getBackendLanguages();
		
		$oApi = new \Deepl\Api();
		
		foreach($aSelectedIds as $iId) {
			
			$oTranslation = \L10N_BackendTranslations::getInstance($iId);
			$aChangeData = [];

			foreach($aBackendLanguages as $aBackendLanguage) {
				
				$sBackendLanguage = $aBackendLanguage[0];
				
				if(!Deepl\Api\Object\Translation::checkValidLanguage($sBackendLanguage)) {
					continue;
				}
				
				$aData = $oTranslation->getData();
				
				if(isset($aData[$sBackendLanguage]) && empty($aData[$sBackendLanguage])) {
			
					$sTranslated = $oApi->translate('de', $sBackendLanguage, $oTranslation->code);
					
					$oTranslation->$sBackendLanguage = $sTranslated;
					$aChangeData[$sBackendLanguage] = $sTranslated;
					
					$oTranslation->setExternalService($sBackendLanguage, \Deepl\Api::SERVICE);
				}				
			}
			
			if(!empty($aChangeData)) {
				$oTranslation->save();
			}			
		}
	
		$aTransfer = [];
		$aTransfer['action'] = 'loadTable';
		
		return $aTransfer;
	}

	protected function requestTranslationVerifications($aData) {
		
		$aTransfer = [];
		$aTransfer['action'] = 'requestTranslationVerificationsCallback';
		
		$oTranslation = \L10N_BackendTranslations::getInstance(reset($aData['id']));
		
		$aTransfer['data']['external'] = $oTranslation->getExternalServices();
				
		return $aTransfer;
	}
	
	protected function requestVerifyTranslation($aData) {
		
		$aTransfer = [];
		$aTransfer['action'] = 'requestVerifyTranslationCallback';
		
		$oTranslation = \L10N_BackendTranslations::getInstance($aData['translation_id']);
		$bVerified = $oTranslation->verifyExternalTranslation($aData['language']);
		
		$oTranslation->save();
		
		$aTransfer['data']['id'] = (int) $aData['translation_id'];
		$aTransfer['data']['language'] = $aData['language'];
		$aTransfer['data']['verified'] = (int) $bVerified;
				
		return $aTransfer;
	}
	
}