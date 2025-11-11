<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;

class InquiryPayments extends Api
{

	const HUBSPOT_OBJECT_KEY = 'hubspot_payments';
	const HUBSPOT_FIELD_PROPERTIES_KEY = 'hubspot_payment_fields';
	const SERVICE_STRING = 'payment';

	// Übersetzung wo anders
	const SERVICE_FOR_ERROR = 'Buchungszahlungen';

	public function update(\Ext_Thebing_Inquiry_Payment $payment)
	{
		$helper = new General();
		return $helper->prepareServiceUpdate($payment, self::HUBSPOT_OBJECT_KEY, $this->oHubspot);
	}

	public static function getServicesByInquiry(\Ext_TS_Inquiry $inquiry)
	{
		return $inquiry->getPayments();
	}
}