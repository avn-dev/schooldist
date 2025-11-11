<?php

namespace TsCompany\Gui2\Data\JobOpportunity;

use Core\Helper\BundleConfig;
use TsCompany\Entity\JobOpportunity;

class StudentAllocation extends \Ext_Thebing_Gui2_Data {

	public static function getDialog(\Ext_Thebing_Gui2 $gui2): \Ext_Gui2_Dialog {

		$dialog = $gui2->createDialog($gui2->t('Arbeitsangebote zuweisen'), $gui2->t('Arbeitsangebote zuweisen'));
		$dialog->height = 400;
		$dialog->width = 900;

		return $dialog;

	}

	public function getDialogHTML(&$iconAction, &$dialog, $selectedIds = array(), $additional = false) {

		if ($iconAction === 'additional_document') {

			global $_VARS;
			// Data-Class für den Dokumenten-Dialog setzen, geht leider nicht anders
			$_VARS['data_class'] = \TsCompany\Gui2\Data\JobOpportunity\StudentAllocation\Document::class;

		} else if ($iconAction === 'assign') {

			$dialog->aElements = [];

			// TODO - Basierend auf Buchung filtern (aktuell nicht möglich)
			$jobOpportunities = JobOpportunity::query()->where('status', 1)->get()
				->mapWithKeys(function (JobOpportunity $jobOpportunity) {
					$name = sprintf('%s (%s / %s)', $jobOpportunity->getName(), $jobOpportunity->getCompany()->getName(false), $jobOpportunity->getIndustry()->getShortName());
					return [$jobOpportunity->getId() => $name];
				});

			$parentGuiIds = $this->getParentDecodedIds();

			foreach ($parentGuiIds as $parentIds) {
				// Bereits verwendete Arbeitsangebote entfernen
				$usedJobOpportunityIds = JobOpportunity\StudentAllocation::query()
					->where('inquiry_course_id', $parentIds['inquiry_journey_course_id'])
					->where('program_service_id', $parentIds['program_service_id'])
					->pluck('job_opportunity_id');

				$jobOpportunities = $jobOpportunities->diffKeys($usedJobOpportunityIds->flip());
			}

			$dialog->setElement($dialog->createRow($this->t('Arbeitsangebote'), 'select', [
				'db_column' => 'job_opportunities',
				'select_options' => $jobOpportunities->toArray(),
				'multiple' => 5,
				'jquery_multiple' => true,
				'searchable' => true,
				'required' => true,
			]));

		}

		return parent::getDialogHTML($iconAction, $dialog, $selectedIds, $additional);
	}

	public function saveDialogData($action, $selectedIds, $data, $additional = false, $save = true) {

		if ($save && $action === 'assign') {

			$jobOpportunities = (array) $data['job_opportunities'];

			$parentGuiIds = $this->getParentDecodedIds();

			foreach ($parentGuiIds as $parentIds) {
				foreach ($jobOpportunities as $jobOpportunityId) {
					$allocation = new JobOpportunity\StudentAllocation();
					$allocation->inquiry_course_id = $parentIds['inquiry_journey_course_id'];
					$allocation->program_service_id = $parentIds['program_service_id'];
					$allocation->job_opportunity_id = (int)$jobOpportunityId;
					$allocation->save();
				}

				$this->updateInquiryIndex((int)$parentIds['inquiry_id']);
			}

			return [
				'action' => 'closeDialogAndReloadTable',
				'data' => ['id' => 'ID_'.implode('_', (!empty($selectedIds)) ? $selectedIds : [0])],
				'error' => []
			];

		}

		return parent::saveDialogData($action, $selectedIds, $data, $additional, $save);

	}

	protected function deleteRow($iRowId) {

		$deleted = parent::deleteRow($iRowId);

		if ($deleted === true) {
			$this->updateInquiryIndex($this->oWDBasic->getInquiry()->getId());
		}

		return $deleted;
	}

	protected function updateInquiryIndex(int $inquiryId) {
		\Ext_Gui2_Index_Stack::update('ts_inquiry', $inquiryId, [
			'company_id',
		]);
	}

	protected function getParentDecodedIds(): array {

		$parentGuiIds = $this->_oGui->getParentGuiIds();

		$final = array_map(function($parentId) {
			return $this->_getParentGui()->decodeId($parentId);
		}, $parentGuiIds);

		return $final;
	}

	public static function getWhere(\Ext_Thebing_Gui2 $gui2) {

		return [];

	}

	protected function requestChangeStatus($_VARS) {

		$selectedIds = $_VARS['id'] ?? [];
		$requestStatus = (int)$_VARS['additional'] ?? null;

		$selectedId = reset($selectedIds);

		/* @var JobOpportunity\StudentAllocation $allocation */
		$allocation = JobOpportunity\StudentAllocation::getRepository()->findOrFail($selectedId);

		$order = BundleConfig::of('TsCompany')->get('student_allocation.status_order');

		foreach ($order as $status) {

			if ($requestStatus === $status) {
				$allocation->addFlag('status', $requestStatus);
				break;
			}

			// Status unterhalb des gewünschten Status entfernen (siehe Reihenfolge config.php)
			$allocation->removeFlag('status', $requestStatus);
		}

		$allocation->save();

		return [
			'action' => 'loadTable'
		];
	}

}
