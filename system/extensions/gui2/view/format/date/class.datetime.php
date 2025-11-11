<?php

class Ext_Gui2_View_Format_Date_DateTime extends Ext_Gui2_View_Format_Date {

	/**
	 * @var string
	 */
	protected $sWDDatePart = WDDate::DB_DATETIME;

	/**
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return array
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$sOriginalFormat = $this->format;

		$this->format = $sOriginalFormat.' %H:%M:%S';

		$sColumn = $oColumn->db_column;

		/*
		 * Wenn die Daten als Array vorliegen, soll nichts
		 * angezeigt werden. Hier könnte man die Daten auch als
		 * kommaseparierte Liste anzeigen.
		 */
		if(is_array($aResultData[$sColumn])) {
			return array();
		}

		if($this->oGui->checkWDSearch() === true) {

			$oDate = new DateTime($aResultData[$sColumn]);
			$aResultData[$sColumn] = $oDate->format('Y-m-d H:i:s');

		} else {

			$sColumn = $oColumn->select_column;
			if(empty($sColumn)) {
				$sColumn = $oColumn->db_column;
			}

		}

		$sToolTip = $this->format($aResultData[$sColumn]);

		$aReturn = array();
		$aReturn['content'] = (string)$sToolTip;
		$aReturn['tooltip'] = true;

		$this->format = $sOriginalFormat;

		return $aReturn;
	}

}