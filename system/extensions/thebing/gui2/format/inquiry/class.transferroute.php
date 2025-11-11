<?php

class Ext_Thebing_Gui2_Format_Inquiry_TransferRoute extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sStart = $this->_getLabel($aResultData['transfer_start_location']);
		$sEnd = $this->_getLabel($aResultData['transfer_end_location']);

		$sRoute = '';

		if(!empty($sStart)) {
			$sRoute .= $sStart;
		}

		if(
			!empty($sStart) &&
			!empty($sEnd)
		) {
			$sRoute .= ' - ';
		}

		if(!empty($sEnd)) {
			$sRoute .= $sEnd;
		}

		return $sRoute;

	}

	protected function _getLabel($sLabel) {
		switch($sLabel) {
			case 'school':
				$sLabel = L10N::t('Schule');
				break;
			case 'accommodation':
				$sLabel = L10N::t('Unterkunft');
				break;
			default:
				break;
		}
		return $sLabel;
	}

}
