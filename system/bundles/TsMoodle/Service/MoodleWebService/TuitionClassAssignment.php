<?php

namespace TsMoodle\Service\MoodleWebService;

class TuitionClassAssignment extends \TsMoodle\Service\MoodleWebService 
{

	/**
	 * Es gibt nur eine Zuweisung von User zu Course in Moodle, daher nur frühestes und letztes Datum, egal ob eine Pause da ist.
	 * 
	 * @param \Ext_Thebing_Tuition_Class $class
	 * @param \Ext_TS_Inquiry_Journey_Course $journeyCourse
	 */
	public function sync(\Ext_Thebing_Tuition_Class $class, \Ext_TS_Inquiry_Journey_Course $journeyCourse, \Ext_Thebing_Tuition_Course $course=null) 
	{

		// Klasse abgleichen
		$classSync = new TuitionClass($this->school);
		$moodleCourse = $classSync->sync($class);
		
		// Schüler abgleichen, ohne die Zuweisungen abzugleichen (Achtung: Rekursion)
		$studentSync = new Student($this->school);
		$moodleUser = $studentSync->sync($journeyCourse->getInquiry(), false);

		// Kurs abgleichen
		if($course !== null) {
			$courseSync = new Course($this->school);
			$moodleSubcategory = $courseSync->sync($course);
		}
		
		// Alle Zuweisung dieses Schülers zu dieser Klasse ermitteln
		$assignmentSearch = new \Ext_Thebing_School_Tuition_Allocation_Result();
		$assignmentSearch->setInquiry($journeyCourse->getInquiry());
		$assignmentSearch->setClass($class);
		$assignmentSearch->setGroupByWeek();
		$assignments = $assignmentSearch->fetch();

		$log = \Log::getLogger('api', 'moodle');

		if(empty($assignments)) {
			
			$response = $moodleCourse->unenrolUser($this->context, $moodleUser, 5);
			
			$log->info('Unenrol', [$response, $moodleUser->toArray(), $moodleCourse->toArray()]);

		} else {

			$firstWeek = $lastWeek = null;
			foreach($assignments as $assignment) {

				$assignmentWeek = new \DateTime($assignment['block_week']);

				// Initialwerte setzen beim ersten Durchlauf
				if($firstWeek === null) {
					$firstWeek = clone $assignmentWeek;
					$lastWeek = clone $assignmentWeek;
				}

				$firstWeek = min($firstWeek, $assignmentWeek);
				$lastWeek = max($lastWeek, $assignmentWeek);
			}

			$firstWeek->modify('last Saturday');
			$firstWeek->modify('09:00:00');

			$lastWeek->modify('next Saturday');
			$lastWeek->modify('17:00:00');	

			// Enrol User as student
			$response = $moodleCourse->enrolUser($this->context, $moodleUser, 5, $firstWeek, $lastWeek);
			
			$log->info('Enrol', [$response, $moodleUser->toArray(), $moodleCourse->toArray(), $firstWeek, $lastWeek]);

		}
			
		return $response;
	}

}
