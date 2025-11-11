<?
class Ext_TC_Frontend_Form_Field_Select_Grouped_Date extends Ext_TC_Frontend_Form_Field_Select {
	
	protected $_sTemplateType = 'select_grouped_date';
	
	public function getOptions() {
		$aOptions = parent::getOptions();
		$oSelection = $this->_oMapping->getSelection();
		
		if($oSelection) {
			$sClass = get_class($oSelection);		
			$aOptions = $sClass::getGroupedOptions($aOptions);
		}
		
		return $aOptions;
	}
	
	public function getValue($bFormated = true, $sLanguage = null) {
	
		if($bFormated){
			$aOptions	= $this->getOptions();

			$sValue = $this->_sFormatedValue;
			
			foreach($aOptions as $aOption) {
				$sTempValue = $this->_getRecursiveValue($aOption, $sValue, $bFormated);
				if($sTempValue !== null) {
					$sValue = $sTempValue;
					break;
				}
			}			
		} else {
			$sValue = $this->_sValue;
		}

		return $sValue;
	}
	
	protected function _getRecursiveValue($aOption, $sValue, $bFormated = true) {
		
		$mReturn = null;

		if(isset($aOption['value'])) {
			if($aOption['value'] == $sValue) {
				if($bFormated) {
					$mReturn = $aOption['text'];
				} else {
					$mReturn = $aOption['value'];
				}
			}	
		} elseif(isset($aOption['options'])) {
			foreach($aOption['options'] as $aSubOption) {
				$mReturn = $this->_getRecursiveValue($aSubOption, $sValue, $bFormated);
				if($mReturn !== null) {
					break;
				}
			}
		}
		
		return $mReturn;
	}
	
}