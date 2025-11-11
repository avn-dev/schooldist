<?php

namespace Ts\Gui2\Selection\Special;

class Accommodations extends MultipleSchoolsAbstract {
	
	public function getSchoolOptions(\Ext_Thebing_School $school): array {
		
		return $school->getAccommodationCombinations();		
	}
	
}
