<?php

class Ext_TS_Enquiry_Combination_Gui2_Selection_Accommodation_Roomtype extends Ext_Gui2_View_Selection_Abstract {

	/**
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array 
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		$aReturn = array();
		$oSchool = $oWDBasic->getSchool();

		if(
			$this->oJoinedObject &&
			$oSchool
		) {	
			$aRoomtypes = $oSchool->getAccommodationRoomCombinations();		
			$oAccommodation = $this->oJoinedObject;

			if(!empty($aRoomtypes[$oAccommodation->accommodation_id])) {
				$aTemp = $aRoomtypes[$oAccommodation->accommodation_id];				
				foreach($aTemp as $iRoomtypeId) {
					$oRoomtype = Ext_Thebing_Accommodation_Roomtype::getInstance($iRoomtypeId);
					$aReturn[$iRoomtypeId] = $oRoomtype->getName();
				}
			}
			
		}

	
		return $aReturn;
	}
	
}