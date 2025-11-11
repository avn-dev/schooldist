<?php

use Carbon\Carbon;

class Ext_Gui2_View_Format_Date extends Ext_Gui2_View_Format_Date_Abstract {

	/**
	 * @var array
	 */
	protected $aOption = array('format'=>'%x');

	/**
	 * @var string
	 */
	protected $sWDDatePart = WDDate::DB_DATE;

	/**
	 * Methode setzt den Excel-Format-Typ auf "Datum" für Datumsspalten. 
	 * Die Methode gibt es schon in der Ext_Gui2_View_Format_Abstract aber mit DataType::TYPE_STRING
	 */
	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {

		if (empty($mValue) || $mValue === '0000-00-00') {
			return;
		}

		try {

			if ($aValue['original'][4] === '-' && $aValue['original'][7]) {
				// 0000-00-00 und 0000-00-00T00:00:00.000Z
				$date = DateTime::createFromFormat(strlen($aValue['original'] === 10) ? 'Y-m-d' : 'Y-m-d|+', $aValue['original']);
			} elseif (is_numeric($aValue['original'])) {
				// Timestamp (alte Klassen mit aFormat)
				$date = \Core\Helper\DateTime::createFromLocalTimestamp($aValue['original']);
			}

			if (empty($date)) {
				throw new RuntimeException('Unknown date format');
			}

			$oCell->setValueExplicit($date, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_ISO_DATE)
				->getStyle()
				->getNumberFormat()
				->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY);

		} catch (\Throwable) {
			
			parent::setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData);
			
		}
		
	}

}
