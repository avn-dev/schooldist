<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Entity\AgeGroup as AgeGroupEntity;
use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class AgeGroup extends AbstractGrouping
{
	protected array $ageGroups;

	public function getTitle(): string
	{
		return $this->t('Altersgruppe');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$sql = ["CASE"];
		$bindings = [];
		foreach ($this->getAgeGroups() as $ageGroup) {
			$this->pushItem($ageGroup->id, $ageGroup->name, null);
			$bindings[] = (int)$ageGroup->age_from;
			$bindings[] = (int)$ageGroup->age_until;
			$bindings[] = (int)$ageGroup->id;
			$sql[] = "WHEN TIMESTAMPDIFF(YEAR, tc_c.birthday, ts_i.service_from) >= ? AND TIMESTAMPDIFF(YEAR, tc_c.birthday, ts_i.service_from) <= ? THEN ?";
		}
//		$sql[] = "ELSE 0";
		$sql[] = "END ".$this->buildSelectFieldId();

//		$this->pushItem(0, $this->t('keine'), 0);
		$builder->selectRaw(join(' ', $sql), $bindings);
		$builder->groupBy($this->buildSelectFieldId());
		$builder->havingNotNull($this->buildSelectFieldId());
	}

	public function getConfigOptions(): array
	{
		$ageGroups = AgeGroupEntity::query()
			->get()
			->mapWithKeys(fn(AgeGroupEntity $ageGroup) => [$ageGroup->id => $ageGroup->name])
			->toArray();

		return [
			[
				'key' => 'age_groups',
				'label' => $this->t('Altersgruppen'),
				'type' => 'multiselect',
				'options' => $ageGroups
			]
		];
	}

	/**
	 * @return AgeGroupEntity[]
	 */
	public function getAgeGroups(): array
	{
		return array_map(fn($id) => AgeGroupEntity::getInstance($id), $this->ageGroups);
	}
}
