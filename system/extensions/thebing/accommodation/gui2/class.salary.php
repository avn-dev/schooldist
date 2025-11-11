<?php

class Ext_Thebing_Accommodation_Gui2_Salary extends Ext_Thebing_Gui2_Data {

	/**
	 * {@inheritdoc}
	 */
	protected function _getErrorMessage($sError, $sField = '', $sLabel = '', $sAction = NULL, $sAdditional = NULL) {

		switch($sError) {
			case 'COSTCATEGORY_NOT_CHANGABLE':
				return $this->t('Es existieren noch Zahlungen zu dieser Kostenkategorie. Die Kostenkategorie darf nicht verändert werden.');
		}

		return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);

	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {
		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
		foreach((array)$aTransfer['error'] as $iKey => $mError) {
			if(!is_array($mError)) {
				continue;
			}
			if(
				$mError['input']['dbalias'] == 'kts' &&
				$mError['input']['dbcolumn'] == 'valid_from'
			) {
				$aTransfer['error'][$iKey]['input']['dbalias'] = '';
			} elseif(
				$mError['input']['dbalias'] == 'kts' &&
				$mError['input']['dbcolumn'] == 'salary'
			) {
				$aTransfer['error'][$iKey]['input']['dbalias'] = 'kas';
			}
		}
		return $aTransfer;
	}

}
