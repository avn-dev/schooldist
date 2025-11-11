<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Scopes\Booking\CourseScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Course extends AbstractGrouping
{
	protected string $type;

	public function getTitle(): string
	{
		return $this->t('Kurs');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->requireScope(CourseScope::class);
		$this->addSelect($builder, $values);
	}

	protected function addSelect(QueryBuilder $builder, ValueHandler $values): void
	{
		$alias = $this->type === 'category' ? 'ktcc' : 'ktc';
		$builder->addSelect(sprintf("%s.id as %s", $alias, $this->buildSelectFieldId()));
		$builder->addSelect(sprintf("%s.name_%s as %s", $alias, $values->getLocale(), $this->buildSelectFieldLabel()));
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
					'course' => $this->t('Kurs'),
					'category' => $this->t('Kategorie')
				]
			]
		];
	}
}
