<?php

namespace TsCompany\Gui2\Selection;

use TsCompany\Entity\Industry;

class CompanyIndustries extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$industries = [];

		if($oWDBasic->company_id > 0) {
			$company = $oWDBasic->getCompany();
			// Nur die zur ausgewählten Firma gehörenden Branchen
			$industries = array_intersect_key(Industry::getSelectOptions(), array_flip($company->industries));
		}

		return $industries;
	}

}
