<?php

namespace Ts\Gui2\Icon\Status;
class SpecialCodes extends \Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$selectedIds, &$rowData, &$element) {

		if(count($selectedIds) > 0) {

			$firstRow = reset($rowData);

			if(
				$element->task == 'deleteRow' &&
				$firstRow['latest_use'] !== null
			) {
				return false;
			}

			return true;
		}

		return parent::getStatus($selectedIds, $rowData, $element);
	}

}