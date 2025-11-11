<?php

namespace Ts\Gui2\AccommodationProvider;

class PaymentPeriodFormat extends \Ext_Thebing_Gui2_Format_Date {

	private $sFromField;
	private $sUntilField;

	public function __construct($sFromField='from', $sUntilField='until') {
		parent::__construct();

		$this->sFromField = $sFromField;
		$this->sUntilField = $sUntilField;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		$sPeriod = '';
		
		$sFrom = parent::format($aResultData[$this->sFromField]);
		$sUntil = parent::format($aResultData[$this->sUntilField]);

		if(
			!empty($sFrom) &&
			!empty($sUntil)
		) {
			$sPeriod = $sFrom.' – '.$sUntil;
		}
		
		return $sPeriod;
	}

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {

		// Weil die Formatklasse von der Date ableitet und da in der setExcelValue() wird der Typ von der Excel-Spalte auf "date"
		// gesetzt, aber in diesem Fall ist es ein String mit einer Zeitspanne und kein direktes Datum.
		$oCell->setValueExplicit(
			$mValue,
			\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
		);
	}
	
}