<?php

class Ext_TS_Insurances_Gui2 extends Ext_Thebing_Gui2 {
	
	/**
	 * siehe parent
	 * @param type $aFilter
	 * @param type $aOrderBy
	 * @param type $aSelectedIds
	 * @param type $sFlexType
	 * @param type $bSkipLimit
	 * @return type 
	 */
	public function getTableData($aFilter = array(), $aOrderBy = array(), $aSelectedIds = array(), $sFlexType = 'list', $bSkipLimit = false){

		$aTable = parent::getTableData($aFilter, $aOrderBy, $aSelectedIds, $sFlexType, $bSkipLimit);
		
		// Zusatzdaten mitschicken
		$iConfirmed = 0;
		$aSelectedIds	= (array)$this->decodeId((array)$aSelectedIds, 'inquiry_insurance_id');
		
		$bUnset = false;
		$bSet = false;
		
		if(!empty($aSelectedIds)){
//			$iSelectedId = reset($aSelectedIds);	
//			$oContractVersion = Ext_TS_Inquiry_Journey_Insurance::getInstance($iSelectedId);
//			
//			$iConfirmed = 0;
//			if($oContractVersion->isConfirmed()) {				
//				$iConfirmed = 1;
//			}

			foreach($aSelectedIds as $iSelectedId) {
				$oContractVersion = Ext_TS_Inquiry_Journey_Insurance::getInstance($iSelectedId);
				if($oContractVersion->isConfirmed()) {				
					$bUnset = true;
				}
				
				if(!$oContractVersion->isConfirmed()) {				
					$bSet = true;
				}
				
			}
			
		}

		if(
			$bSet == false &&
			$bUnset == true
		) {
			$aTable['additional_data'] = 1;
		} else if(
			$bSet == true &&
			$bUnset == true	
		) {
			$aTable['additional_data'] = 2;
		} else {
			$aTable['additional_data'] = 0;
		}

		return $aTable;

	}	
	
}
