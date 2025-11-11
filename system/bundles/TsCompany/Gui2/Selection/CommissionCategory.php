<?php

namespace TsCompany\Gui2\Selection;

class CommissionCategory extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		/* @var \Ext_Thebing_Agency_Provision_Group $oWDBasic */
		$categories = $oWDBasic->getAgency()->getAvailableCommissionCategories(true);

		return \Ext_Thebing_Util::addEmptyItem($categories);
	}

}

