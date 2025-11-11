<?php

use Ts\Entity\Inquiry\Journey\Course\LessonsContingent;

class Ext_TS_System_Checks_Inquiry_Journey_CourseLessonsCatchUpDate extends GlobalChecks
{
	public function getTitle()
	{
		return 'Bookings';
	}

	public function getDescription()
	{
		return 'Prepare course lessons catch up structure';
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$journeyCourses = (array)\DB::getQueryCol("
			SELECT 
				`id`
			FROM 
			    `ts_inquiries_journeys_courses` 
			WHERE 
			    `state` & '".\Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION."' AND
			    `lessons_catch_up_original_until` IS NULL
		");

		if (empty($journeyCourses)) {
			return true;
		}

		$backup = \Util::backupTable('ts_inquiries_journeys_courses');

		if (!$backup) {
			__pout('Backup error');
			return false;
		}

		foreach ($journeyCourses as $journeyCoursesId) {
			$this->addProcess(['id' => $journeyCoursesId], 100);
		}

		return true;
	}

	public function executeProcess(array $data)
	{
		$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($data['id']);

		if (!$journeyCourse->exist() || !$journeyCourse->isActive()) {
			return true;
		}

		$period = $journeyCourse->createPeriod();

		$originalDate = \Ext_Thebing_Util::getCourseEndDate($period->start(), (int)$journeyCourse->weeks, (int)$journeyCourse->getJourney()->getSchool()->course_startday);

		$update = "
			UPDATE 
				`ts_inquiries_journeys_courses` 
			SET
				`changed` = `changed`,
				`lessons_catch_up_original_until` = :originalDate
			WHERE 
				`id` = :id
		";

		\DB::executePreparedQuery($update, [
			'originalDate' => $originalDate->format('Y-m-d'),
			'id' => $journeyCourse->id,
		]);

		return true;
	}

}