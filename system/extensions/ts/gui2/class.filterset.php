<?php

class Ext_TS_Gui2_Filterset extends Ext_TC_Gui2_Filterset {
	
	/**
     * get all availaible Applications for filtersets
     * @return array 
     */
	public static function getApplications() {
		
		// key entspricht der Config Datei falls vorhanden
		$aApplications = array(
			'ts_enquiry' => L10N::t('Anfragen', static::TRANSLATION_PATH),
			'ts_inquiry_group' => L10N::t('Buchungen: Gruppen', static::TRANSLATION_PATH),
			'ts_inquiry_document'=> L10N::t('Rechnungsübersicht pro Buchung', static::TRANSLATION_PATH),
			'ts_booking_stack' => L10N::t('Buchungsstapel', static::TRANSLATION_PATH),
			'ts_inquiry_journey_courses' => L10N::t('Klassenplanung: Gebuchte Kurse', static::TRANSLATION_PATH),
		);
		
		return $aApplications;
	}
	
}
