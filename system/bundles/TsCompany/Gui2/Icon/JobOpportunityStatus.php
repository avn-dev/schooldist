<?php

namespace TsCompany\Gui2\Icon;

use TsCompany\Entity\JobOpportunity\StudentAllocation;

class JobOpportunityStatus extends \Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$selectedIds, &$rowData, &$element) {

		if(count($selectedIds) > 0) {

			$selectedId = reset($selectedIds);

			if(
				$element->task == 'deleteRow' &&
				null !== StudentAllocation::query()->where('job_opportunity_id', $selectedId)->first()
			) {
				return false;
			}

			return true;
		}

		return parent::getStatus($selectedIds, $rowData, $element);
	}
}
