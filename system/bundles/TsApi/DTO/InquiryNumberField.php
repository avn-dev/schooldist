<?php

namespace TsApi\DTO;

class InquiryNumberField extends ApiField {

	public function setValue($entity, $value, array $objectsByAlias = []): void {
		
		$numberrange = \Ts\Service\Numberrange\Booking::getObject($entity);
		
		$entity->number = $value;
		$entity->numberrange_id = $numberrange->id;
		
	}
	
}
