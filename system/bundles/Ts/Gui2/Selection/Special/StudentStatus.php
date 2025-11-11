<?php

namespace Ts\Gui2\Selection\Special;

class StudentStatus extends MultipleSchoolsAbstract {
	
	public function getSchoolOptions(\Ext_Thebing_School $school): array {
		
		return $school->getCustomerStatusList();		
	}
	
}
