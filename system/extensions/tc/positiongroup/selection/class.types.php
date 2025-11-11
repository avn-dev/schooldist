<?php

class Ext_TC_Positiongroup_Selection_Types extends Ext_Gui2_View_Selection_Abstract {

	/**
	 *
	 * @param type $aSelectedIds
	 * @param type $aSaveField
	 * @param Ext_TC_Positiongroup_Section $oWDBasic
	 * @return array
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$oPositiongroup = $oWDBasic->getJoinedObject('positiongroup');
		/* @var $oPositiongroup Ext_TC_Positiongroup */
		$aPositions		= $oPositiongroup->getUnallocatedPositionTypes();
		
		$aSelfPositions = $oWDBasic->positions;
		$aPositionTypes = Ext_TC_Positiongroup_Gui2_Data::getPositionTypes();

		foreach((array)$aPositionTypes as $sKey => $sType){
			if(!in_array($sKey, $aPositions) && !in_array($sKey, $aSelfPositions)){
				unset($aPositionTypes[$sKey]);
			}
		}

		return $aPositionTypes;

	}

}