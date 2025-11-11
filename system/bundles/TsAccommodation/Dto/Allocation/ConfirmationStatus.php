<?php

namespace TsAccommodation\Dto\Allocation;

use Carbon\Carbon;

class ConfirmationStatus
{
	public function __construct(
		private \Ext_Thebing_Accommodation_Allocation $allocation,
		private ?Carbon $confirmDate
	) {}

	public function getDate(): ?Carbon
	{
		return $this->confirmDate;
	}

	public function isConfirmed(): bool
	{
		// TODO $changedDate abfragen?
		return $this->confirmDate !== null;
	}

	public function getColor(): ?string
	{
		$journeyAccommodation = $this->allocation->getInquiryAccommodation();
		$activeAllocations = array_map(fn ($allocation) => $allocation->id, $journeyAccommodation->getAllocations());
		return self::getColorByValues($this->allocation->id, $this->confirmDate, $this->allocation->getAllocationChangedDate(), $activeAllocations);
	}

	public static function getColorByValues(int $allocationId, ?Carbon $confirmDate, ?Carbon $changedDate, array $activeAllocations): ?string
	{
		if ($confirmDate) {
			if ($changedDate && $confirmDate < $changedDate) {
				return \Ext_Thebing_Util::getColor('bad');
			} else {
				return \Ext_Thebing_Util::getColor('good');
			}
		} else if (in_array($allocationId, $activeAllocations)) {
			return \Ext_Thebing_Util::getColor('bad');
		}

		return null;
	}

}