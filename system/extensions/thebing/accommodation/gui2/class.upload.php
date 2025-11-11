<?php


class Ext_Thebing_Accommodation_Gui2_Upload extends Ext_Thebing_Gui2_Data {

	/**
	 * @todo Dateien nicht weiter löschen!
	 */
	public function deleteRowHook($iRowId){
		switch($this->_oGui->class_wdbasic){
			case 'Ext_Thebing_Accommodation_Upload':
				$oWDBasic = call_user_func(array($this->_oGui->class_wdbasic, 'getInstance'), (int)$iRowId, $this->_oGui->_aTableData['table']);
				@unlink($oWDBasic->getPath());
				break;
			default:
				break;
		}
	}

	protected function _getErrorMessage($sError, $sField = '', $sLabel = '', $sAction = NULL, $sAdditional = NULL) {

		switch ($sError){
			case 'INVALID_PDF':
				$sErrorMessage = Ext_Thebing_Util::getErrorMessageFileExtension('pdf', $this->_oGui->gui_description);
			break;
			case 'INVALID_IMAGE':
				$sErrorMessage = Ext_Thebing_Util::getErrorMessageFileExtension('image', $this->_oGui->gui_description);
			break;
			default:
				$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sErrorMessage;

	}

}
