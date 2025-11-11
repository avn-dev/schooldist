<?php

namespace TsStatistic\Model\Filter;

use TcStatistic\Model\Filter\AbstractFilter;
use TsReporting\Generator\ValueHandler;

class CourseCategory extends AbstractFilter
{
	public function getKey()
	{
		return 'course_category';
	}

	public function getTitle()
	{
		return self::t('Kurskategorie');
	}

	public function getInputType()
	{
		return 'multiselect';
	}

	public function getSelectOptions()
	{
		$valueHandler = new ValueHandler(\System::getInterfaceLanguage());
		$filter = new \TsReporting\Generator\Filter\Booking\CourseCategory();
		$values = $filter->getOptions($valueHandler);

		return array_reduce($values, function (array $carry, array $option) {
			$carry[$option['key']] = $option['label'];
			return $carry;
		}, []);

	}

	public function getDefaultValue()
	{
		return array_keys($this->getSelectOptions());

	}
}
