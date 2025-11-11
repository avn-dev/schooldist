<?php

namespace TsReporting\Generator\Groupings;

use Carbon\Carbon;
use TsReporting\Generator\Filter\Period as PeriodFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Period extends AbstractGrouping implements QueryableInterface
{
	protected string $unit;

	public function getTitle(): string
	{
		return $this->t('Zeitraum');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->selectRaw("? as ".$this->buildSelectFieldId(), [$values->getPeriod()->getStartDate()->toDateString()]);
		$builder->groupBy($this->buildSelectFieldId());
//		$builder->orderBy($this->buildSelectFieldId());
	}

	// TODO Daten müssten auch noch in die Gruppierung einfließen, wenn es keine Daten in der Iteration gibt
	public function group(ValueHandler $values, \Closure $next): void
	{
		$periods = \Ext_TC_Util::generateDatePeriods($values->getPeriod()->getStartDate(), $values->getPeriod()->getEndDate(), $this->unit);

		foreach ($periods as $period) {
			$this->pushItem($period->getStartDate()->toDateString(), null, $period->getStartDate()->timestamp);

			$newValues = clone $values;
			$newValues->getFilter(PeriodFilter::class)->setValue($period);

			$next($newValues);
		}
	}

	public function getFormat(ValueHandler $values): array
	{
		return [
			'type' => 'date',
			'unit' => $this->unit,
			'labels' => [
				'quarter' => $this->t('Quartal'),
				'week' => $this->t('Woche'),
			]
		];
	}

	public function getConfigOptions(): array
	{
		return [
			[
				'key' => 'unit',
				'label' => $this->t('Einheit'),
				'type' => 'select',
				'options' => [
					'year' => $this->t('Jahr'),
					'quarter' => $this->t('Quartal'),
					'month' => $this->t('Monat'),
					'week' => $this->t('Woche (ISO)'),
					'day' => $this->t('Tag')
				]
			]
		];
	}

	public function getSortValue(array $row): mixed
	{
		return Carbon::parse($row[$this->buildSelectFieldId()])->timestamp;
	}

//	public function getSortFlag(): int
//	{
//		return SORT_NUMERIC;
//	}

//	private function format(CarbonInterface $date): string
//	{
//		return match ($this->unit) {
//			'year' => $date->isoFormat('YYYY'),
//			'quarter' => $this->t('Quartal').$date->isoFormat(' Q, YYYY'),
//			'month' => $date->isoFormat('MMMM, YYYY'),
//			'week' => $this->t('Woche').$date->isoFormat(' W, YYYY'),
//			'day' => $date->isoFormat('L')
//		};
//	}
}