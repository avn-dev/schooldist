<?php

class Ext_TS_Enquiry_Offer_Gui2_Format_Information extends Ext_Gui2_View_Format_Abstract {

	protected $_aResultData = array();

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		$this->_aResultData = array();
		
		$iId = (int)$aResultData['id'];
		// Angebot holen
		$oOffer = Ext_TS_Enquiry_Offer::getInstance($iId);
		// Enquiry holen
		$oEnquiry = Ext_TS_Enquiry::getInstance($oOffer->enquiry_id);

		/* Kursinformationen -------------------------------------------------*/
		$this->_addCourseInformation($oOffer);		
		/* Unterkunftsinformationen ------------------------------------------*/
		$this->_addAccommodationInformation($oOffer);			
		/* Transferinformationen ---------------------------------------------*/
		$this->_addTransferInformation($oOffer);
		/* Versicherungsinformationen ----------------------------------------*/
		$this->_addInsuranceInformation($oOffer);
		/* Gruppeninformationen ----------------------------------------------*/
		
		// Gruppe holen und zwischenspeichern (falls vorhanden)
		$oGroup = $oEnquiry->getGroup();
		if($oGroup) {
			$this->_addInfo($oGroup->name_short, 'group', $oGroup->id);
		}
		
		$aResult = array();
		$this->_setInfo('course', $aResult);
		$this->_setInfo('accommodation', $aResult, ' / ');
		$this->_setInfo('transfer', $aResult);
		$this->_setInfo('insurance', $aResult);
		$this->_setInfo('group', $aResult);
		
		$sReturn = implode(' | ', $aResult);
		
		return $sReturn;
	}
	
	/**
	 * Speichert Daten anhand ihrer Eigenschaft zwischen und prüft, ob überhaupt ein Wert
	 * vorhanden ist, damit keine Leereinträge entstehen
	 * 
	 * $mAllocation verhindert, das Eintäge mehrfach vorkommen
	 * 
	 * array(
	 *		'course' => array(
	 *			'course_id' => Kurs1
	 *		)
	 * )
	 * 
	 * @param mixed $mData
	 * @param array $aResultData 
	 */
	protected function _addInfo($mData, $sKey, $mAllocation) {
		if(!empty($mData)) {
			$this->_aResultData[$sKey][$mAllocation] = $mData;
		}
	}
	
	/**
	 * Holt sich die zwischengespeicherten Werte und baut ein neues Array zusammen
	 * 
	 * Bei $bAsString = true:
	 * 
	 * array(
	 *		[0] => K2 (27.08.2012), K2
	 *		[1] => Category1 / Roomtype1 / Meal1 / Category2
	 *		[2] => Insurance1
	 *		[3] => Transfer1
	 *		[4] => Group1
	 * ) 
	 * 
	 * @param string $sKey
	 * @param array $aResult 
	 * @param string $sSeperator
	 * @param bool $bAsString 
	 */
	protected function _setInfo($sKey, &$aResult, $sSeperator = ', ', $bAsString = true) {
		$mData = $this->_getInfo($sKey, $sSeperator, $bAsString);
		if(!empty($mData)) {
			$aResult[] = $mData;
		}		
	}	
	
	/**
	 * gibt eine mit allen genutzen Leistungen zurück (gruppiert nach Leistung) 
	 * 
	 * @param string $sKey
	 * @param string $sSeperator
	 * @param bool $bAsString
	 * @return mixed 
	 */
	protected function _getInfo($sKey, $sSeperator = ', ', $bAsString = true) {
		
		$mReturn = '';
		
		if(!empty($this->_aResultData[$sKey])) {
			if($bAsString) {
				$mReturn = implode($sSeperator, $this->_aResultData[$sKey]);
			} else {
				$mReturn = $this->_aResultData[$sKey];	
			}
		}

		return $mReturn;
		
	}
	
	/**
	 * speichert Kursinformationen zwischen
	 * @param Ext_TS_Enquiry_Offer $oOffer 
	 */
	protected function _addCourseInformation($oOffer) {
		// Datumsformat holen
		$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
		
		$aCombinationCourses = $oOffer->getCourses();
		// Kombinationskurse durchlaufen
		foreach($aCombinationCourses as $oCombinationCourse) {
			// Kurs holen
			$oCourse = $oCombinationCourse->getCourse();			
			// Datum formatieren
			$sDate = $oDateFormat->format($oCombinationCourse->from);
			// Kursname
			$sCourse = $oCourse->name_short;			
			// Datum hinzufügen
			if(!empty($sDate)) {
				$sCourse .= ' ('.$sDate.')';
			}			
			// Kurs zwischenspeichern
			$this->_addInfo($sCourse, 'course', $oCourse->id . '-' . $sDate);
		}
	}
	
	/**
	 * speichert Unterkunftsinformationen zwischen
	 * @param Ext_TS_Enquiry_Offer $oOffer 
	 */
	protected function _addAccommodationInformation($oOffer) {
		$aCombinationAccommodations = $oOffer->getAccommodations();
		// Kombinationsunterkünfte durchlaufen
		foreach($aCombinationAccommodations as $oCombinationAccommodation) {
			// Kategorie holen und zwischenspeichern
			$oAccommodationCategory = $oCombinationAccommodation->getCategory();
			$this->_addInfo($oAccommodationCategory->getShortName(), 'accommodation', 'accommodation_'.$oAccommodationCategory->id);
			// Raumart holen und zwischenspeichern				
			$oAccommodationRoomtype = $oCombinationAccommodation->getRoomtype();
			$this->_addInfo($oAccommodationRoomtype->getShortName(), 'accommodation', 'roomtype_'.$oAccommodationRoomtype->id);
			// Raumart holen und zwischenspeichern
			$oAccommodationMeal = $oCombinationAccommodation->getMeal();
			$this->_addInfo($oAccommodationMeal->getName('', true), 'accommodation', 'meal_'.$oAccommodationMeal->id);			
		}	
	}
	
	/**
	 * speichert Transferinformationen zwischen
	 * @param Ext_TS_Enquiry_Offer $oOffer 
	 */
	protected function _addTransferInformation($oOffer) {
		$aCombinationTransfers = $oOffer->getTransfers();
		// Kombinationstransfere durchlaufen
		foreach($aCombinationTransfers as $oCombinationTransfer) {
			// Transferholen
			$oTransferFormat = new Ext_TS_Enquiry_Combination_Gui2_Format_Transfer();
			$sTransfer = $oTransferFormat->format($oCombinationTransfer->transfer_type);
			// Transfer zwischenspeichern
			$this->_addInfo($sTransfer, 'transfer', $oCombinationTransfer->transfer_type);			
		}
	}
	
	/**
	 * speichert Versicherungsinformationen zwischen
	 * @param Ext_TS_Enquiry_Offer $oOffer 
	 */
	protected function _addInsuranceInformation($oOffer) {
		$aCombinationInsurances = $oOffer->getInsurances();
		// Kombinationsversicherungen durchlaufen
		foreach($aCombinationInsurances as $oCombinationInsurance) {
			// Versicherung holen und zwischenspeichern
			$oInsurance = $oCombinationInsurance->getInsurance();			
			$this->_addInfo($oInsurance->getName(), 'insurance', $oInsurance->id);
		}
	}
	
}
