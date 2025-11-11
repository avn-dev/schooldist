<?php

namespace Tc\Gui2\Icon;

use Tc\Interfaces\EventManager\TestableEvent;

class EventManagementIcon extends \Ext_Gui2_View_Icon_Abstract
{
	/**
	 * @param array $selectedIds
	 * @param array $rowData
	 * @param $element
	 * @return bool
	 */
	public function getStatus(&$selectedIds, &$rowData, &$element)
	{
		if (!empty($selectedIds) && $element->action === 'testing') {
			return is_a($rowData[0]['event_name'], TestableEvent::class, true);
		}

		return parent::getStatus($selectedIds, $rowData, $element);
	}

}