<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Scopes\Booking\AccommodationScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Accommodation extends AbstractGrouping
{
	protected string $type;

	public function getTitle(): string
	{
		return $this->t('Unterkunft');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->requireScope(AccommodationScope::class);
		$this->addSelect($builder, $values);
	}

	protected function addSelect(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->addSelect(sprintf("kac.id as %s", $this->buildSelectFieldId()));
		$builder->addSelect(sprintf("kac.name_%s as %s", $values->getLocale(), $this->buildSelectFieldLabel()));
		$builder->groupBy($this->buildSelectFieldId());
	}

	public function getConfigOptions(): array
	{
		return [
			[
				'key' => 'type',
				'label' => $this->t('Typ'),
				'type' => 'select',
				'options' => [
					'category' => $this->t('Kategorie')
				]
			]
		];
	}
}
