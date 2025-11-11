<?php

namespace TsReporting\Generator\Groupings\Document;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Groupings\Booking\Accommodation as BookingAccommodation;
use TsReporting\Generator\Scopes\Booking\AccommodationScope;
use TsReporting\Generator\Scopes\Booking\ItemScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Accommodation extends BookingAccommodation
{
	public function getTitle(): string
	{
		return $this->t('Unterkunft (basierend auf Rechnung)');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder
			->requireScope(ItemScope::class)
			->addJoinAddition(function (JoinClause $join) {
				$join->whereIn('kidvi.type', ['accommodation', 'extra_nights', 'extra_weeks']);
			});

		$builder
			->requireScope(AccommodationScope::class)
			->byItems();


		$this->addSelect($builder, $values);
	}
}