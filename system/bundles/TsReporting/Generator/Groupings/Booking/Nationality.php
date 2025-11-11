<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Nationality extends AbstractGrouping
{
	protected string $label;

	public function getTitle(): string
	{
		return $this->t('Nationalität');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$label = $this->label === 'iso' ? 'tc_c.nationality' : 'dc_nationality.nationality_'.$values->getLocale();
		$builder->addSelect("tc_c.nationality as ".$this->buildSelectFieldId());
		$builder->selectRaw("COALESCE($label, tc_c.nationality) as ".$this->buildSelectFieldLabel());
		$builder->leftJoin('data_countries as dc_nationality', 'dc_nationality.cn_iso_2', 'tc_c.nationality');
		$builder->groupBy($this->buildSelectFieldId());
//		$builder->orderBy($label);
	}

	public function getConfigOptions(): array
	{
		return [
			[
				'key' => 'label',
				'label' => $this->t('Anzeige'),
				'type' => 'select',
				'options' => [
					'name' => $this->t('Vollständiger Name'),
					'iso' => $this->t('ISO-Code')
				]
			]
		];
	}
}
