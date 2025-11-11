<?php

namespace Ts\Gui2\Selection\Special;

class Transfers extends MultipleSchoolsAbstract {
	
	public function getSchoolOptions(\Ext_Thebing_School $school): array {
		
		return $school->getTransferPackages(true);		
	}
	
}
