<?php

namespace TsReporting\Generator\Filter\Booking;

use Illuminate\Support\Collection;
use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\CourseFilterTrait;

class CourseCategory extends AbstractFilter
{
	use CourseFilterTrait;

	public function getTitle(): string
	{
		return $this->t('Kurskategorie');
	}

	public function getType(): string
	{
		return 'select:multiple';
	}

	public function build(QueryBuilder $builder)
	{
		$this->apply($builder, fn(QueryBuilder $builder) => $builder->whereIn('ktc.category_id', $this->value));
	}

	public function getOptions(ValueHandler $valueHandler): array
	{
		if (\Ext_Thebing_System::isAllSchools()) {
			$schools = \Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(false, true);
		} else {
			$schools = [\Ext_Thebing_School::getSchoolFromSession()];
		}

		$options = collect();
		foreach ($schools as $school) {
			$categories = $school->getCourseCategoriesList('object');
			$options = array_reduce($categories, function (Collection $options, \Ext_Thebing_Tuition_Course_Category $category) use ($valueHandler) {
				$options->push([
					'key' => (int)$category->id,
					'label' => $category->getName($valueHandler->getLocale()),
					'position' => (int)$category->position
				]);
				return $options;
			}, $options);
		}

		$options = $options->sortBy('position');

		return $options->toArray();
	}
}