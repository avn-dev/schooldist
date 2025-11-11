<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;

class InquiryInsurances extends Api {

	const HUBSPOT_OBJECT_KEY = 'hubspot_insurances';
	const HUBSPOT_FIELD_PROPERTIES_KEY = 'hubspot_insurance_fields';
	const SERVICE_STRING = 'insurance';

	// Übersetzung wo anders
	const SERVICE_FOR_ERROR = 'Versicherungsbuchung';

	public function update(\Ext_TS_Inquiry_Journey_Insurance $insurance) {
		$helper = new General();
		return $helper->prepareServiceUpdate($insurance, self::HUBSPOT_OBJECT_KEY, $this->oHubspot);
	}

	public static function getServicesByInquiry(\Ext_TS_Inquiry $inquiry) {
		return $inquiry->getInsurances();
	}
}