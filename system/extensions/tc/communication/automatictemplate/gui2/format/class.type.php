<?php

class Ext_TC_Communication_AutomaticTemplate_Gui2_Format_Type extends Ext_TC_Gui2_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		/** @var Ext_TC_Communication_AutomaticTemplate_Gui2_Data $oGuiData */
		$sClass = \Factory::getClassName('Ext_TC_Communication_AutomaticTemplate_Gui2_Data');
		$oGuiData = new $sClass($this->oGui);
		$aTypes = $oGuiData->getSelectOptionsTypes();

		return $aTypes[$mValue];

	}

}
