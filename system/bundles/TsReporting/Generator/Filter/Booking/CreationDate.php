<?php

namespace TsReporting\Generator\Filter\Booking;

use Carbon\CarbonInterface;
use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Traits\TimeframeFilterTrait;
use TsReporting\Services\QueryBuilder;

class CreationDate extends AbstractFilter
{
	use TimeframeFilterTrait;

	public function getTitle(): string
	{
		return $this->t('Erstellungsdatum');
	}

	public function getType(): string
	{
		return 'timeframe';
	}

	public function build(QueryBuilder $builder)
	{
		self::apply($builder, $this->value->getStartDate(), $this->value->getEndDate());
	}

	public static function apply(QueryBuilder $builder, CarbonInterface $from, CarbonInterface $until)
	{
		$builder->where(function (QueryBuilder $query) use ($from, $until) {
			// Buchung ohne Anfrage
			$query->where(function (QueryBuilder $query) use ($from, $until) {
				$query->where('ts_i.type', '=', \Ext_TS_Inquiry::TYPE_BOOKING);
				$query->whereBetween('ts_i.created', [$from, $until]);
			});
			// Buchung aus Anfrage konvertiert
			$query->orWhere(function (QueryBuilder $query) use ($from, $until) {
				$query->where('ts_i.type', '&', \Ext_TS_Inquiry::TYPE_ENQUIRY);
				$query->whereBetween('ts_i.converted', [$from, $until]);
			});
		});
	}
}