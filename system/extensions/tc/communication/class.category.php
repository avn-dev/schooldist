<?php

/**
 * Farbkategorien WDBASIC
 */
class Ext_TC_Communication_Category extends Ext_TC_Basic {
	
	protected $_sTable = 'tc_communication_categories';
	
	protected $_sTableAlias = 'tc_cc';
	
	protected $_aFormat = array(
		'name' => array(
			'required' => true
		),
		'code' => array(
			'required' => true
		),
	);

	public function validate($bThrowExceptions = false)
	{
		$mError = parent::validate($bThrowExceptions);

		if($mError === true)
		{
			$aErrors = array();
			$sColorCode = $this->code;
			$aRgb = imgBuilder::_htmlHexToBinArray($sColorCode);
			$iBrightness = sqrt($aRgb[0]*$aRgb[0]*0.241+$aRgb[1]*$aRgb[1]*0.691+$aRgb[2]*$aRgb[2]*0.068);
		
			if($iBrightness <= 128) {
				$aErrors['code'] = Ext_TC_Communication::t('Bitte wählen Sie eine helle Farbe.');
			}

			if(empty($aErrors)) {
				return true;
			} else {
				return $aErrors;
			}

		}
		else
		{
			return $mError;
		}
	}
	
	public static function getSelectOptions()
	{
		$oSelf = new self;
		$aList = $oSelf->getArrayList(true); //Das Array ist an dieser Stelle alphabetisch sortiert. Kein Grund nochmal zu sortieren.
		$aReturn = Ext_TC_Util::addEmptyItem($aList);
		
		return $aReturn;
		
	}

}
