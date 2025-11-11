<?php

namespace TsReporting\Generator\Scopes\Booking;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Bases\BookingServicePeriod;
use TsReporting\Generator\Scopes\AbstractScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class TuitionScope extends AbstractScope
{
	public function apply(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder
			->join('kolumbus_tuition_blocks_inquiries_courses AS ktbic', function (JoinClause $join) {
				$join->on('ktbic.inquiry_course_id', 'ts_ijc.id');
				$join->where('ktbic.active', 1);
			})
			->join('kolumbus_tuition_blocks AS ktb', function (JoinClause $join) use ($values) {
				$join->on('ktb.id', 'ktbic.block_id');
				$join->where('ktb.active', 1);

				if ($this->base instanceof BookingServicePeriod) {
					$join->whereRaw('getCorrectCourseStartDay(ktb.week, cdb2.course_startday) <= ?', [$values->getPeriod()->getEndDate()]);
					$join->whereRaw('getCorrectCourseStartDay(ktb.week, cdb2.course_startday) + INTERVAL 6 DAY >= ?', [$values->getPeriod()->getStartDate()]);
				}
			})
			->join('kolumbus_tuition_templates AS ktt', 'ktt.id', 'ktb.template_id')
			->join('kolumbus_tuition_classes AS ktcl', function (JoinClause $join) {
				$join->on('ktcl.id', 'ktb.class_id');
				$join->where('ktcl.active', 1);
			});
	}
}