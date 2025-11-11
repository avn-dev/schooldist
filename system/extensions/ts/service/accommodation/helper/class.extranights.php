<?php

/**
 * Helper-Klasse für Unterkünfte in der Inbox und Anfragen:
 * 
 * Die Klasse berechnet den genauen Zeitraum der Unterkunft so wie die einzelnen
 * Zeiträume von Extranächten/-wochen vor bzw. nach dem eigentlich Unterkunftszeitraum
 * 
 * @author TS
 */
class Ext_TS_Service_Accommodation_Helper_Extranights {
	
	/**
	 * interner Cache
	 * @var array 
	 */
	protected $_aDateData = array();
	
	/**
	 * Extranächte
	 * @var array 
	 */
	public $aExtraNights = array();
	
	/**
	 * Extrawochen
	 * @var array 
	 */
	public $aExtraWeeks = array();

	/**
	 * @var Ext_TS_Service_Interface_Accommodation|Ext_TS_Inquiry_Journey_Accommodation|Ext_TS_Enquiry_Combination_Accommodation
	 */
	protected $_oServiceAccommodation = null;
	
	/**
	 * @param Ext_TS_Service_Interface_Accommodation|Ext_TS_Inquiry_Journey_Accommodation|Ext_TS_Enquiry_Combination_Accommodation $oServiceAccommodation
	 */
	public function __construct($oServiceAccommodation) {
		$this->_oServiceAccommodation = $oServiceAccommodation;
	}
	
	/**
	 * gibt das neu berechnete Von-Datum der Leistung (Unterkunft/Extranächte/Extrawochen) zurück 
	 * @param string $sType 
	 * @return string
	 */
	public function getRealFrom($sType = 'accommodation') {
		$sFrom = $this->_getRealDate($sType, 'from');
		return $sFrom;
	}
	
	/**
	 * gibt das neu berechnete Bis-Datum der Leistung (Unterkunft/Extranächte/Extrawochen) zurück 
	 * @param string $sType 
	 * @return string
	 */
	public function getRealUntil($sType = 'accommodation') {
		$sUntil = $this->_getRealDate($sType, 'until');
		return $sUntil;
	}
	
	/**
	 * gibt das neu berechnete Datum aus
	 * @param string $sType
	 * @param string $sDate
	 * @return string
	 */
	protected function _getRealDate($sType, $sDate) {
		$aData = $this->_getAccommodationPeriods();
		
		// from, until
		$sReturn = $this->_oServiceAccommodation->$sDate;
		if(!empty($aData[$sType][$sDate])) {
			$sReturn = $aData[$sType][$sDate];
		}
		
		return $sReturn;
	}
	
	/**
	 * berechnet die genauen Daten der Unertkunft, Extranächte und Extrawochen
	 * @return array
	 */
	protected function _getAccommodationPeriods() {
		
		if(empty($this->_aDateData)) {
			
			$sFrom = $this->_oServiceAccommodation->from;
			$sUntil = $this->_oServiceAccommodation->until;
			
			// Aktuelle Daten
			$oAccommodationFrom = new WDDate($sFrom, WDDate::DB_DATE);
			$oAccommodationUntil = new WDDate($sUntil, WDDate::DB_DATE);
			
			// Daten die vorhanden sind
			$aDateData = array(
				'nights_at_start'	=> array(),
				'weeks_at_start'	=> array(),
				'accommodation'		=> array(),
				'weeks_at_end'		=> array(),
				'nights_at_end'		=> array(),
			);
			
			// Zeitraum der Extranächte berechnen und von dem Zeitraumd er Unterkunft abziehen
			foreach($this->aExtraNights as $aData) {
				$this->_calculatePeriod('extranight', $aData, $aDateData, $oAccommodationFrom, $oAccommodationUntil);
			}
			
			// Zeitraum der Extrawochen berechnen und von dem Zeitraumd er Unterkunft abziehen
			foreach ($this->aExtraWeeks as $aData) {				
				$this->_calculatePeriod('extraweek', $aData, $aDateData, $oAccommodationFrom, $oAccommodationUntil);
			}
			
			// Zeitraum der Unterkunft abzüglich der Zeiträume von Extranächten/-wochen
			$aDateData['accommodation'] = array(
				'until' => $oAccommodationUntil->get(WDDate::DB_DATE),
				'from' => $oAccommodationFrom->get(WDDate::DB_DATE)
			);
			
			$this->_aDateData = $aDateData;
			
		}
		
		return $this->_aDateData;
	}
	
	/**
	 * zieht die Anzahl der Extranächte/-wochen von dem Zeitraum der Unterkunft ab und setzt
	 * den Zeitraum der Extranächte/-wochen
	 * @param string $sType
	 * @param array $aData
	 * @param array $aDateData
	 * @param WDDate $oAccommodationFrom
	 * @param WDDate $oAccommodationUntil
	 * @throws Exception
	 */
	protected function _calculatePeriod($sType, $aData, &$aDateData, WDDate &$oAccommodationFrom, WDDate &$oAccommodationUntil) {
		
		// Anzahl der Nächte/Wochen
		$iNumber = (int) $aData['nights'];
		$sKey = 'unknown';
		
		// Leistung vor dem Start
		if($aData['type'] == 'nights_at_start') {

			// Altes Von-Datum ist das neue Von-Datum der Leistung
			$sFrom = $oAccommodationFrom->get(WDDate::DB_DATE);

			// Zeitraum abziehen
			if($sType == 'extraweek') {
				$oAccommodationFrom->add($iNumber, WDDate::WEEK);
				$sKey = 'weeks_at_start';
			} elseif($sType == 'extranight') {
				$oAccommodationFrom->add($iNumber, WDDate::DAY);
				$sKey = 'nights_at_start';
			}

			$aDateData[$sKey] = array(
				'from' => $sFrom,
				'until' => $oAccommodationFrom->get(WDDate::DB_DATE)
			);					

			// # auskommentiert # Von-Datum um 1 erhöhen damit sich die Daten nicht überschneiden
            // sollen sich überschneiden da endatum der extra nach das startdatum der unterkunft sein muss
			//$oAccommodationFrom->add(1, WDDate::DAY);
			
		} 
		// Leistung am Ende
		elseif($aData['type'] == 'nights_at_end') {

			// Altes Bis-Datum ist das neue Bis-Datum der Leistung
			$sUntil = $oAccommodationUntil->get(WDDate::DB_DATE);

			// Zeitraum abziehen
			if($sType == 'extraweek') {
				$oAccommodationUntil->sub($iNumber, WDDate::WEEK);
				$sKey = 'weeks_at_end';
			} elseif($sType == 'extranight') {
				$oAccommodationUntil->sub($iNumber, WDDate::DAY);
				$sKey = 'nights_at_end';
			}

			$aDateData[$sKey] = array(
				'until' => $sUntil,
				'from' => $oAccommodationUntil->get(WDDate::DB_DATE)
			);

			// # auskommentiert # Vom Bis-Datum 1 Tag abziehen damit sich die Daten nicht überschneiden
            // sollen sich überschneiden da endatum der extra nach das startdatum der unterkunft sein muss
			//$oAccommodationUntil->sub(1, WDDate::DAY);
			
		} else {
			throw new Exception('Unknown type "'.$aData['type'].'"!');
		}
		
	}
}
