<?php

class Ext_Thebing_Gui2_Style_Matching_Row extends Ext_Gui2_View_Style_Abstract {


	public function getStyle($mValue, &$oColumn, &$aResultData) {

		$sColor = '';
		// Gelöschte Zuweisungen 
		if(!empty($aResultData['family_change'])){
			$aAllocationData = explode('{||}', $aResultData['family_change']);
			if(!empty($aAllocationData)){
				foreach((array)$aAllocationData as $sValue){
					$aData = explode('{|}', $sValue);
					if(
						!empty($aData) &&
						$aData[6] == 2
					){
						$sColor = Ext_Thebing_Util::getColor('changed');
					}
				}
			}
		}
	
		

		$sReturn = '';
		
		if(!empty($sColor)){
			$sReturn = 'background-color: '.$sColor.';';
		}

		return $sReturn;

	}


}
