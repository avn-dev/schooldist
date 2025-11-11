<?php

class Ext_Thebing_Accommodation_Gui2_Room extends Ext_Thebing_Gui2_Basic_School {

	/**
	 * @var string
	 */
	protected $sSchoolField = '';

	/**
	 * @var string
	 */
	protected $sClientField = '';

	/**
	 * @param string $sError
	 * @param string $sField
	 * @param string $sLabel
	 * @param null $sAction
	 * @param null $sAdditional
	 * @return string
	 * @throws Exception
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {
		
		if($sError === 'ALLOCATIONS_EXISTS') {
			$sErrorMessage = 'Es befinden sich noch Zuweisungen zu diesem Raum.';
			$sErrorMessage = $this->t($sErrorMessage);
		} elseif($sError === 'COUNT_OF_BEDS_LOWER_THAN_BEFORE') {
			$sErrorMessage = $this->t('Die Anzahl der gesamten Betten ist kleiner als vorher.');
		} else {
			$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}
		
		return $sErrorMessage;
	}

}