<?php

namespace TsTuition\Gui2\Data\Examination;

class SectionCategories extends \Ext_Thebing_Gui2_Data {
	
	protected function manipulateSqlParts(array &$aSqlParts, string $sView) {
		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT `schools`.`school_id`) `schools`
		";
	}
	
}
