<?php

class Ext_Thebing_Gui2_Format_Date_DateTime extends Ext_Thebing_Gui2_Format_Date {

	/**
	 * @var string
	 */
	protected $sWDDatePart = WDDate::DB_DATETIME;

	/**
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return array|bool
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$sColumn = $oColumn->select_column;
		if(empty($sColumn)) {
			$sColumn = $oColumn->db_column;
		}

		/*
 		 * Falls es einen Original-Wert gibt, soll dieser genutzt werden,
		 * damit eine Uhrzeit ermittelt werden kann.
		 */
		if(
			isset($aResultData[$sColumn.'_original']) &&
			!empty($aResultData[$sColumn.'_original']) &&
			is_string($aResultData[$sColumn.'_original'])
		) {
			$sColumn .= '_original';
		}

		// Uhrzeit-Tooltip nur wenn Unix-Timestamp oder MySQL-DateTime
		if(
			!is_numeric($aResultData[$sColumn]) &&
			strpos($aResultData[$sColumn], ':') === false
		) {
			return false;
		}

		$sOriginalFormat = $this->format;

		$this->format = Ext_Thebing_Format::getDateFormat($aResultData['school_id']).' %H:%M:%S';

		$sToolTip = $this->getFormattedValue($aResultData[$sColumn]);

		$aReturn = array();
		
		if(!empty($sToolTip)) {
			$aReturn['content'] = (string)$sToolTip;
			$aReturn['tooltip'] = true;
		}

		$this->format = $sOriginalFormat;

		return $aReturn;
	}

}
