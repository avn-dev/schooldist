<?php

/**
 * Helper-Klasse zur Validierung von Validity-Objekten 
 */
class Ext_TC_Validity_Helper_Error {

	protected $_sValidFrom = '0000-00-00';

	protected $_sValidUntil = '0000-00-00';
	
	protected $_oWDBasic = null;

	protected $_sTableAlias = 'validity';

	/**
	 * Konstruktor
	 * @param Ext_TC_Validity $oWDBasic 
	 */
	public function __construct(Ext_TC_Validity $oWDBasic) {

		if($this->_checkDate($oWDBasic->valid_from)) {
			$this->_sValidFrom = $oWDBasic->valid_from;
		}

		if($this->_checkDate($oWDBasic->valid_until)) {
			$this->_sValidUntil = $oWDBasic->valid_until;
		}

		#$this->_sTableAlias = $oWDBasic->getTableAlias();

		$this->_oWDBasic = $oWDBasic;
	}

	/**
	 * validiert, je nach Angaben, den Eintrag
	 * @return array 
	 */
	public function validate() {
		$aErrors = array();

		if(
			$this->_sValidFrom == '0000-00-00' &&
			$this->_sValidUntil == '0000-00-00'
		) {
			$aErrors = $this->_checkNoDateGiven();
		} else if(
			$this->_sValidFrom != '0000-00-00' &&
			$this->_sValidUntil == '0000-00-00'		
		) {
			$aErrors = $this->_checkValidFromGiven();
		} else if(
			$this->_sValidFrom == '0000-00-00' &&
			$this->_sValidUntil != '0000-00-00'				
		) {
			$aErrors = $this->_checkValidUntilGiven();
		} else {
			$aErrors = $this->_checkBothDatesGiven();
		}

		return $aErrors;
	}

	/**
	 * prüft die Eingabe der Datenfelder
	 * @param string $sDate
	 * @return boolean 
	 */
	protected function _checkDate($sDate) {

		if(
			!empty($sDate) &&
			WDDate::isDate($sDate, WDDate::DB_DATE) === true
		) {
			return true;
		}

		return false;
	}

	/**
	 * Wenn kein Datum angegeben wurde
	 * @return array 
	 */
	protected function _checkNoDateGiven() {
		$aErrors = array();
		$aErrors[][] = L10N::t('Es muss mindestens ein Datum eingetragen sein!');
		return $aErrors;
	}

	/**
	 * Wenn nur "valid_from" angegeben wurde
	 * @return array 
	 */
	protected function _checkValidFromGiven() {
		$aErrors = array();
		// Prüfen, ob "Gültig ab" nach dem aktuellsten "Gültig ab" liegt

		$iDependencyId = (!empty($this->_oWDBasic->sDependencyColumn))
			? $this->_oWDBasic->{$this->_oWDBasic->sDependencyColumn}
			: null;

		$iLatestEntry = $this->_oWDBasic->getLatestEntry(false, null, $iDependencyId);

		if($iLatestEntry > 0) {

			$oLatestEntry = call_user_func(array(get_class($this->_oWDBasic), 'getInstance'), (int) $iLatestEntry);

			/*
			* Falls der letzte Eintrag bereits ein valid_until besitzt,
			* ist valid_until das aktuelle Datum
			*/					
			if(
				$oLatestEntry->valid_until == '0000-00-00' ||
				$oLatestEntry->valid_until == ''
			){
				$oDate = new WDDate($oLatestEntry->valid_from, WDDate::DB_DATE);		
			}else{
				$oDate = new WDDate($oLatestEntry->valid_until, WDDate::DB_DATE);
			}

			$iCompare = $oDate->compare($this->_sValidFrom, WDDate::DB_DATE);

			/**
			 * NE ...<-------....
			 * LE .......<------- 
			 */
			if($iCompare >= 0) {
				$aErrors[$this->_sTableAlias.'.valid_from'][] = L10N::t('Der Wert in Feld "%s" ist zu klein!');
			}
		}		

		return $aErrors;
	}

