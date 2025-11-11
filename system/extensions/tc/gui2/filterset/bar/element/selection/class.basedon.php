<?php

class Ext_TC_Gui2_Filterset_Bar_Element_Selection_Basedon extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param Ext_TC_Gui2_Filterset_Bar_Element $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

        $aBasedOn = $oWDBasic->getAllBasedOn();

        return $aBasedOn;
	}

}