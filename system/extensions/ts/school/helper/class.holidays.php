<?php

/**
 * 
 * Helper-Klasse um alle Feiertage einer Schule innerhalb eines Zeitraumes zu 
 * bekommen
 * 
 * @author TS 
 * 
 */
class Ext_TS_School_Helper_Holidays {
	
	/**
	 * @var Ext_Thebing_School 
	 */
	protected $_oSchool;
	/**
	 * @var DateTime 
	 */
	protected $_oDateFormat;
	/**
	 * @var <array> 
	 */
	protected $_aFinalHolidays = array();
	
	/**
	 * Konstruktor
	 * @param Ext_Thebing_School $oSchool
	 */
	public function __construct(Ext_Thebing_School $oSchool) {
		$this->_oSchool			= $oSchool;	
		$this->_oDateFormat		= new Ext_Thebing_Gui2_Format_Date(false, $oSchool->id);
	}
	
	/**
	 * liefert alle Feiertage und ggf. Schulferien innerhalb des gewünschten Zeitraumes
	 * 
	 * @param DateTime $oDateFrom
	 * @param DateTime $oDateUntil
	 * @param boolean $bIncludeSchoolHolidays
	 * @return array
	 */
	public function getHolidays(DateTime $oDateFrom, DateTime $oDateUntil, $bIncludeSchoolHolidays = true) {
		
		$iFrom		= $oDateFrom->getTimestamp();
		$iUntil		= $oDateUntil->getTimestamp();
		
		// alle Feiertage und ggf. Schulferien der Schule holen
		$aHolidays = $this->_oSchool->getHolidays($iFrom, $iUntil, $bIncludeSchoolHolidays);

		foreach($aHolidays as $aHoliday) {
			$oHoliday = new DateTime($aHoliday['date']);			
			
			//jährliche Feiertage müssen gesondert behandelt werden
			if($aHoliday['annual'] == 1) {
				// falls der Zeitraum über mehrere Jahre geht muss hier für jedes
				// Jahr ein Feiertag hinzugefügt werden
				$aAnnualHolidays = $this->_getAnnualHolidays($oHoliday, $oDateFrom, $oDateUntil);
				
				foreach((array) $aAnnualHolidays as $oAnnualHoliday) {
					$this->_addHoliday($oAnnualHoliday);
				}
							
			} else {
				$this->_addHoliday($oHoliday);
			}		
		}
		
		// Daten anhand des keys sortieren (Key = Timestamp)
		ksort($this->_aFinalHolidays);
		
		return $this->_aFinalHolidays;
	}
	
	/**
	 * ermittelt für einen jährlichen Feiertag wie oft dieser in dem gewünschten Zeitraum 
	 * aufkommt (z.b. wenn der Zeitraum über mehrere Jahre geht)
	 * 
	 * @param DateTime $oAnnualHoliday
	 * @param DateTime $oDateFrom
	 * @param DateTime $oDateUntil
	 * @return array
	 */
	protected function _getAnnualHolidays(DateTime $oAnnualHoliday, DateTime $oDateFrom, DateTime $oDateUntil) {
		
		$iFromYear		= (int) $oDateFrom->format('Y');
		$iUntilYear		= (int) $oDateUntil->format('Y');
		
		$iDay			= (int) $oAnnualHoliday->format('d');
		$iMonth			= (int) $oAnnualHoliday->format('m');
		
		$aAnnualHolidays = array();
		
		// wenn der Zeitraum über mehrere Jahre geht muss für jedes Jahr ein Feiertag
		// hinzugefügt werden
		for($i = $iFromYear; $i <= $iUntilYear; $i++) {
			$sDate = $i . '-' . $iMonth . '-' . $iDay;
			$oTempHoliday = new DateTime($sDate);
			if(
				$oTempHoliday >= $oDateFrom &&
				$oTempHoliday <= $oDateUntil
			) {				
				// Wenn der Feiertag für das Jahr in den gewünschten Zeitraum fällt
				// muss dieser hinzugefügt werden
				$aAnnualHolidays[] = $oTempHoliday;
			}
			
		}
		
		return $aAnnualHolidays;
	}
	
	/**
	 * fügt einen Feiertag hinzu
	 * 
	 * @param DateTime $oHoliday
	 */
	protected function _addHoliday(DateTime $oHoliday) {
		$sKey = $oHoliday->getTimestamp();
		$this->_aFinalHolidays[$sKey] = $oHoliday;
	}
	
	/**
	 * liefert ein formatiertes Datum zurück
	 * 
	 * @param DateTime $oDate
	 * @return string
	 */
	public function formatDate(DateTime $oDate) {
		return $this->_oDateFormat->formatByValue($oDate->format('Y-m-d'));
	}
	
}
