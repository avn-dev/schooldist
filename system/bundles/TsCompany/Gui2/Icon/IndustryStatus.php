<?php

namespace TsCompany\Gui2\Icon;

use TsCompany\Entity\Industry;

class IndustryStatus extends \Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$selectedIds, &$rowData, &$element) {

		if(!$element->active) {

			if (count($selectedIds) > 0) {
				$industryId = (int)reset($selectedIds);
				$industry = Industry::getInstance($industryId);

				if (
					$element->task == 'deleteRow' &&
					$industry->isUsedByCompany()
				) {
					// Löschen nicht möglich solange der Eintrag noch mit einer Firma verknüpft ist
					return false;
				}

				return true;
			}

			return false;
		}

		return parent::getStatus($selectedIds, $rowData, $element);
	}
}
