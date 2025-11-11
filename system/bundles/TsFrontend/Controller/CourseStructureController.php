<?php

namespace TsFrontend\Controller;

use Illuminate\Http\Request;

class CourseStructureController extends \MVC_Abstract_Controller {
	
	protected $_sAccessRight = ['ts_admin_frontend_course_structure'];
	
	public function page() {

		$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		
		$languages = $school->getLanguageList(true);

		$courseStructure = \TsFrontend\Service\CourseStructure::getInstance($school);
		
		$structure = $courseStructure->getStructure();
		
		$transfer = [
			'languages' => $languages,
			'default' => $courseStructure->isDefault(),
			'school' => $school,
			'structure' => $structure,
			'courses' => $school->getCourses()
		];
		
		return response()->view('course_structure/page', $transfer);
	}
	
	public function save(\MVC_Request $request) {
		
		$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		
		$data = $request->getJSONDecodedPostData();
		
		$courseStructure = \TsFrontend\Service\CourseStructure::getInstance($school);
		
		$courseStructure->saveRequestData($data);
		
		return response()->json(['success'=>true]);
	}
	
	public function reset() {
		
		$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		
		$courseStructure = \TsFrontend\Service\CourseStructure::getInstance($school);
		
		$courseStructure->reset();
		
		return response()->json(['success'=>true]);		
	}
	
}
