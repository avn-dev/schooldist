<?php

namespace TsApi\DTO;

class ContactNumberField extends ApiField {
	
	public function setValue($entity, $value, array $objectsByAlias = []): void {
		
		$traveller = $entity->getTraveller();
		
		// Nummern per API/Import nicht überschreiben
		if($traveller->getCustomerNumber() !== null) {
			return;
		}
		
		$school = $entity->getSchool();
	
		$customerNumberGenerator = new \Ext_Thebing_Customer_CustomerNumber($entity);
        $numberrangeApplication = $customerNumberGenerator->getApplicationByType();
        $numberrange = \Ext_TS_Numberrange_Contact::getByApplicationAndObject($numberrangeApplication, $school->id);
		
		$traveller->numbers = [
			[
				'number' => $value,
				'numberrange_id' => $numberrange->id ?? 0
			]
		];
		
	}
	
}
