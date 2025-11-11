<?php

abstract class Ext_TC_Communication_Message_Notice_Gui2_Selection_Correspondant extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		global $_VARS;

		// Die GUI schreibt diesen Wert in die WDBasic beim Öffnen des Dialogs
		$sParentClass = $oWDBasic->relation;
		$aParentIds = (array)$_VARS['parent_gui_id'];
		$iParentId = reset($aParentIds);

		return array();

	}

}