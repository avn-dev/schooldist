<?php

abstract class Ext_Gui2_View_Autocomplete_Abstract implements Ext_Gui2_View_Autocomplete_Interface {

	public function getOption($aSaveField, $sValue) {

		$sLabel = '';

		return $sLabel;

	}
	public function getOptions($sInput, $aSelectedIds, $aSaveField) {

		$aOptions = array();

		return $aOptions;

	}

	public function printOptions($sInput, $aSelectedIds, $aSaveField) {

		$aOptions = $this->getOptions($sInput, $aSelectedIds, $aSaveField);

		$returnArray = [];
		foreach((array)$aOptions as $mKey=>$sValue) {
			$returnArray[] = [
				'label' => $sValue,
				'value' => $mKey
			];
		}

		echo json_encode($returnArray);

		die();

	}

}
