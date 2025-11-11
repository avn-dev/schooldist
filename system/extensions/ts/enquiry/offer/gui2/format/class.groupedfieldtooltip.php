<?php

class Ext_TS_Enquiry_Offer_Gui2_Format_GroupedfieldTooltip extends Ext_TS_Enquiry_Offer_Gui2_Format_Groupedfield {

	/**
	 * @var string
	 */
	private $_sTitleColumn;

	/**
	 * @param string $sTitleColumn
	 * @param string $sField
	 * @param string|null $sFormatClass
	 * @param string|null $sRowSeperator
	 * @param string|null $sFieldSeperator
	 */
	public function __construct($sTitleColumn, $sField, $sFormatClass=null, $sRowSeperator=null, $sFieldSeperator=null) {
		parent::__construct($sField, $sFormatClass, $sRowSeperator, $sFieldSeperator);
		$this->_sTitleColumn = $sTitleColumn;
	}

	/**
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return array|bool
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aFields = explode($this->sFieldSeperator, $this->sValue);

		$bFound = false;
		$aTooltipValue = array();
		foreach($aFields as $sField) {
			if($sField == $this->_sTitleColumn) {
				$bFound = true;
				continue;
			}
			if($bFound) {
				$aTooltipValue[] = $sField;
				$bFound = false;
			}
		}

		$aReturn = array();
		$aReturn['content'] = implode("<br />", $aTooltipValue);
		$aReturn['tooltip'] = true;

		return $aReturn;
	}

}
