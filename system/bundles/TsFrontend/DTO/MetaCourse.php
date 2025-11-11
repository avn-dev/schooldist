<?php

namespace TsFrontend\DTO;

class MetaCourse {
	
	public function __construct(string $slug, string $name, array $courseIds, int $categoryId, string $categoryName) {
		$this->slug = $slug;
		$this->name = $name;
		$this->courseIds = $courseIds;
		$this->categoryId = $categoryId;
		$this->categoryName = $categoryName;
	}
	
	public function getSlug() {
		return $this->slug;
	}
	
	public function getName() {
		return $this->name;
	}

	public function getFirstCourse() {
		
		$courseId = reset($this->courseIds);
		
		$course = \Ext_Thebing_Tuition_Course::getInstance($courseId);

		$courseProxy = \Ts\Proxy\Course::getInstance($course);

		return $courseProxy;
	}
	
	public function getCourses() {

		$courses = array_map(function ($courseId) { 
			$course = \Ext_Thebing_Tuition_Course::getInstance($courseId);
			$courseProxy = \Ts\Proxy\Course::getInstance($course);
			return $courseProxy;
		}, $this->courseIds);
		
		return $courses;
	}
	
}
