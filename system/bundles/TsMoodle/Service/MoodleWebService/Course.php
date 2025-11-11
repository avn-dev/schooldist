<?php

namespace TsMoodle\Service\MoodleWebService;

use \MoodleSDK\Api\Model\CourseCategory;

class Course extends \TsMoodle\Service\MoodleWebService 
{
	
	/**
	 * 
	 * @param \Ext_Thebing_Tuition_Course $course
	 * @return \MoodleSDK\Api\Model\CourseCategory
	 */
	public function sync(\Ext_Thebing_Tuition_Course $course) 
	{

		// Gibt es die Kategorie des Kurses schon in Moodle? Falls nicht, anlegen.
		$category = $course->getCategory();
		
		$moodleCategory = CourseCategory::instance()->findOneByIdNumber($this->context, 'CA'.$category->id);
		
		if($moodleCategory === null) {
			$moodleCategory = CourseCategory::instance()->findOneByName($this->context, $category->getName());
		}
		
		if($moodleCategory === null) {
			
			$response = CourseCategory::instance()
				->setName($category->getName())
				->setIdnumber('CA'.$category->id)
				->create($this->context);

			$moodleCategory = CourseCategory::instance()->findOneById($this->context, $response['id']);
			
		} else {
			$moodleCategory->setName($category->getName())
				->update($this->context);
		}
		
		// Gibt es den Kurs schon als Kategorie in Moodle? Falls nicht, anlegen.
		$moodleSubcategory = CourseCategory::instance()->findOneByIdNumber($this->context, $course->id);
		
		if($moodleSubcategory === null) {
			$moodleSubcategory = CourseCategory::instance()->findOneByName($this->context, $course->getName());
		}
		
		if($moodleSubcategory === null) {
			
			$response = CourseCategory::instance()
				->setName($course->getName())
				->setIdnumber($course->id)
				->setParent($moodleCategory->getId())
				->create($this->context);

			$moodleSubcategory = CourseCategory::instance()->findOneById($this->context, $response['id']);
			
		} else {
			$moodleSubcategory->setName($course->getName())
				->update($this->context);
		}

		return $moodleSubcategory;
	}
	
}
