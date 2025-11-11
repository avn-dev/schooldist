<?php

namespace TsReporting\Generator;

use Carbon\CarbonPeriod;
use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\Filter\Period;
use TsReporting\Generator\Groupings\AbstractGrouping;

/**
 * DTO für ReportGenerator, welches alle Spalten, Gruppen und Filter bekommen
 */
class ValueHandler
{
	private string $locale;

	private array $filters = [];

	public AbstractGrouping $grouping;

	public function __construct(string $locale)
	{
		$this->locale = $locale;
	}

	/**
	 * Backend-Sprache für name-Felder
	 *
	 * @return string
	 */
	public function getLocale(): string
	{
		return $this->locale;
	}

	public function setFilter(AbstractFilter $filter)
	{
		$this->filters[get_class($filter)] = $filter;
	}

	/**
	 * @template T
	 * @param class-string<T> $class
	 * @return ?T
	 */
	public function getFilter(string $class): ?AbstractFilter
	{
		return $this->filters[$class] ?? null;
	}

	public function getPeriod(): CarbonPeriod
	{
		$filter = $this->getFilter(Period::class);
		if (!$filter) {
			throw new \RuntimeException('Period is not set but requested.');
		}

		return $filter->getValue();
	}

	public function __clone(): void
	{
		$this->filters = array_map(fn(AbstractFilter $filter) => clone $filter, $this->filters);
	}
}