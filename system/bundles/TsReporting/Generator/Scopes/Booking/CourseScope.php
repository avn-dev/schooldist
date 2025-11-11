<?php

namespace TsReporting\Generator\Scopes\Booking;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Bases\BookingServicePeriod;
use TsReporting\Generator\Scopes\AbstractScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class CourseScope extends AbstractScope
{
	private bool $byItems = false;

	public function byItems()
	{
		$this->byItems = true;
	}

	public function apply(QueryBuilder $builder, ValueHandler $values): void
	{
		if ($this->byItems) {
//			$builder->selectRaw("JSON_VALUE(kidvi.additional_info, '$.from') AS course_from");
//			$builder->selectRaw("JSON_VALUE(kidvi.additional_info, '$.until') AS course_until");
//			$builder->selectRaw("JSON_VALUE(kidvi.additional_info, '$.course_weeks') AS course_weeks");
		} else {
			$builder->addSelect('ts_ijc.from AS course_from');
			$builder->addSelect('ts_ijc.until AS course_until');
			$builder->addSelect('ts_ijc.weeks AS course_weeks');
			$builder->join('ts_inquiries_journeys_courses as ts_ijc', function (JoinClause $join) use ($values) {
				$join->on('ts_ijc.journey_id', 'ts_ij.id');
				$join->where('ts_ijc.active', 1);
				$join->where('ts_ijc.visible', 1);

				if ($this->base instanceof BookingServicePeriod) {
					$join->where('ts_ijc.from', '<=', $values->getPeriod()->getEndDate());
					$join->where('ts_ijc.until', '>=', $values->getPeriod()->getStartDate());
				}
			});
			$builder->groupBy('ts_ijc.id');
		}

		$builder
			->join('kolumbus_tuition_courses as ktc', 'ktc.id', $this->byItems ? 'kidvi.type_object_id' : 'ts_ijc.course_id')
			->join('ts_tuition_coursecategories as ktcc', 'ktcc.id', 'ktc.category_id');
	}
}