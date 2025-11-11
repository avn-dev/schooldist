<?php

namespace TsCompany\Handler\Communication\JobOpportunity\StudentAllocation\Tab;

use TsCompany\Entity\JobOpportunity\StudentAllocation;

/**
 * @deprecated
 */
class Tabarea extends \Ext_TC_Communication_Tab_TabArea {

	public function getRecipientSelects() {

		$className = $this->_oParent->getCommunicationObject()->getClassName('Tab_TabArea_RecipientSelect');

		$selects = [];

		if ($this->_sType === 'customer') {

			/* @var \Ext_TC_Communication_Tab_TabArea_RecipientSelect $select */
			$select = new $className($this, 'traveller');
			$select->sKey = $this->_sType;
			$selects[] = $select;

			$optionalSelects = [
				new $className($this, 'agency_contacts')
			];

			foreach ($optionalSelects as $optionalSelect) {

				$selectedIds = $this->_oParent->getCommunicationObject()->getSelectedIds();

				foreach ($selectedIds as $selectedId) {
					if (!empty($optionalSelect->getCustomers($selectedId))) {
						$selects[] = $optionalSelect;
					}
				}

			}

		} else if ($this->_sType === 'company') {

			$select = new $className($this);
			$select->sKey = $this->_sType;
			$selects[] = $select;

		}

		return $selects;

	}

	public static function getFlags() {

		$flags = [
			'company' => [
				'job_opportunity_requested' => [
					'label' => \Ext_TC_Communication::t('Arbeitsangebot angefragt')
				],
				'job_opportunity_allocated' => [
					'label' => \Ext_TC_Communication::t('Arbeitsangebot zugewiesen')
				],
			]
		];

		return $flags;
	}

	protected function validateTemplateFlag(string $sFlag): bool {

		if ($sFlag === 'job_opportunity_allocated') {

			$alreadyAllocated = collect($this->_oPreparedObjects[0]->getAllStudentAllocations())
				->first(function (StudentAllocation $allocation) {
					return $allocation->hasFlag('status', StudentAllocation::STATUS_ALLOCATED);
				});

			// Wenn es bereits eine final Zuweisung gibt kann es keine weitere Zuweisung geben
			if ($alreadyAllocated !== null) {
				return false;
			}

		}

		return true;
	}

}
