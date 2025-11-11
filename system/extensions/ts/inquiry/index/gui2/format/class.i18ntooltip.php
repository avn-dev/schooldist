<?php

class Ext_TS_Inquiry_Index_Gui2_Format_I18NTooltip extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @var string
	 */
	protected $sType;

	public function __construct($sType) {
		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$sLang = $oSchool->getInterfaceLanguage();
		$this->sType = $sType.'_'.$sLang;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		return $mValue;
	}
	
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aReturn = [];
		$aReturn['content'] = (string)($aResultData[$this->sType] ?? '');
		$aReturn['tooltip'] = true;

		return $aReturn;
	}

}