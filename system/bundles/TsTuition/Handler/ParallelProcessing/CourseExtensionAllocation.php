<?php

namespace TsTuition\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class CourseExtensionAllocation extends TypeHandler {
	
	public function getLabel() {
		return \L10N::t('Automatische Klassenzuweisung', 'School');
	}

	public function execute(array $data, $debug = false) {
		
		$inquiry = \Ext_TS_Inquiry::getInstance($data['inquiry_id']);
		
		$school = $inquiry->getSchool();
		
		if($school->tuition_automatic_course_extension_allocation) {
			$service = new \TsTuition\Service\CourseExtensionService();
			$service->extendAllocations($inquiry);
		}
		
		
//		$inquiryCourses = $inquiry->getCourses();
//		
//		// Gleiche, zusammenhängende Kurse checken
//		foreach($inquiryCourses as $inquiryCourse) {
//			
//			__pout($inquiryCourse->aData);
//			
//			$services = $inquiryCourse->getProgram()->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);
//			
//			foreach($services as $service) {
//				__pout($service->aData);
//				
//				$allocations = \Ext_Thebing_School_Tuition_Allocation::query()->where('inquiry_course_id', $inquiryCourse->id)
//					->where('program_service_id', $service->id)
//					->get();
//				
//				__pout($allocations);
//			}
//
//		}
		
	}

}
