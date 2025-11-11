<?php

class Ext_TS_Document_Release_Gui2_XmlItData extends Ext_TS_Document_Release_Gui2_Data {

	/**
	 * @return array
	 */
	public static function getListWhere(Ext_Gui2 $oGui = null) {

		$aWhere = parent::getListWhere($oGui);
		
		return $aWhere;
	}
	
}