<?php

namespace Ts\Gui2\Selection\Inquiry;

class SubAgencies extends \TsCompany\Gui2\Selection\SubAgencies {

	public function getDefaultValue(\Ext_TS_Inquiry $oWDBasic) {
		
		$agency = $oWDBasic->getAgency();

		if($agency) {
			return $agency->subagency_id;
		}
		
		return 0;
	}
	
}

