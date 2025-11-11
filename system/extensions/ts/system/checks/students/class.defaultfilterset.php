<?php

class Ext_TS_System_Checks_Students_DefaultFilterset extends Ext_Thebing_System_Checks_Enquiry_Filterset {

	public function getTitle() {
		$sTitle = 'Standard filter elements';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Predefined filters are inserted for studentlists';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$aLangs			= $this->_getTranslationLanguages();
		$aUserGroups	= $this->_getUserGroups();		
		
		/**
		 * Einstellungen der Filtersets
		 */
		$aFiltersetData = array(
			'ts_students_simple' => array(
				'timefilter_diff_past'		=> 3,
				'timefilter_diff_future'	=> 10,
				'interval'					=> 'day',
				'first_element'				=> 'course_contact_original' // Leistungszeitraum
			),
			'ts_students_arrival' => array(
				'timefilter_diff_past'		=> 3,
				'timefilter_diff_future'	=> 10,
				'interval'					=> 'day',
				'first_element'				=> 'first_course_start_original' // Kursbeginn
			),
			'ts_students_departure' => array(
				'timefilter_diff_past'		=> 3,
				'timefilter_diff_future'	=> 10,
				'interval'					=> 'day',
				'first_element'				=> 'course_last_end_original' // Kursende
			),
			'ts_students_visum' => array(
				'timefilter_diff_past'		=> 6,
				'timefilter_diff_future'	=> 8,
				'interval'					=> 'month',
				'first_element'				=> 'visum_contact_original' // Leistungszeitraum
			),
		);
			
		/**
		 * Verfügbare Felder für den Zeitfilter
		 */
		$aTimeFilterElements = array(
			'accommodation_first_start_original',
			'accommodation_last_end_original',	
			'first_course_start_original',
			'course_last_end_original',
			'course_contact_original',
			'all_start_original',
			'all_end_original',
			'document_date_original',
			'visum_contact_original',						
			'created_original',
		);
		
		foreach($aFiltersetData as $sFiltersetKey => $aData) {
			
			$oFilterset = new Ext_TC_Gui2_Filterset();
			
			// Prüfen, ob es für diesen Bereich schon ein Filterset gibt
			$bExists = $this->_checkFilterset($sFiltersetKey, $oFilterset);
			if($bExists === true) {
				continue;
			}
			
			// Filterzeile erstellen
			$oFilterset->name = 'Default';
			$oFilterset->application = $sFiltersetKey;
			$oBar = $oFilterset->getJoinedObjectChild('bars');
			$oBar->name = 'all Users';
			$oBar->usergroups = $aUserGroups;
			
			##############
			## Inputfilter
			##############
			
			// Suche
			$oElement	= $oBar->getJoinedObjectChild('elements');			
			foreach($aLangs as $aLang){
				$oElement->setI18NName(Ext_TC_L10N::t('Suche', $aLang['iso']), $aLang['iso'], 'label');
			}
			$oElement->type = 'input';
			$oElement->display_label = 1;
			$oElement->basedon = array('group', 'customerNumber', 'document_number', 'customer_lastname', 'customer_firstname', 'email', 'customer_city', 'customer_address', 'customer_address_addon', 'customer_address_state');
			
			##############
			## Zeitfilter
			##############
			
			$oElement	= $oBar->getJoinedObjectChild('elements');
			foreach($aLangs as $aLang){
				$oElement->setI18NName(Ext_TC_L10N::t('Zeitraum', $aLang['iso']), $aLang['iso'], 'label');
			}
			
			$aBasedOn = array($aData['first_element']);
			foreach($aTimeFilterElements as $sKey) {
				if($sKey != $aData['first_element']) {
					$aBasedOn[] = $sKey;
				}
			}

			$oElement->type						= 'date';
			$oElement->display_label			= 0;
			$oElement->basedon					= $aBasedOn;
			$oElement->timefilter_from_count	= $aData['timefilter_diff_past'];
			$oElement->timefilter_from_type		= $aData['intervall'];
			$oElement->timefilter_until_count	= $aData['timefilter_diff_future'];
			$oElement->timefilter_until_type	= $aData['interval'];
			
			################
			## Selectfilter
			################
			
			// Visum
			$this->_createSelectFilter('Visum', 'visum_status_original', $oBar, $aLangs);

			// Schülerstatus
			$this->_createSelectFilter('Schülerstatus', 'customer_status_original', $oBar, $aLangs);
			
			// Zahlungsstatus
			$this->_createSelectFilter('Zahlungsstatus', 'filter_payment_status', $oBar, $aLangs);
			
			// Art der Buchung
			$this->_createSelectFilter('Art der Buchung', 'filter_booking_type', $oBar, $aLangs);
			
			// Special
			$this->_createSelectFilter('Special', 'filter_special', $oBar, $aLangs);
			
			// Land
			$this->_createSelectFilter('Land', 'customer_country_original', $oBar, $aLangs);
			
			// Agentur
			$this->_createSelectFilter('Agentur', 'agency_id', $oBar, $aLangs);
			
			// Aufmerksam
			$this->_createSelectFilter('Aufmerksam', 'filter_referrer', $oBar, $aLangs);
								
			$oFilterset->save();
			
		}		
		
		return true;
	}
		
}