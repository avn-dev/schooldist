<?php

/**
 * Prüft ob es auf der Installation Lektionskurse gibt die mit Kombinationskursen verknüpft und direkt als Kursbuchung
 * gebucht wurden
 */
class Ext_TS_System_Checks_Course_CheckUsedLessonsCourses extends GlobalChecks
{
	public function getTitle()
	{
		return 'Courses';
	}

	public function getDescription()
	{
		return 'Checks usage of lessons courses';
	}

	public function executeCheck()
	{
		$sql = "
			SELECT
				`ktc`.`id`,
				`ktc`.`name_en`,
				MAX(`ts_ijc`.`id`) as `last_combi_journey_course_id`,
				MAX(`ts_ijc`.`created`) as `last_combi_journey_course_created`,
				MAX(`ts_ijc_direct`.`id`) as `last_direct_journey_course_id`,
				MAX(`ts_ijc_direct`.`created`) as `last_direct_journey_course_created`,
				GROUP_CONCAT(DISTINCT `ktc_prog`.`name_en`) as program_courses
			FROM
				`kolumbus_tuition_courses` `ktc` INNER JOIN
				/* Als Kombinationskurs benutzt */
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`type` = 'course' AND
					`ts_tcps`.`type_id` = `ktc`.`id` AND
					`ts_tcps`.`active` = 1 INNER JOIN
				`ts_tuition_courses_programs` `ts_tcp` ON
					`ts_tcp`.`id` = `ts_tcps`.`program_id` AND
					`ts_tcp`.`course_id` !=  `ktc`.`id` AND
					`ts_tcp`.`active` = 1 INNER JOIN
				`kolumbus_tuition_courses` `ktc_prog` ON
					`ktc_prog`.`id` = `ts_tcp`.`course_id` AND
					`ktc_prog`.`active` = 1 AND
					(
						`ktc_prog`.`valid_until` >= CURDATE() OR
						`ktc_prog`.`valid_until` = '0000-00-00'
					) INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
				    `ts_ijc`.`course_id` = `ts_tcp`.`course_id` AND
				    `ts_ijc`.`active` = 1 INNER JOIN
				/* Lektionskurs direkt gebucht */
				`ts_inquiries_journeys_courses` `ts_ijc_direct` ON
				    `ts_ijc_direct`.`course_id` = `ktc`.`id` AND
				    `ts_ijc_direct`.`active` = 1
			WHERE
				`ktc`.`per_unit` = 1 AND
				`ktc`.`active` = 1 AND
				`ktc`.`only_for_combination_courses` = 0 AND
				(
					`ktc`.`valid_until` >= CURDATE() OR
					`ktc`.`valid_until` = '0000-00-00'
				)
			GROUP BY
			    `ktc`.`id`
		";

		$mailContent = '';

		try {

			$courses = (array)\DB::getQueryRows($sql);

			if (!empty($courses)) {
				$mailContent =
					sprintf('Folgende Lektionskurse wurden auf der Installation "%s" mit Kombinationskursen verknüpft und in einer Kursbuchung direkt gebucht', \System::d('domain')) .
					"\n\n" .
					print_r($courses, 1);
			}

		} catch (\Throwable $e) {
			$mailContent = 'FEHLER'. "\n\n".$e->getMessage();
		}

		if (!empty($mailContent)) {
			try {
				$mail = new \WDMail();
				$mail->subject = 'V2: Lektionskurse für Kombinationskurse und direkt gebucht';
				$mail->text = $mailContent;
				$mail->send(['ts@fidelo.com']);
			} catch (\Throwable $e) {
				return false;
			}
		}

		return true;
	}
}