<?php

namespace Ts\Dto\Transfer;

use Carbon\Carbon;

class ConfirmationStatus
{
	public function __construct(
		private \Ext_TS_Inquiry_Journey_Transfer $transfer,
		private ?Carbon $confirmDate
	) {}

	public function getDate(): ?Carbon
	{
		return $this->confirmDate;
	}

	public function isConfirmed(): bool
	{
		return $this->confirmDate !== null;
	}

	public function getColor(): ?string
	{
		$cancellationDate = $this->transfer->getJourney()?->getInquiry()?->getCancellationDate();
		return self::getColorByValues($this->confirmDate, $this->transfer->getProviderUpdatedDate(), $cancellationDate);
	}

	public static function getColorByValues(?Carbon $confirmDate, ?Carbon $changedDate, ?Carbon $cancellationDate): ?string
	{
		if(
			$confirmDate &&
			(
				($changedDate && $changedDate > $confirmDate) ||
				($cancellationDate && $cancellationDate > $confirmDate)
			)
		){
			return \Ext_Thebing_Util::getColor('neutral'); // gelb
		} else if ($confirmDate){
			return \Ext_Thebing_Util::getColor('good'); // grün
		}

		return \Ext_Thebing_Util::getColor('bad');
	}

}