<?php

class L10N_Translations_Gui2_Code extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$mValue = \Util::convertHtmlEntities($mValue);

		return $mValue;

	}

}
