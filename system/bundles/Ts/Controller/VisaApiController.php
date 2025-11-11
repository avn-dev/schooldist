<?php

namespace Ts\Controller;

use Ts\Handler\VisaLetterVerification\ExternalApp;

class VisaApiController extends \Illuminate\Routing\Controller {

	public function handleVisaQrCode($uniqueKey) {

		$portalHost = \System::d('visa_host', 'visa.fidelo.com');

		if (gethostbyname($portalHost) !== request()->ip()) {
			return response('Unauthorized', 401);
		}

		$inquiry = \Ext_TS_Inquiry::query()
			->where('unique_key', $uniqueKey)
			->get()
			->first();

		if (empty($inquiry)) {
			return response('Booking not found', 404);
		}

		/** @var \Ext_Thebing_School $school */
		$school = $inquiry->getSchool();

		$logo = $school->getSchoolFileDir().'/'.$school->getMeta(ExternalApp::KEY_LOGO);
		if(is_file($logo)) {
			$mimeType = mime_content_type($logo);
			$customerData['school_logo'] = 'data:'.$mimeType.';base64,'.base64_encode(file_get_contents($logo));
		}

		$customerData['school_name'] = $school->getName();
		$customerData['student_number'] = $inquiry->getFirstTraveller()->getCustomerNumber();
		$customerData['student_name'] = $inquiry->getName();

		$courses = $inquiry->getCourses();

		$courseInfos = [];
		foreach ($courses as $course) {
			$courseInfos[$course->id] = $course->getInfo();
		}

		$accommodations = $inquiry->getAccommodations();

		$accommodationInfos = [];
		foreach ($accommodations as $accommodation) {
			$accommodationInfos[$accommodation->id] = $accommodation->getInfo();
		}

		$customerData['student_courses'] = $courseInfos;
		$customerData['student_accommodations'] = $accommodationInfos;

		return response()->json($customerData);
	}

}