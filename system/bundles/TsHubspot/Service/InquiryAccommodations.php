<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;

class InquiryAccommodations extends Api {

	const HUBSPOT_OBJECT_KEY = 'hubspot_accommodations';
	const HUBSPOT_FIELD_PROPERTIES_KEY = 'hubspot_accommodation_fields';
	const HUBSPOT_ADDITIONAL_SERVICES_PROPERTIES_KEY = 'hubspot_accommodation_additional_services';
	const SERVICE_STRING = 'accommodation';

	// Übersetzung wo anders
	const SERVICE_FOR_ERROR = 'Unterkunftsbuchung';

	public function update(\Ext_TS_Inquiry_Journey_Accommodation $accommodation) {
		$helper = new General();
		return $helper->prepareServiceUpdate($accommodation, self::HUBSPOT_OBJECT_KEY, $this->oHubspot);
	}

	public static function getServicesByInquiry(\Ext_TS_Inquiry $inquiry) {
		return $inquiry->getAccommodations();
	}
}