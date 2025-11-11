<?php

namespace Ts\Gui2\AccommodationProvider\Payment\Category;

class ValidityData extends \Ext_TC_Gui2_Data {

	/**
	 * {@inheritdoc}
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {
		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
		foreach((array)$aTransfer['error'] as $iKey => $mError) {
			if(
				is_array($mError) &&
				$mError['input']['dbalias'] == 'ts_appcv' &&
				$mError['input']['dbcolumn'] == 'valid_from'
			) {
				$aTransfer['error'][$iKey]['input']['dbalias'] = 'validity';
			}
		}
		return $aTransfer;
	}

}
