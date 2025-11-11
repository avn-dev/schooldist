<?php

use Ts\Entity\Inquiry\Journey\Course\LessonsContingent;

class Ext_TS_System_Checks_Inquiry_Journey_CourseLessonContingents extends GlobalChecks
{

	public function getTitle()
	{
		return 'Bookings';
	}

	public function getDescription()
	{
		return 'Build up students lessons contingents';
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$first = \DB::getQueryOne("SELECT `id` FROM `ts_inquiries_journeys_courses_lessons_contingent` LIMIT 1");

		if (
			$first !== null &&
			(
				!\Util::backupTable('ts_inquiries_journeys_courses_lessons_contingent') ||
				!\Util::backupTable('ts_inquiries_journeys_courses')
			)
		) {
			__pout('Backup error');
			return false;
		}

		// Einträge löschen die mit keinem JourneyCourse verknüpft sind (Import?)
		\DB::executeQuery("
			DELETE `ts_inquiries_journeys_courses_lessons_contingent` FROM 
				`ts_inquiries_journeys_courses_lessons_contingent` LEFT JOIN 
				`ts_inquiries_journeys_courses` ON
					`ts_inquiries_journeys_courses`.`id` = `ts_inquiries_journeys_courses_lessons_contingent`.`journey_course_id`
			WHERE 
				`ts_inquiries_journeys_courses`.`id` IS NULL
		");

		$inquiryJourneyCourses = \DB::getQueryCol("SELECT `id` FROM `ts_inquiries_journeys_courses` WHERE `active` = 1 ORDER BY `id` DESC");

		foreach ($inquiryJourneyCourses as $id) {
			$this->addProcess(['id' => $id]);
		}

		return true;
	}

	public function executeProcess(array $data)
	{
		$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($data['id']);

		if (!$journeyCourse->exist() || !$journeyCourse->isActive()) {
			return true;
		}

		$programServices = $journeyCourse->getProgram()->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

		foreach ($programServices as $service) {
			$contingent = $journeyCourse->getLessonsContingent($service);
			$contingent->refresh(LessonsContingent::ABSOLUTE | LessonsContingent::USED | LessonsContingent::CANCELLED);

			$this->updateContingent($contingent);
		}

		return true;

	}

	private function updateContingent(LessonsContingent $contingent)
	{
		$entity = \Illuminate\Support\Arr::except($contingent->getData(), ['id', 'changed', 'editor_id']);

		if (!$contingent->exist()) {
			$sql = "
				INSERT INTO 
					`ts_inquiries_journeys_courses_lessons_contingent` 
				SET
					`journey_course_id` = :journey_course_id,
					`program_service_id` = :program_service_id,
					`absolute` = :absolute,
					`used` = :used,
					`cancelled` = :cancelled,
					`lessons` = :lessons,
					`lessons_unit` = :lessons_unit,
					`weeks` = :weeks
			";
		} else {

			$delete = "
				DELETE FROM
					`ts_inquiries_journeys_courses_lessons_contingent`
				WHERE
				    `journey_course_id` = :journey_course_id AND
					`program_service_id` = :program_service_id AND
					`id` != :id
			";

			\DB::executePreparedQuery($delete, [
				'journey_course_id' => $entity['journey_course_id'],
				'program_service_id' => $entity['program_service_id'],
				'id' => $contingent->id,
			]);

			$sql = "
				UPDATE 
					`ts_inquiries_journeys_courses_lessons_contingent` 
				SET
					`changed` = `changed`,
					`absolute` = :absolute,
					`used` = :used,
					`cancelled` = :cancelled,
					`lessons` = :lessons,
					`lessons_unit` = :lessons_unit,
					`weeks` = :weeks
				WHERE 
					`journey_course_id` = :journey_course_id AND
					`program_service_id` = :program_service_id
			";

		}

		\DB::executePreparedQuery($sql, $entity);
	}

}