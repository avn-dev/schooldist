<?php

namespace TsTuition\Proxy;

use Illuminate\Support\Str;

class SuperordinateCourse extends \Ts\Proxy\AbstractProxy {
	
	protected $sEntityClass = \TsTuition\Entity\SuperordinateCourse::class;

	public function getSlug() {
		return Str::slug($this->oEntity->getName($this->sLanguage));
	}
	
	public function getName($sLanguage = '') {

		if($sLanguage == '') {
			$sLanguage = $this->sLanguage;
		}

		$sName = $this->oEntity->getName($sLanguage);

		return $sName;
	}
	
	public function getCourses() {
		
		$courses = $this->oEntity->getJoinedObjectChilds('courses');
		
		$courses = array_map(function($course) {
					return \Ts\Proxy\Course::getInstance($course);
				}, $courses);
			
		return $courses;
	}
	
}
