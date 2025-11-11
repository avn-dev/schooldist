<?php

namespace Tc\Gui2\Format\EventManagement;

use Tc\Entity\EventManagement;
use Tc\Facades\EventManager;

class EventName extends \Ext_Gui2_View_Format_Abstract {

	public function format($value, &$column = null, &$resultData = null) {

		if (method_exists($value, 'toReadable')) {
			$entity = EventManagement::getInstance($resultData['id']);
			$value = $value::toReadable($entity);
		} else {
			$value = EventManager::getEventTitle($value);
		}

		if (
			isset($resultData['listeners_count']) &&
			(int)$resultData['listeners_count'] === 0
		) {
			$value .= ' <i class="fa fa-warning fa-colored" title="'.EventManager::l10n()->translate('Keine Aktionen definiert').'"></i>';
		}

		return $value;
	}

}
