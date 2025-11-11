<?php

namespace TsReporting\Generator\Filter;

use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use TsReporting\Generator\ValueHandler;

class FilterFactory
{
	public function create(string $class): AbstractFilter
	{
		return new $class();
	}

	public function fromConfig(): array
	{
		$bundleConfig = (new \Core\Helper\Bundle())->readBundleFile('TsReporting', 'definitions');
		return array_map(fn(string $class) => $this->create($class), $bundleConfig['filter']);
	}

	/**
	 * @param AbstractFilter[] $filters
	 * @param array $values
	 * @param ValueHandler $valueHandler
	 * @return void
	 */
	public function applyRequest(array $filters, array $values, ValueHandler $valueHandler): void
	{
		foreach ($filters as $filter) {
			$requestFilter = Arr::first($values, fn(array $requestFilter) => $requestFilter['key'] === get_class($filter));

			if ($requestFilter) {
				$filter->setValue($requestFilter['value']);
			}

			if ($filter->hasValue() || $filter->isRequired()) {
				$valueHandler->setFilter($filter);
			}
		}
	}

	public function toJson(array $filters, ValueHandler $valueHandler): array
	{
		return array_map(function (AbstractFilter $filter) use ($valueHandler) {
			$value = $filter->getValue();
			if ($value instanceof CarbonPeriod) {
				$value = ['start' => $value->getStartDate(), 'end' => $value->getEndDate()];
			}

			$type = explode(':', $filter->getType());
			return [
				'key' => get_class($filter),
				'name' => $filter->getTitle(),
				'component' => $type[0],
				'type' => $type[1] ?? null,
				'value' => $value,
				'options' => $filter->getOptions($valueHandler),
				'dependencies' => $filter->getDependencies(),
				'required' => $filter->isRequired()
			];
		}, $filters);
	}
}