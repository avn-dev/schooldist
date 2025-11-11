<?php

class Ext_Thebing_Placementtests_Gui2_Format_FirstCourse extends Ext_Gui2_View_Format_Abstract {

	/**
	 * Die 2 Spalten, die diese Formatklasse benutzt haben, sind auskommentiert -> wenn die nicht mehr benötigt werden
	 * kann diese Formatklasse auch weg
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aFirstCourse = explode('{|}', $aResultData['first_course']);

		if($oColumn->db_column === 'first_course') {
			$mValue = $aFirstCourse[0];
		} elseif($oColumn->db_column === 'first_course_start') {
			$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
			$mValue = $oFormatDate->format($aFirstCourse[2], $oColumn, $aResultData);
		}

		return $mValue;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aFirstCourse = explode('{|}', $aResultData['first_course']);

		$aReturn = [];
		if($oColumn->db_column === 'first_course') {
			$aReturn['content'] = (string)$aFirstCourse[1];
			$aReturn['tooltip'] = (bool)true;
		}

		return $aReturn;
	}

}
