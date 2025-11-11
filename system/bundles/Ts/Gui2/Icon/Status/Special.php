<?php

namespace Ts\Gui2\Icon\Status;

class Special extends \Ext_Gui2_View_Icon_Abstract
{

	public function getStatus(&$selectedIds, &$rowData, &$element)
	{
		// Wenn das Special von Buchungen benutzt wird / wurde, dann das Löschen-Icon ausgrauen.
		if ($element->task == 'deleteRow') {

			$amountUsed = \Ext_Thebing_School_Special::getInstance(reset($selectedIds))->getUsedQuantity();
			if ($amountUsed > 0) {
				return 0;
			}
		}

		return parent::getStatus($selectedIds, $rowData, $element);
	}

}