<?php

class Ext_Thebing_Tuition_Attendance_Document extends Ext_Thebing_Inquiry_Document {

	protected $_sPlaceholderClass = 'Ext_Thebing_Tuition_Attendance_Document_Placeholder';

	protected $_aAttributes = [
		'inquiry_course_allocation_id' => [
			'class' => 'WDBasic_Attribute_Type_Int',
		],
		'week_from_filter' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		],
		'week_until_filter' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];
	
	/**
	 * @return Ext_Thebing_School_Tuition_Allocation[]
	 */
	public function getAllocations() {
		
		$iAllocationId = $this->inquiry_course_allocation_id;
		
		if(!empty($iAllocationId)) {
			
			$oAllocation = Ext_Thebing_School_Tuition_Allocation::getInstance($iAllocationId);
			
			$aAllocations = array($oAllocation);
			
		} else {
			
			$oInquiry = $this->getInquiry();

			if($oInquiry instanceof Ext_TS_Inquiry) {
				$aAllocations = Ext_Thebing_School_Tuition_Allocation::getAllocationIdsByInquiry($oInquiry);
			}
			
		}
		
		return $aAllocations;
	}
	
}