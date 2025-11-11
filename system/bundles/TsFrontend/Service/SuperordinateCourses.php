<?php

namespace TsFrontend\Service;

use Illuminate\Support\Str;

class SuperordinateCourses {
	
	private $language;
	
	public function __construct($language) {
		$this->language = $language;
	}

	public function getCourses(int $categoryId=null, array $schoolIds=null, array $flexFilter=[]) {

		$queryBuild = \TsTuition\Entity\SuperordinateCourse::query()->select('ts_sc.*');
		
		if(!empty($categoryId)) {
			$queryBuild->where('ts_sc.coursecategory_id', $categoryId);
		}
		
		if(!empty($schoolIds)) {
			$queryBuild
				->join('kolumbus_tuition_courses', 'kolumbus_tuition_courses.superordinate_course_id', '=', 'ts_sc.id')
				->whereIn('kolumbus_tuition_courses.school_id', $schoolIds);
		}
		
		$items = $queryBuild->get();

		$courses = [];
		foreach($items as $item) {

			$add = true;
			foreach($flexFilter as $flexId=>$flexValues) {
				
				$values = $item->getFlexValue($flexId);

				if(
					(
						is_array($values) &&
						empty(array_intersect($flexValues, $values))
					) ||
					(
						is_scalar($values) &&
						!in_array($values, $flexValues)
					) ||
					empty($values)
				) {				
					$add = false;
					break;
				}
			}

			if($add) {
				$proxy = new \TsTuition\Proxy\SuperordinateCourse($item);
				$proxy->setLanguage($this->language);
				$courses[Str::slug($item->getName($this->language))] = $proxy;
			}
			
		}

		return $courses;
	}
	
}
