<?php

namespace TsReporting\Generator\Groupings\Tuition;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Scopes\Booking\CourseScope;
use TsReporting\Generator\Scopes\Booking\TuitionScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class TuitionTime extends AbstractGrouping
{
	public function getTitle(): string
	{
		return $this->t('Standardzeit');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$label = $this->t('Individuell');
		$builder->requireScope(CourseScope::class);
		$builder->requireScope(TuitionScope::class);
		$builder->selectRaw("IF(ktt.custom, 0, ktt.id) as ".$this->buildSelectFieldId());
		$builder->selectRaw("IF(ktt.custom, '$label', ktt.name) as ".$this->buildSelectFieldLabel());
		$builder->selectRaw("IF(ktt.custom, ".PHP_INT_MAX.", ktt.position) as ".$this->buildSelectFieldId().'_position');
		$builder->groupBy($this->buildSelectFieldId());
	}

	public function getSortValue(array $row): mixed {
		return $row[$this->buildSelectFieldId().'_position'];
	}

//	public function getSortFlag(): int {
//		return SORT_NUMERIC;
//	}
}