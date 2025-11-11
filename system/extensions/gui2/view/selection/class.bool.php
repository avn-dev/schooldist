<?php

class Ext_Gui2_View_Selection_Bool extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aOptions = array();
		$aOptions[0] = L10N::t('Nein', Ext_Gui2::$sAllGuiListL10N);
		$aOptions[1] = L10N::t('Ja', Ext_Gui2::$sAllGuiListL10N);

		return $aOptions;

	}

}