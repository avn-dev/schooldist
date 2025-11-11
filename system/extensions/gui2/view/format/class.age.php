<?php

class Ext_Gui2_View_Format_Age extends Ext_Gui2_View_Format_Date {

	protected $sWDDatePart = WDDate::DB_DATE;

	public function format($mValue, &$oColumn = null, &$aResultData = null){
	 
		if(
			(
				is_numeric($mValue) &&
				$mValue == 0
			) ||
			$mValue == '0000-00-00'
		) {
			return '';
		}

		$bIsUnixTimestamp = false;

		// Wenn Nummeric => unix timestamp
		if(
			is_numeric($mValue)
		){
			$bIsUnixTimestamp = true;
		}

		try {

			// Wenn (mysql) Timestamp dann rechne es in Unix Timestamp um!
			if(!$bIsUnixTimestamp) {

				$oDate = new WDDate($mValue, $this->sWDDatePart);

			} else {

				$oDate = new WDDate($mValue, WDDate::TIMESTAMP);

			}

			$sAge = $oDate->getAge();

		} catch(Exception $e) {
			$sAge = '';
		}

		return (string)$sAge;

	}

	public function align(&$oColumn = null){
		return 'right';
	}

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {
		$oCell->setValueExplicit(
			$mValue,
			\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
		);
	}
	
}
