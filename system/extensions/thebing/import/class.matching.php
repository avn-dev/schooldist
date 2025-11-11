<?php

/**
 * v1
 */
class Ext_Thebing_Import_Matching {
	
	protected $aMatching = array(
		'inquiry' => array(),
		'inquiry_group' => [],
		'course' => array(),
		'flex' => array(),
		'visum' => array(),
		'contact' => array(),
		'contact_email' => array(),
		'contact_detail' => array(),
		'contact_address' => array(),
		'emergency_contact' => array(),
		'emergency_contact_detail' => array(),
		'emergency_contact_email' => array(),
		'matching_details' => array(),
		'customer_number' => array(),
		'transfer' => [],
		'agency' => [],
		'agency_contact' => [],
		'accommodation' => [],
		'document' => [],
		'document_version' => [],
		'invoice' => [],
		'invoice_item' => [],
		'invoice_version'=>[],
		'payment' => [],
		'payment_grouping' => [],
		'insurance' => []
	);
	
	public function setMatching($sKey, array $aMatching) {
		if(array_key_exists($sKey, $this->aMatching)) {
			$this->aMatching[$sKey] = $aMatching;
		} else {
			throw new Exception('Matching "'.$sKey.'" does not exists!');
		}
	}
	
	public function getMatching($sKey) {
		if(array_key_exists($sKey, $this->aMatching)) {
			return $this->aMatching[$sKey];
		} else {
			throw new Exception('Matching "'.$sKey.'" does not exists!');
		}
	}

	public function getInquiryMatching() {
		return $this->aMatching['inquiry'];
	}

	public function getFlexMatching() {
		return $this->aMatching['flex'];
	}

	public function getVisumMatching() {
		return $this->aMatching['visum'];
	}

	public function getContactMatching() {
		return $this->aMatching['contact'];
	}

	public function getContactEmailMatching() {
		return $this->aMatching['contact_email'];
	}

	public function getContactDetailMatching() {
		return $this->aMatching['contact_detail'];
	}

	public function getContactAddressMatching() {
		return $this->aMatching['contact_address'];
	}

	public function getEmergencyContactMatching() {
		return $this->aMatching['emergency_contact'];
	}

	public function getEmergencyContactDetailMatching() {
		return $this->aMatching['emergency_contact_detail'];
	}

	public function getEmergencyContactEmailMatching() {
		return $this->aMatching['emergency_contact_email'];
	}

	public function getMatchingDetailsMatching() {
		return $this->aMatching['matching_details'];
	}

	public function getCustomerNumberMatching() {
		return $this->aMatching['customer_number'];
	}
	
	public function getCourseMatching() {
		return $this->aMatching['course'];
	}
	
	public function getInsuranceMatching() {
		return $this->aMatching['insurance'];
	}
	
	public function getAgencyMatching() {
		return $this->aMatching['agency'];
	}
	
	public function getAccommodationMatching() {
		return $this->aMatching['accommodation'];
	}
}
