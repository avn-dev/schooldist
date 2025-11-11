<?php

abstract class Ext_TS_Service_Abstract extends Ext_Thebing_Basic {

	/**
	 * Liefert die Season, Kurs/Unterkunft/Versicherung
	 */
	public function getSeason() {

		$sTimeFieldFrom = '';

		if(isset($this->_aData['from'])) {
			$sTimeFieldFrom = 'from';
		} elseif($this->_aData['transfer_date']) {
			$sTimeFieldFrom = 'transfer_date';
		} else {
			throw new Exception('Feld "from" wird benötigt');
		}

		$oInquiry = $this->getInquiry();
		$oSchool = $oInquiry->getSchool();
		$oSaisonSearch = new Ext_Thebing_Saison_Search();

		$aSaisonData = $oSaisonSearch->bySchoolAndTimestamp(
			$oSchool->id,
			Ext_Thebing_Util::convertDateToTimestamp($this->$sTimeFieldFrom),
			$oInquiry->getCreatedForDiscount()
		);

		return Ext_Thebing_Marketing_Saison::getInstance((int)$aSaisonData[0]['id']);

	}

	/**
	 * @return Ext_TS_Inquiry_Abstract|Ext_TS_Inquiry|Ext_TS_Enquiry
	 */
	abstract public function getInquiry();

    /**
     * Definiert ob der Service in Schulferien blockiert wird oder ob er trozdem stattfinden kann.
	 *
     * @return boolean
     */
    public function splitByHolidays() {
        return true;
    }

	/**
	 * Summe aller Monate, die diese Leistung schneidet (für Preisberechnung)
	 *
	 * Methode gehört eigentlich zu den Kursen, aber dafür gibt es keine übergeordnete Klasse.
	 * Anteilige Monate werden in 1/4 Monaten ausgerechnet (Clic, #15325)
	 *
	 * @param self $oObject
	 * @return int
	 */
	public static function getMonthCount(object $oObject) {

		$dFrom = new \Carbon\Carbon($oObject->from);
		$dUntil = new \Carbon\Carbon($oObject->until);

		$oFirstPeriod = null;
		
		$iMonths = 0;
		
		if($dFrom->isSameMonth($dUntil)) {
			
			$iDays = $dUntil->day - $dFrom->day;
			
			$iPart = $iDays / $dFrom->daysInMonth * 4;
			
			// Immer aufrunden
			$iPart = ceil($iPart);

			$iMonths += $iPart/4;
			
		} else {

			if($dFrom->day != 1) {

				$iDays = $dFrom->daysInMonth - $dFrom->day;
	
				$iPart = $iDays / $dFrom->daysInMonth * 4;

				// Immer aufrunden
				$iPart = ceil($iPart);
	
				$iMonths += $iPart/4;
	
				$dFrom->next('month')->startOfMonth();

			}

			if($dUntil->isLastOfMonth() !== true) {

				$iPart = $dUntil->day / $dUntil->daysInMonth * 4;

				// Immer aufrunden
				$iPart = ceil($iPart);
	
				$iMonths += $iPart/4;

				$dUntil->previous('month')->endOfMonth();

			}

			if($dFrom < $dUntil) {
				$aMonths = \Core\Helper\DateTime::getMonthPeriods($dFrom, $dUntil, false);
				$iMonths += count($aMonths);	
			}
		}

		return $iMonths;
	}

	abstract protected function assignLineItemDescriptionVariables(\Core\Service\Templating $oSmarty, \Tc\Service\Language\Frontend $oLanguage);

	/**
	 * @todo Soll die getInfo() für die Rechnungspositionsbeschreibung ersetzen
	 * @param \Tc\Service\Language\Frontend $oLanguage
	 * @return string
	 */
	public function getLineItemDescription(\Tc\Service\Language\Frontend $oLanguage):string {
		
		$oSchool = $this->getInquiry()->getSchool();
		
		$sTemplate = $oSchool->getPositionTemplate($this->sInfoTemplateType);
		
		$oSmarty = new \Core\Service\Templating;
		$oSmarty->setLanguage($oLanguage);
		
		$this->assignLineItemDescriptionVariables($oSmarty, $oLanguage);
		
		$sInfo = trim($oSmarty->fetch('string:'.$sTemplate));
		
		return $sInfo;
	}
	
}
