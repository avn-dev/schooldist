<?php


class Ext_Gui2_View_Style_Color extends Ext_Gui2_View_Style_Abstract {

	protected $_sUseField;

	public function __construct($sUseField=false){
		$this->_sUseField = $sUseField;
	}

	public function getStyle($mValue, &$oColumn, &$aRowData) {
		$sReturn = ''; // #FFFFFF

		$sUseField = $this->_sUseField;
		if(is_string($sUseField) && isset($aRowData[$sUseField])){
			$mValue = $aRowData[$sUseField];
		}

		if(!empty($mValue)) {

			$aRgb = imgBuilder::_htmlHexToBinArray($mValue);

			$iBrightness = sqrt($aRgb[0]*$aRgb[0]*0.241+$aRgb[1]*$aRgb[1]*0.691+$aRgb[2]*$aRgb[2]*0.068);
			if($iBrightness > 128) {
				$sReturn .= 'color: #000; ';
			} else {
				$sReturn .= 'color: #FFF; ';
			}

			$sReturn .= 'background-color: '.$mValue.'; ';
		}
		return $sReturn;
	}

}
