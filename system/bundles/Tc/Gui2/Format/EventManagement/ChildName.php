<?php

namespace Tc\Gui2\Format\EventManagement;

use Tc\Entity\EventManagement;

class ChildName extends \Ext_Gui2_View_Format_Abstract {

	public function format($value, &$column = null, &$resultData = null) {

		if (method_exists($value, 'toReadable')) {
			$entity = match ($resultData['type']) {
				EventManagement\Condition::TYPE => EventManagement\Condition::getInstance($resultData['id']),
				EventManagement\Listener::TYPE => EventManagement\Listener::getInstance($resultData['id']),
				default => throw new \InvalidArgumentException('Unknown child type "'.$resultData['type'].'"')
			};

			$value = $value::toReadable($entity);
		} else if (method_exists($value, 'getTitle')) {
			$value = $value::getTitle();
		}

		return $value;
	}

}
