<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;

class InquiryTransfers extends Api {

	const HUBSPOT_OBJECT_KEY = 'hubspot_transfers';
	const HUBSPOT_FIELD_PROPERTIES_KEY = 'hubspot_transfer_fields';
	const SERVICE_STRING = 'transfer';

	// Übersetzung wo anders
	const SERVICE_FOR_ERROR = 'Transferbuchung';

	public function update(\Ext_TS_Inquiry_Journey_Transfer $transfer) {
		$helper = new General();
		return $helper->prepareServiceUpdate($transfer, self::HUBSPOT_OBJECT_KEY, $this->oHubspot);
	}

	public static function getServicesByInquiry(\Ext_TS_Inquiry $inquiry) {
		return $inquiry->getTransfers();
	}
}