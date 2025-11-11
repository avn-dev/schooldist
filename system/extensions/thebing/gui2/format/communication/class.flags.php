<?php

class Ext_Thebing_Gui2_Format_Communication_Flags extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aFlags = Ext_Thebing_Communication::getFlags();

		$sFlags = '';
		foreach((array)$mValue as $sFlag) {
			$sFlags .= $aFlags[$sFlag].'<br/>';
		}

		return $sFlags;

	}

}
