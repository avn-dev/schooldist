<?php

namespace Ts\Gui2\Selection\Special;

class Courses extends MultipleSchoolsAbstract {
	
	public function getSchoolOptions(\Ext_Thebing_School $school): array {
		
		return $school->getCourseList();		
	}
	
}
