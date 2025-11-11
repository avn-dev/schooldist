<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Booking extends AbstractGrouping
{
	protected string $field;

	public function getTitle(): string
	{
		if (!isset($this->field)) {
			return $this->t('Buchung');
		}

		return data_get($this->getConfigOptions(), '0.options.'.$this->field);
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$field = match ($this->field) {
			'name' => "CONCAT(tc_c.lastname, ', ', tc_c.firstname)",
			'student_id' => "tc_cn.number",
			'booking_number' => "ts_i.number",
		};

		$builder->addSelect("ts_i.id as ".$this->buildSelectFieldId());
		$builder->selectRaw("$field as ".$this->buildSelectFieldLabel());
		$builder->groupBy($this->buildSelectFieldId());
	}

	public function getConfigOptions(): array
	{
		return [
			[
				'key' => 'field',
				'label' => $this->t('Feld'),
				'type' => 'select',
				'options' => [
					'name' => $this->t('Name'),
					'student_id' => $this->t('Kundennummer'),
					'booking_number' => $this->t('Buchungsnummer')
				]
			]
		];
	}
}