<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class StudentStatus extends AbstractFilter
{
	public function getTitle(): string
	{
		return $this->t('Schülerstatus');
	}

	public function getType(): string
	{
		return 'select:multiple';
	}

	public function build(QueryBuilder $builder)
	{
		$builder->whereIn('ts_i.status_id', $this->value);
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		if (($filterSchool = $valueHandler->getFilter(School::class)) === null) {
			return [];
		}

		$options = [];
		foreach ($filterSchool->getValue() as $schoolId) {
			foreach(\Ext_Thebing_Marketing_StudentStatus::getList(false, $schoolId) as $status) {
				$options[$status->id] = ['key' => (int)$status->id, 'label' => $status->text];
			}
		}

		usort($options, fn($a, $b) => strnatcmp($a['label'], $b['label']));

		return array_values($options);
	}

	public function getDependencies(): array
	{
		return [School::class];
	}
}