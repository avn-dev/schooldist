<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Entity\AgeGroup as AgeGroupEntity;
use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class AgeGroup extends AbstractFilter
{
	public function getTitle(): string
	{
		return $this->t('Altersgruppe');
	}

	public function getType(): string
	{
		return 'select';
	}

	public function build(QueryBuilder $builder)
	{
		$ageGroup = AgeGroupEntity::getInstance($this->value);
		$builder->whereRaw("TIMESTAMPDIFF(YEAR, tc_c.birthday, ts_i.service_from) >= ?", [$ageGroup->age_from]);
		$builder->whereRaw("TIMESTAMPDIFF(YEAR, tc_c.birthday, ts_i.service_from) <= ?", [$ageGroup->age_until]);
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		return AgeGroupEntity::query()
			->get()
			->map(fn(AgeGroupEntity $ageGroup) => ['key' => (string)$ageGroup->id, 'label' => $ageGroup->name])
			->toArray();
	}
}