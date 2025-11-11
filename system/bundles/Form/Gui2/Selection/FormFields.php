<?php

namespace Form\Gui2\Selection;

class FormFields extends \Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aFormTypes = \Form\Gui2\Data\Fields::getTypes();
		
		$aOptions = [];
		
		$sSql = "
			SELECT 
				* 
			FROM 
				`form_options` 
			WHERE 
				`form_id` = ".(int)$oWDBasic->form_id." AND 
				`active` = 1 AND
				`type` IN ('checkbox', 'radio', 'select', 'reference')
			ORDER BY 
				`position`
		";
		
		$aFields = (array)\DB::getQueryRows($sSql);
		foreach($aFields as $aField) {
			$aOptions[$aField['id']] = $aField['name']." (ID: ".$aField['id'].", Typ: ".$aFormTypes[$aField['type']].")";
		}

		$aOptions = \Util::addEmptyItem($aOptions);
		
		return $aOptions;
	}
	
}
