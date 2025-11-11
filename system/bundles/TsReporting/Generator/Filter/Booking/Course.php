<?php

namespace TsReporting\Generator\Filter\Booking;

use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\CourseFilterTrait;

class Course extends AbstractFilter
{
	use CourseFilterTrait;

	public function getTitle(): string
	{
		return $this->t('Kurs');
	}

	public function getType(): string
	{
		return 'select:multiple';
	}

	public function build(QueryBuilder $builder)
	{
		$this->apply($builder, fn (QueryBuilder $builder) => $builder->whereIn('ktc.id', $this->value));
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		if (($filterSchool = $valueHandler->getFilter(School::class)) === null) {
			return [];
		}

		$options = [];
		foreach ($filterSchool->getValue() as $schoolId) {
			$school = \Ext_Thebing_School::getInstance($schoolId);
			foreach ($school->getCourses() as $course) {
				$label = '';
				if (count($filterSchool->getValue()) > 1) {
					$label = $school->short.' – ';
				}
				$label .= $course->getName($valueHandler->getLocale());
//				$label .= $course->name_short;
				$options[] = ['key' => (int)$course->id, 'label' => $label];
			}
		}

		return $options;
	}

	public function getDependencies(): array
	{
		return [School::class];
	}
}