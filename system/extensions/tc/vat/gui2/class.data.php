<?php

class Ext_TC_Vat_Gui2_Data extends Ext_TC_Gui2_Data {

	/**
	 * ergänzt die dritte Zeile der GUI
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Bar $oBar
	 */
	public static function setAdditionalBarData(&$oGui, $oBar) {
		
	}
	
	/**
	 * See parent
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null) {

		$sMessage = '';

		switch($sError) {
			case 'INVALID_DATE_TAX':
				$sMessage = 'Änderungen am Steuersatz werden für bestehende Rechnungen und Verbuchungen in der Buchhaltung nicht übernommen.';
				$sMessage = $this->t($sMessage);
				break;
			default:
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel);
				break;
		}

		return $sMessage;

	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		if(!empty($aTransfer['error'])) {
			foreach($aTransfer['error'] as $iKey => $aError) {
				if(
					!is_array($aError) ||
					!isset($aError['input']) ||
					!is_array($aError['input']) ||
					!isset($aError['input']['dbalias']) ||
					!isset($aError['input']['dbcolumn'])
				) {
					continue;
				}
				if(
					$aError['input']['dbcolumn'] === 'valid_from' &&
					$aError['input']['dbalias'] === 'tc_vrv'
				) {
					$aTransfer['error'][$iKey]['input']['dbalias'] = 'validity';
				}
			}
		}

		return $aTransfer;

	}

}
