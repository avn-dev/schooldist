<?php

class Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent extends Ext_Gui2_View_Format_Abstract {

	protected $sLabelNotAvailable;

	protected $iDecimalPlaces = null;

	/**
	 * @param string|Tc\Service\LanguageAbstract $oLanguage
	 * @param int $iDecimalPlaces
	 */
	public function __construct($oLanguage = null, $iDecimalPlaces = null) {
		$oLanguage = Ext_TC_Util::getLanguageObject($oLanguage, Ext_Thebing_Tuition_Gui2_Attendance_Gui2::TRANSLATION_PATH);
		$this->sLabelNotAvailable = $oLanguage->translate('N/A');
		$this->iDecimalPlaces = $iDecimalPlaces;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		if($mValue !== null) {
			return Ext_Thebing_Format::Number($mValue, false, (int)$aResultData['school_id'], true, $this->iDecimalPlaces).' %';
		} else {
			return $this->sLabelNotAvailable;
		}
	}

	public function align(&$oColumn = null) {
		return 'right';
	}

}
