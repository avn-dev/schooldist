<?php

namespace Ts\Gui2\Selection\Special;

class AdditionalCosts extends MultipleSchoolsAbstract {
	
	public function getSchoolOptions(\Ext_Thebing_School $school): array {
		
		$sLang = \Ext_Thebing_School::fetchInterfaceLanguage();
		
		return $school->getAdditionalCosts($sLang);		
	}
	
}
