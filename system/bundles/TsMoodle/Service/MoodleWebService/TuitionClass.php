<?php

namespace TsMoodle\Service\MoodleWebService;

use \MoodleSDK\Api\Model\Course as MoodleCourse;

class TuitionClass extends \TsMoodle\Service\MoodleWebService 
{
	
	/**
	 * Klasse in Fidelo ist Kurs in Moodle
	 * Es wird nur der erste Kurs der Klasse berücksichtigt! 
	 * Moodle kann einen Kurs nicht mehreren Kategorien zuweisen.
	 * 
	 * @todo Bessere Lösung für den ShortName finden! Das mit der ID am Ende ist doof!
	 * @param \Ext_Thebing_Tuition_Class $class
	 */
	public function sync(\Ext_Thebing_Tuition_Class $class) 
	{

		$courses = $class->getJoinTableObjects('courses');

		$course = reset($courses);
		
		// Darf eigentlich nicht auftreten. Ist bei Tudias passiert
		if(empty($course)) {
			return;
		}
		
		// Kurs abgleichen (Kategorie in Moodle)
		$courseSync = new Course($this->school);
		$moodleSubcategory = $courseSync->sync($course);
		
		// Gibt es die Klasse (Kurs in Moodle) schon? 
		$moodleCourse = MoodleCourse::instance()
				->findOneByField($this->context, 'idnumber', $class->id);
		
		if($moodleCourse === null) {
			$response = MoodleCourse::instance()
				->setIdnumber($class->id)
				->setCategoryId($moodleSubcategory->getId())
				->setFullName($class->getName())
				// Die ID hängt da dran, damit das immer eindeutig ist.
				->setShortName($class->getName().' '.$class->id)
				->create($this->context);

			$moodleCourse = MoodleCourse::instance()
				->findOneByField($this->context, 'id', $response['id']);
		}

		// Klasse (Moodle Kurs) aktualisieren
		$response = $moodleCourse
				->setCategoryId($moodleSubcategory->getId())
				->setFullName($class->getName())
				// Die ID hängt da dran, damit das immer eindeutig ist.
				->setShortName($class->getName().' '.$class->id)
				->setStartDate(new \DateTime($class->start_week))
				->setEndDate($class->getLastDate())
				->update($this->context);

		return $moodleCourse;
	}

}
