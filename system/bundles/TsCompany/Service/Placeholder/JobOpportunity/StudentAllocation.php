<?php

namespace TsCompany\Service\Placeholder\JobOpportunity;

class StudentAllocation extends \Ext_TC_Placeholder_Abstract {

	protected $_aSettings = [
		'variable_name' => 'oJobAllocation'
	];

	protected $_aPlaceholders = [
		'job_allocation_status' => [
			'label' => 'Status',
			'type' => 'field',
			'source' => 'status',
			'format' => \TsCompany\Gui2\Format\JobOpportunity\StudentAllocationStatus::class
		],
		'job_opportunity' => [
			'label' => 'Arbeitsangebot',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getJobOpportunity',
			'variable_name' => 'oJobOpportunity'
		],
		'inquiry_course' => [
			'label' => 'Gebuchter Kurs',
			'type' => 'parent',
			'parent' => 'method',
			'source' => 'getInquiryCourse',
			'variable_name' => 'oInquiryCourse'
		],
		'job_allocations_loop' => [
			'label' => 'Alle Arbeitsangebote des Schülers',
			'type' => 'loop',
			'loop' => 'method',
			'source' => 'getAllStudentAllocations',
			'class' => \TsCompany\Entity\JobOpportunity\StudentAllocation::class
		]
	];

	/*public function setFlexiblePlaceholder($bAssign = false) {

		// Den Loop über die alle Zuweisungen nur in der ersten Ebene einbauen, ansonsten würde der Loop rekursiv in der
		// Platzhalterliste eingebaut werden
		if ($this->_oWDBasic->getPlaceholderParentEntity() === null) {
			$this->_aPlaceholders['job_allocations_loop'] = [
				'label' => 'Alle Arbeitsangebote des Schülers',
				'type' => 'loop',
				'loop' => 'method',
				'source' => 'getAllStudentAllocations',
				'class' => \TsCompany\Entity\JobOpportunity\StudentAllocation::class
			];
		}

		parent::setFlexiblePlaceholder($bAssign);
	}*/
}
