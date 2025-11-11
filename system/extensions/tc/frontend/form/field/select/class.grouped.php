<?php

/**
 * Class Ext_TC_Frontend_Form_Field_Select_Grouped
 */
class Ext_TC_Frontend_Form_Field_Select_Grouped extends Ext_TC_Frontend_Form_Field_Select {
	
	/**
	 * @var string
	 */
	protected $_sTemplateType = 'select_grouped';
	
	protected function setFirstOptionEmpty(&$aOptions) {
		
		$iFirstOptionGroup = reset(array_keys($aOptions));
		if(empty($aOptions[$iFirstOptionGroup]['text'])) {
			$aTemp = $aOptions[$iFirstOptionGroup]['options'];
			$aFirst = [
				self::EMPTY_OPTION_KEY => ''
			];
			
			if(is_array($aTemp)) {
				$aOptions[$iFirstOptionGroup]['options'] = $aFirst + $aTemp;
			} else {
				$aOptions[$iFirstOptionGroup]['options'] = $aFirst;
			}
		} else {
			$aFirst = [ -1 => [
				'options' => [
					self::EMPTY_OPTION_KEY => ''
				]
			]];

			$aOptions = $aFirst + $aOptions;
		}
				
		$aOptions = array_values($aOptions);
	}
	
	protected function setLabelInEmptyOption($aOptions) {
		
		$aTmp = $aOptions;
		foreach($aTmp as $iTemp => $aValue) {
			foreach((array)$aValue['options'] as $sKey => $sValue) {			
				if($sKey == self::EMPTY_OPTION_KEY) {
					$aOptions[$iTemp]['options'][$sKey] = $this->getLabel();
					break 2;
				}
			}
		}
		
		return $aOptions;
	}
	
}