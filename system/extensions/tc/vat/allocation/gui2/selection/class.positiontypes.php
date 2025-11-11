<?php

class Ext_TC_Vat_Allocation_Gui2_Selection_Positiontypes extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param type $aSelectedIds
	 * @param type $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aPositionTypes = $this->_getAllPositionTypes($aSelectedIds, $aSaveField, $oWDBasic);

		if($this->oJoinedObject) {

			$aCurrentPositionTypes = array();

			$aChilds = (array)$oWDBasic->getJoinedObjectChilds($this->sJoinedObjectKey, true);
			foreach($aChilds as $iKey => $oChild) {
				if($iKey != $this->iJoinedObjectKey) {
					$aCurrentPositionTypes = array_merge($aCurrentPositionTypes, $oChild->positiontypes);
				}
			}
			foreach($aCurrentPositionTypes as $sCurrentPositionType) {
				unset($aPositionTypes[$sCurrentPositionType]);
			}

		}

		return $aPositionTypes;

	}

	/**
	 * @param $aSelectedIds
	 * @param $aSaveField
	 * @param $oWDBasic
	 * @return array
	 */
	protected function _getAllPositionTypes($aSelectedIds, $aSaveField, &$oWDBasic) {
		$aPositionTypes = Ext_TC_Factory::executeStatic('Ext_TC_Positiongroup_Gui2_Data', 'getPositionTypes');
		return $aPositionTypes;
	}
	
}