<?php

namespace TsApi\Handler;

class Enquiry extends AbstractHandler {
	
	protected $type = \Ext_TS_Inquiry::TYPE_ENQUIRY;
	protected $typeString = \Ext_TS_Inquiry::TYPE_ENQUIRY_STRING;

	protected array $flexFieldsUsage = ['enquiry', 'enquiry_booking'];

	#[\Override]
	public function buildInquiry(): \Ext_TS_Inquiry {
		
		$oEnquiry = new \Ext_TS_Inquiry();
		$oEnquiry->type = \Ext_TS_Inquiry::TYPE_ENQUIRY;
		$oEnquiry->currency_id = $this->oSchool->getCurrency();
		$oEnquiry->payment_method = 1;

		$oJourney = $oEnquiry->getJourney();
		$oJourney->school_id = $this->oSchool->id;
		$oJourney->productline_id = $this->oSchool->getProductLineId();
		$oJourney->type = \Ext_TS_Inquiry_Journey::TYPE_DUMMY;
		
		return $oEnquiry;
	}
	
}
