<?php

namespace TsReporting\Generator\Groupings\Document;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Groupings\Booking\Course as BookingCourse;
use TsReporting\Generator\Scopes\Booking\CourseScope;
use TsReporting\Generator\Scopes\Booking\ItemScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Course extends BookingCourse
{
	public function getTitle(): string
	{
		return $this->t('Kurs (basierend auf Rechnung)');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder
			->requireScope(ItemScope::class)
			->addJoinAddition(function (JoinClause $join) {
				$join->where('kidvi.type', 'course');
			});

		$builder
			->requireScope(CourseScope::class)
			->byItems();


		$this->addSelect($builder, $values);
	}
}