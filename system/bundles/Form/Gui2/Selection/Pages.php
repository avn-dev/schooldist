<?php

namespace Form\Gui2\Selection;

class Pages extends \Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aOptions = (array)\DB::getQueryPairs("SELECT `id`, `name` FROM `form_pages` WHERE `form_id` = ".(int)$oWDBasic->form_id." AND `active` = 1 ORDER BY `position`");
		$aOptions = \Util::addEmptyItem($aOptions);
		
		return $aOptions;
	}
	
}
