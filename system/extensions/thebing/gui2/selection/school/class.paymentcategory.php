<?php

class Ext_Thebing_Gui2_Selection_School_PaymentCategory extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * {@inheritdoc}
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aSelectedSchoolIds = [];

		if($oWDBasic instanceof \Ts\Entity\AccommodationProvider\Payment\Category\Validity) {
			$oAccomodation = \Ext_Thebing_Accommodation::getInstance($oWDBasic->provider_id);
			if($oAccomodation->exist()) {
				// TODO #9834 - hier kommen die Schulen raus die gespeichert sind, nicht das was aktuell im Dialog ausgewählt ist
				$aSelectedSchoolIds = $oAccomodation->getJoinTableData('schools');
			}
		}

		// Wenn keine Schulen ausgewählt sind sollen gar keine Kategorien auswählbar sein
		if(empty($aSelectedSchoolIds)) {
			return [];
		}

		$aPaymentCategories = \Ts\Entity\AccommodationProvider\Payment\Category::getRepository()->findAll();

		$aBack = [];
		foreach($aPaymentCategories as $oPaymentCategory) {
			foreach($aSelectedSchoolIds as $iSelectedSchoolId) {
				if(!in_array($iSelectedSchoolId, $oPaymentCategory->schools)) {
					/*
					 * Die Kategorie ist für die gewählte Schule nicht verfügbar, es sollen nur Kategorien
					 * auswählbar sein die für alle gewählten Schulen verfügbar sind.
					 */
					continue 2;
				}
			}
			$aBack[$oPaymentCategory->id] = $oPaymentCategory->name;
		}
		
		asort($aBack);
		
		return $aBack;
	}

}
