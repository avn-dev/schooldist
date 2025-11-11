<?php

namespace TsReporting\Generator\Scopes\Booking;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Bases\BookingServicePeriod;
use TsReporting\Generator\Scopes\AbstractScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class AccommodationScope extends AbstractScope
{
	private bool $byItems = false;

	public function byItems()
	{
		$this->byItems = true;
	}

	public function apply(QueryBuilder $builder, ValueHandler $values): void
	{
		if ($this->byItems) {
//			$builder->selectRaw("JSON_VALUE(kidvi.additional_info, '$.from') AS accommodation_from");
//			$builder->selectRaw("JSON_VALUE(kidvi.additional_info, '$.until') AS accommodation_until");
//			$builder->selectRaw("JSON_VALUE(kidvi.additional_info, '$.accommodation_weeks') AS accommodation_weeks");
		} else {
			$builder->join('ts_inquiries_journeys_accommodations as ts_ija', function (JoinClause $join) use ($values) {
				$join->on('ts_ija.journey_id', 'ts_ij.id');
				$join->where('ts_ija.active', 1);
				$join->where('ts_ija.visible', 1);

				if ($this->base instanceof BookingServicePeriod) {
					$join->where('ts_ija.from', '<=', $values->getPeriod()->getEndDate());
					$join->where('ts_ija.until', '>=', $values->getPeriod()->getStartDate());
				}
			});
		}

		$builder->join('kolumbus_accommodations_categories as kac', 'kac.id', $this->byItems ? 'kidvi.type_object_id' : 'ts_ija.accommodation_id');
	}
}