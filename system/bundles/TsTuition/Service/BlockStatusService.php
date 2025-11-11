<?php

namespace TsTuition\Service;

use TsTuition\Entity\Block\Unit;
use Core\Entity\ParallelProcessing\Stack;

readonly class BlockStatusService
{
	public function __construct(
		private \Ext_Thebing_School_Tuition_Block $block
	) {}

	public function lazyUpdate(int $prio = 1): void
	{
		if (!$this->block->exist() || !$this->block->isActive()) {
			return;
		}

		$payload = [
			'id' => $this->block->id
		];

		Stack::getRepository()->writeToStack('ts-tuition/block-status-flag', $payload, $prio);
	}

	public function update(): void
	{
		$allocationIds = array_map(fn ($allocation) => $allocation->id, $this->block->getAllocations());

		if (empty($allocationIds)) {
			return;
		}

		foreach ($this->block->days as $day) {

			$unit = $this->block->getUnit($day);

			if ($unit->isCancelled()) {
				continue;
			}

			// Sobald es eine Anwesenheit für den Tag gibt gilt die Einheit als stattgefunden
			$sql = "SELECT `id`, #day FROM `kolumbus_tuition_attendance` WHERE #day IS NOT NULL AND `allocation_id` IN (:allocation_ids) AND `active` = 1 LIMIT 1";
			$firstAttendance = \DB::getQueryRow($sql, ['day' => \Ext_TC_Util::convertWeekdayToString($day), 'allocation_ids' => $allocationIds]);

			$save = true;
			if (!empty($firstAttendance) && !$unit->hasState(Unit::STATE_HAS_TAKEN_PLACE)) {
				$unit->addState(Unit::STATE_HAS_TAKEN_PLACE);
			} else if ($unit->hasState(Unit::STATE_HAS_TAKEN_PLACE)) {
				$unit->removeState(Unit::STATE_HAS_TAKEN_PLACE);
			} else {
				$save = false;
			}

			if ($save) {
				$unit->lock()->save();
			}
		}
	}
}