<?php

namespace TsCompany\Gui2\Icon\JobOpportunity;

use Core\Helper\BundleConfig;
use TsCompany\Entity\JobOpportunity\StudentAllocation as StudentAllocationEntity;
use Core\Helper\BitwiseOperator;

class StudentAllocationStatus extends \Ext_Gui2_View_Icon_Abstract {

	/**
	 * @param $selectedIds
	 * @param $rowData
	 * @param $element
	 * @return bool|int
	 */
	public function getStatus(&$selectedIds, &$rowData, &$element) {

		$selectedId = $selectedIds[0] ?? 0;
		$row = $rowData[0] ?? null;

		if (
			!$element->active &&
			$element->action !== 'changeStatus'
		) {
			return $selectedId > 0;
		}

		if ($row !== null) {
			$allocated = StudentAllocationEntity::query()
				->where('inquiry_course_id', $row['inquiry_course_id'])
				->where('program_service_id', $row['program_service_id'])
				->where('status', '&', StudentAllocationEntity::STATUS_ALLOCATED)
				->first();

			if ($allocated !== null) {
				// Es gibt bereits ein final zugewiesenes Arbeitsangebot, keine weiteren Aktionen mehr möglich
				return false;
			}
		}

		if ($element->action === 'changeStatus') {

			if ($selectedId === 0) {
				return false;
			}

			$buttonStatus = (int)$element->additional;

			$order = BundleConfig::of('TsCompany')->get('student_allocation.status_order');

			// Den nächstmöglichen Status ermitteln
			$next = null;
			foreach ($order as $status) {
				if ($status > $row['status'] && !BitwiseOperator::has($row['status'], $status)) {
					$next = $status;
					break;
				}
			}

			if ($buttonStatus != $next) {
				// Button entspricht nicht dem nächstmöglichen Status
				return false;
			}

		}

		return parent::getStatus($selectedIds, $rowData, $element);
	}

}
