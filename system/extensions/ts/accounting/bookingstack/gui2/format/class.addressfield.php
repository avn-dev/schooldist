<?php

class Ext_TS_Accounting_Bookingstack_Gui2_Format_AddressField extends Ext_Gui2_View_Format_Abstract {
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		#__out($aResultData);
		
		$bookingStackEntry = Ext_TS_Accounting_BookingStack::getInstance($aResultData['id']);
		
		if($bookingStackEntry->isPaymentEntry()) {
			return;
		}
		
		$inquiry = $bookingStackEntry->getDocument()?->getInquiry();

		if (!$inquiry) {
			return $mValue;
		}
		
		$languageObject = new \Tc\Service\Language\Frontend($this->_sLanguage);

		$documentAddress = new \Ext_Thebing_Document_Address($inquiry);
		$addressData = $documentAddress->getAddressData([
						'type' => $aResultData['address_type'],
						'type_id' => $aResultData['address_type_id']
					], $languageObject);

		switch($oColumn->db_column) {
			case 'address_firstname_lastname':
				return $addressData['document_firstname'].' '.$addressData['document_lastname'];
			case 'address_zip_city':
				return $addressData['document_zip'].' '.$addressData['document_city'];
			case 'address_name_firstname_lastname':
				$parts = [];
				if(
					!empty($addressData['document_name'])
				) {
					$parts[] = $addressData['document_name'];
				}
				if(
					!empty($addressData['document_firstname']) &&
					!empty($addressData['document_lastname'])
				) {
					$parts[] = $addressData['document_firstname'].' '.$addressData['document_lastname'];
				}
				return implode(' - ', $parts);
			default:
				$field = str_replace('address_', '', $oColumn->db_column);
				return $addressData['document_'.$field];
		}
		
		return $sName;
	}
	
}
