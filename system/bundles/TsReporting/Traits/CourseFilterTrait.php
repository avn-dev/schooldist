<?php

namespace TsReporting\Traits;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Scopes\Booking\CourseScope;
use TsReporting\Services\QueryBuilder;

trait CourseFilterTrait
{
	public function apply(QueryBuilder $builder, \Closure $condition): void
	{
		// ktc wird in beiden CourseScope-Fällen bereitgestellt
		if ($builder->hasScope(CourseScope::class)) {
			$condition($builder);
			return;
		}

		// Filter als Subquery, um z.B. Anzahl Schüler nicht zu verfälschen
		$builder->whereIn('ts_i.id', function (QueryBuilder $builder) use ($condition) {
			$builder->select('ts_i.id')
				->from('ts_inquiries as ts_i')
				->join('ts_inquiries_journeys as ts_ij', function (JoinClause $join) {
					$join->on('ts_ij.inquiry_id', 'ts_i.id')
						->where('ts_ij.active', 1)
						->where('ts_ij.type', '&', \Ext_TS_Inquiry_Journey::TYPE_BOOKING);
				})
				->join('ts_inquiries_journeys_courses as ts_ijc', function (JoinClause $join) {
					$join
						->on('ts_ijc.journey_id', 'ts_ij.id')
						->where('ts_ijc.active', 1)
						->where('ts_ijc.visible', 1);
				})
				->join('kolumbus_tuition_courses as ktc', 'ktc.id',  'ts_ijc.course_id');
			$condition($builder);
		});
	}
}