	/**
	 * Wenn nur "valid_until" angegeben wurde
	 * @return array 
	 */
	protected function _checkValidUntilGiven() {
		$aErrors = array();

		$iFirstEntry = $this->_oWDBasic->getFirstEntry(false, null);
		
		if($iFirstEntry > 0) {

			$oFirstEntry = call_user_func(array(get_class($this->_oWDBasic), 'getInstance'), (int) $iFirstEntry);

			/**
			 * NE ----->.........
			 * FE ..<---------... 
			 */
			if(
				$oFirstEntry->valid_from != '0000-00-00' &&
				$oFirstEntry->valid_from != ''
			) {
				
				$oEntryValidFrom = new WDDate($oFirstEntry->valid_from, WDDate::DB_DATE);
				
				$iCompare = $oEntryValidFrom->compare($this->_sValidUntil, WDDate::DB_DATE);
				
				if($iCompare < 0) {
					$aErrors[$this->_sTableAlias.'.valid_until'][] = L10N::t('Der Wert in Feld "%s" ist zu groß!');
					return $aErrors;
				}
				
			/**
			 * NE ---->.........
			 * FE -------->..... 
			 */	
			} else {
				$aErrors[$this->_sTableAlias.'.valid_until'][] = L10N::t('Der angegebene Zeitraum überschneidet sich mit dem Zeitraum eines anderen Eintrages!');
				return $aErrors;
			}		

		}
		
		return $aErrors;
	}

	/**
	 * Wenn beide Daten angegeben wurden
	 * @return array 
	 */
	protected function _checkBothDatesGiven() {
		$aErrors = array();

		$aEntries = (array)$this->_oWDBasic->getEntries(false, false);

		$oThisDateFrom = new WDDate($this->_sValidFrom, WDDate::DB_DATE);
		$oThisDateUntil = new WDDate($this->_sValidUntil, WDDate::DB_DATE);

		$iCompare = $oThisDateFrom->compare($oThisDateUntil->get(WDDate::DB_DATE), WDDate::DB_DATE);

		/**
		* Überprüfung ob das angegebene Startdatum kleiner ist als das Enddatum 
		*/
		if($iCompare >= 0) {
			$aErrors[$this->_sTableAlias.'.valid_until'][] = L10N::t('Das Enddatum des angegebenen Zeitraumes muss größer sein als das Startdatum!');
			return $aErrors;
		}

		$aMatches = array();

		foreach($aEntries as $iId) {

			$oEntry = call_user_func(array(get_class($this->_oWDBasic), 'getInstance'), (int) $iId);

			$bChangedUntil = false;

			/**
			 * alter Eintrag hat nur "valid_from" gesetzt
			 */
			if(
				$oEntry->valid_from	!= '0000-00-00' ||
				$oEntry->valid_from != ''
			) {
			
				$oDateFrom = new WDDate($oEntry->valid_from, WDDate::DB_DATE);

				/**
				* Falls für einen Eintrag noch kein valid_until gesetzt wurde, wird dieser temporär
				* auf valid_from gesetzt, damit für checkPeriod ein Zeitraum angegeben ist
				*/
				if(
					$oEntry->valid_until == '0000-00-00' ||
					$oEntry->valid_until == ''
				) {
					$oEntry->valid_until = $oEntry->valid_from;
					$bChangedUntil = true;
				}

				$oDateUntil = new WDDate($oEntry->valid_until, WDDate::DB_DATE);

				$iPeriod = WDDate::comparePeriod($oThisDateFrom, $oThisDateUntil, $oDateFrom, $oDateUntil);

				/**
				* valid_until wieder auf 0 setzen, damit dieser beim speichern neu gesetzt
				* werden kann 
				*/
				if($bChangedUntil === true) {
					$oEntry->valid_until = '0000-00-00';
				}

				/**
				* NE ....<------>.......
				* LE ..<----------->....
				* LE ----->.............
				* LE ..........<--------
				* LE ......<--->........
				*/
				if(
					$iPeriod !== WDDate::PERIOD_CONTACT_END &&
					$iPeriod !== WDDate::PERIOD_CONTACT_START &&
					$iPeriod !== WDDate::PERIOD_AFTER &&
					$iPeriod !== WDDate::PERIOD_BEFORE
				) {
					$aMatches[] = false;
					break;
				}
				
			/**
			 * alter Eintrag hat nur "valid_until" gesetzt
			 */				
			} elseif(
				$oEntry->valid_until != '0000-00-00' ||
				$oEntry->valid_until != ''
			) {
				
				$oDateUntil = new WDDate($oEntry->valid_until, WDDate::DB_DATE);
				
				$iCompare = $oDateUntil->compare($oThisDateFrom->get(WDDate::DB_DATE), WDDate::DB_DATE);
				
				/**
				 * Das "valid_from" des neuen Eintrages ist kleiner als das "valid_until" des
				 * alten Eintrages
				 * 
				 * NE ....<----->.....
				 * LE ------>......... 
				 */				
				if($iCompare < 0) {
					$aMatches[] = false;
					break;
				}				
			}

		}

		if(in_array(false, $aMatches)) {
			$aErrors[$this->_sTableAlias.'.valid_from'][] = L10N::t('Der angegebene Zeitraum überschneidet sich mit dem Zeitraum eines anderen Eintrages!');
		}

		return $aErrors;
	}

}