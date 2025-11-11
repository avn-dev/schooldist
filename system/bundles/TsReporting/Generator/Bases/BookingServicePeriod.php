<?php

namespace TsReporting\Generator\Bases;

use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class BookingServicePeriod extends Booking
{
//	const ITEM_PERIOD_FILTERED = 'ITEM_PERIOD_FILTERED';

	public function getTitle(): string
	{
		return $this->t('Buchung: Leistungszeitraum');
	}

	protected function addWhere(QueryBuilder $builder, ValueHandler $values)
	{
		$builder->where('ts_i.service_from', '<=', $values->getPeriod()->getEndDate());
		$builder->where('ts_i.service_until', '>=', $values->getPeriod()->getStartDate());
	}
}