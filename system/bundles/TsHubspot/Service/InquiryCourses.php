<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;

class InquiryCourses extends Api {

	const HUBSPOT_OBJECT_KEY = 'hubspot_courses';
	const HUBSPOT_FIELD_PROPERTIES_KEY = 'hubspot_course_fields';
	const HUBSPOT_CUSTOMFIELD_PROPERTIES_KEY = 'hubspot_course_customfields';
	const HUBSPOT_ADDITIONAL_SERVICES_PROPERTIES_KEY = 'hubspot_course_additional_services';
	const SERVICE_STRING = 'course';

	// Übersetzung wo anders
	const SERVICE_FOR_ERROR = 'Kursbuchung';

	public function update(\Ext_TS_Inquiry_Journey_Course $course) {
		$helper = new General();
		return $helper->prepareServiceUpdate($course, self::HUBSPOT_OBJECT_KEY, $this->oHubspot);
	}

	public static function getServicesByInquiry(\Ext_TS_Inquiry $inquiry) {
		return $inquiry->getCourses();
	}
}