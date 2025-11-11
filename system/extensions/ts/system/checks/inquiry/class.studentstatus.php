<?php

class Ext_TS_System_Checks_Inquiry_Studentstatus extends GlobalChecks {
	
	public function getTitle() {
		return 'Create an index for the student status';
	}
	
	public function getDescription() {
		return 'This will improve speed in the tuition section.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_inquiries_tuition_index` (
				`inquiry_id` INT UNSIGNED NOT NULL ,
				`week` DATE NOT NULL ,
				`state` TINYINT NOT NULL ,
				`from` DATE NOT NULL,
				`until` DATE NOT NULL,
				`current_week` TINYINT NOT NULL ,
				`total_weeks` TINYINT NOT NULL ,
				`total_course_weeks` TINYINT NOT NULL ,
				`total_course_duration` TINYINT NOT NULL ,
				PRIMARY KEY (  `inquiry_id` ,  `week` )
			) ENGINE = INNODB;
		";
		DB::executeQuery($sSql);

		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_courses_tuition_index` (
				`journey_course_id` INT UNSIGNED NOT NULL ,
				`week` DATE NOT NULL ,
				`state` TINYINT NOT NULL ,
				`from` DATE NOT NULL,
				`until` DATE NOT NULL,
				`current_week` TINYINT NOT NULL ,
				`total_weeks` TINYINT NOT NULL ,
				`total_course_weeks` TINYINT NOT NULL ,
				`total_course_duration` TINYINT NOT NULL ,
				PRIMARY KEY (  `journey_course_id` ,  `week` )
			) ENGINE = INNODB;
		";
		DB::executeQuery($sSql);

		// Buchungen durchgehen
		$sSql = "
			SELECT 
				`ti`.`id`
			FROM
				`ts_inquiries` `ti` JOIN
				`ts_inquiries_journeys` `tij` ON
					`ti`.`id` = `tij`.`inquiry_id` JOIN
				`ts_inquiries_journeys_courses` `tijc` ON
					`tij`.`id` = `tijc`.`journey_id` LEFT JOIN
				`ts_inquiries_tuition_index` `titi` ON
					`ti`.`id` = `titi`.`inquiry_id` LEFT JOIN
				`ts_inquiries_journeys_courses_tuition_index` `tijcti` ON
					`tijc`.`id` = `tijcti`.`journey_course_id`
			WHERE
				`ti`.`active` = 1 /*AND (
					`titi`.`inquiry_id` IS NULL OR
					`tijcti`.`journey_course_id` IS NULL
				)*/
			GROUP BY
				`ti`.`id`
			ORDER BY 
				`ti`.`id` DESC
			";
		$aInquiryIds = DB::getQueryCol($sSql);

		if(!empty($aInquiryIds)) {
			foreach($aInquiryIds as $iInquiryId) {

				// Über ParallelProcessing ausführen (muss vor Buchungsindex durchgelaufen sein)
				// @TODO Prio einbauen und nach Alter der Buchung einfügen (das sind pro System mittlerweile viele Buchungen)
				$this->addProcess(['inquiry_id' => $iInquiryId], 10);

			}
		}

		return true;

	}

	public function executeProcess(array $aData) {

		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		$iInquiryId = $aData['inquiry_id'];

		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

		// Nur Buchungen mit Leistung berücksichtigen
		if (!$oInquiry->service_from) {
			return true;
		}

		// Tuition-Index aktualisieren
		$oCourseTuitionIndex = new Ext_TS_Inquiry_TuitionIndex($oInquiry);
		$oCourseTuitionIndex->update();

		// Buchung sofort aktualisieren
		Ext_Gui2_Index_Stack::add('ts_inquiry', $iInquiryId, 0);
		Ext_Gui2_Index_Stack::executeCache();

		return true;

	}

}