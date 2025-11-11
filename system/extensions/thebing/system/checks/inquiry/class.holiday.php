<?php

class Ext_Thebing_System_Checks_Inquiry_Holiday extends GlobalChecks {

	public function getTitle() {
		return 'Migrate student holidays';
	}

	public function getDescription() {
		return 'Create opportunity to delete school holidays of splitted courses.';
	}

	public function executeCheck() {

		ini_set('memory_limit', '1024M');

		if(!in_array('kolumbus_inquiries_holidays', DB::listTables())) {
			return true;
		}

		Util::backupTable('kolumbus_inquiries_courses_structure');
		Util::backupTable('kolumbus_inquiries_holidays');
		Util::backupTable('kolumbus_inquiries_holidays_splitting');
		Util::backupTable('kolumbus_inquiries_holidays_accommodationsave');
		Util::backupTable('kolumbus_inquiries_holidays_coursesave');

//		DB::executeQuery("TRUNCATE TABLE ts_inquiries_holidays");
//		DB::executeQuery("TRUNCATE TABLE ts_inquiries_holidays_splitting");

		DB::begin(__CLASS__);

		$sSql = "
			SELECT
				`id`
			FROM
				`ts_inquiries`
			ORDER BY
				`id`
		";

		$aInquiryIds = (array)DB::getQueryCol($sSql);

		foreach($aInquiryIds as $iInquiryId) {
			$this->migrateInquirySchoolHolidays($iInquiryId);
			$this->migrateInquiryStudentHolidays($iInquiryId);
		}

		DB::commit(__CLASS__);

		DB::executeQuery("DROP TABLE kolumbus_inquiries_courses_structure");
		DB::executeQuery("DROP TABLE kolumbus_inquiries_holidays");
		DB::executeQuery("DROP TABLE kolumbus_inquiries_holidays_splitting");
		DB::executeQuery("DROP TABLE kolumbus_inquiries_holidays_accommodationsave");
		DB::executeQuery("DROP TABLE kolumbus_inquiries_holidays_coursesave");

		return true;

	}

	private function migrateInquirySchoolHolidays($iInquiryId) {

		$sSql = "
			SELECT
			    `ts_ij`.`school_id`,
				`kics`.`master_id`,
				`kics`.`child_id`,
				`ts_ijc`.`weeks` `master_weeks`,
				`ts_ijc`.`from` `master_from`,
				`ts_ijc`.`until` `master_until`,
				`ts_ijc`.`created`,
				`ts_ijc`.`creator_id`,
				`ts_ijc2`.`weeks` `child_weeks`,
				`ts_ijc2`.`from` `child_from`
			FROM
			    `ts_inquiries_journeys` `ts_ij` INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
					`ts_ijc`.`active` = 1 INNER JOIN
				`kolumbus_inquiries_courses_structure` `kics` ON
					`kics`.`master_id` = `ts_ijc`.`id` INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc2` ON
					`ts_ijc2`.`id` = `kics`.`child_id` AND
					`ts_ijc2`.`active` = 1
			WHERE
				`ts_ij`.`inquiry_id` = :inquiry_id AND
			    `ts_ij`.`active` = 1 AND
				`kics`.`master_id` != `kics`.`child_id`
		";

		$aResult = (array)DB::getQueryRows($sSql, ['inquiry_id' => $iInquiryId]);

		foreach($aResult as $aCourseStructure) {

			$aSchoolHoliday = $this->searchSchoolHoliday($aCourseStructure['school_id'], $aCourseStructure['master_until'], $aCourseStructure['child_from']);

			if(empty($aSchoolHoliday)) {
				$aSchoolHoliday = [
					'from' => $aCourseStructure['master_until'],
					'until' => $aCourseStructure['child_from']
				];
			}

			$iWeeks = ceil((new DateTime($aSchoolHoliday['from']))->diff(new DateTime($aSchoolHoliday['until']))->days / 7);

			// Sollte gesetzt werden, da Diff die Werte braucht zum korrekten Berechnen der Zusatzleistungen
			// Da gibt es zwar auch einen Fallback, allerdings sollten die Werte immer vorhanden sein
			$iOriginalWeeks = $aCourseStructure['master_weeks'] + $aCourseStructure['child_weeks'];
			$dOriginalUntil = new DateTime($aCourseStructure['master_from']);
			$dOriginalUntil->add(new DateInterval('P'.$iOriginalWeeks.'W'));
			$dOriginalUntil->sub(new DateInterval('P2D'));

			$iHolidayId = DB::insertData('ts_inquiries_holidays', [
				'created' => $aCourseStructure['created'],
				'changed' => $aCourseStructure['changed'],
				'creator_id' => $aCourseStructure['creator_id'],
				'editor_id' => $aCourseStructure['creator_id'],
				'inquiry_id' => $iInquiryId,
				'type' => 'school',
				'weeks' => $iWeeks,
				'from' => $aSchoolHoliday['from'],
				'until' => $aSchoolHoliday['until']
			]);

			DB::insertData('ts_inquiries_holidays_splitting', [
				'created' => $aCourseStructure['created'],
				'changed' => $aCourseStructure['changed'],
				'creator_id' => $aCourseStructure['creator_id'],
				'editor_id' => $aCourseStructure['creator_id'],
				'holiday_id' => $iHolidayId,
				'journey_course_id' => $aCourseStructure['master_id'],
				'journey_split_course_id' => $aCourseStructure['child_id'],
				'original_weeks' => $iOriginalWeeks,
				'original_from' => $aCourseStructure['master_from'],
				'original_until' => $dOriginalUntil->format('Y-m-d'),
			]);

			$this->logInfo(vsprintf('Migrated school holiday %d / %d for inquiry %d (holiday %d)', [
				$aCourseStructure['master_id'],
				$aCourseStructure['child_id'],
				$iInquiryId,
				$iHolidayId
			]));

		}

	}

	private function migrateInquiryStudentHolidays($iInquiryId) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_inquiries_holidays`
			WHERE
				`inquiry_id` = :inquiry_id AND
				`active` = 1
		";

		$aResult = (array)DB::getQueryRows($sSql, ['inquiry_id' => $iInquiryId]);
		foreach($aResult as $aStudentHoliday) {

			$iHolidayId = DB::insertData('ts_inquiries_holidays', [
				'created' => $aStudentHoliday['created'],
				'changed' => $aStudentHoliday['changed'],
				'creator_id' => $aStudentHoliday['creator_id'],
				'editor_id' => $aStudentHoliday['creator_id'],
				'inquiry_id' => $iInquiryId,
				'type' => 'student',
				'weeks' => $aStudentHoliday['weeks'],
				'from' => $aStudentHoliday['from'],
				'until' => $aStudentHoliday['until']
			]);

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_inquiries_holidays_splitting`
				WHERE
					`holiday_id` = :id
			";

			$aResult = (array)DB::getQueryRows($sSql, $aStudentHoliday);
			foreach($aResult as $aSplitting) {

				// Es sind immer nur Kurs- oder Unterkunftsfelder gefüllt, niemals beide
				if(!empty($aSplitting['inquiry_accommodation_id'])) {
					$sType = 'accommodation';
					$iTypeId = $aSplitting['inquiry_accommodation_id'];
				} else {
					$sType = 'course';
					$iTypeId = $aSplitting['inquiry_course_id'];
				}

				$aOriginalService = $this->searchOriginalService($aStudentHoliday['id'], $sType, $iTypeId);

				if(empty($aOriginalService)) {
					$this->logError('No original service found for inquiry holiday '.$iHolidayId.', inquiry '.$iInquiryId);
				}

				DB::insertData('ts_inquiries_holidays_splitting', [
					'created' => $aStudentHoliday['created'],
					'changed' => $aStudentHoliday['changed'],
					'creator_id' => $aStudentHoliday['creator_id'],
					'editor_id' => $aStudentHoliday['creator_id'],
					'holiday_id' => $iHolidayId,
					'journey_course_id' => empty($aSplitting['inquiry_course_id']) ? null : $aSplitting['inquiry_course_id'],
					'journey_split_course_id' => empty($aSplitting['inquiry_split_course_id']) ? null : $aSplitting['inquiry_split_course_id'],
					'journey_accommodation_id' => empty($aSplitting['inquiry_accommodation_id']) ? null : $aSplitting['inquiry_accommodation_id'],
					'journey_split_accommodation_id' => empty($aSplitting['inquiry_split_accommodation_id']) ? null : $aSplitting['inquiry_split_accommodation_id'],
					'original_weeks' => $aOriginalService['weeks'],
					'original_from' => $aOriginalService['from'],
					'original_until' => $aOriginalService['until'],
				]);

			}

			$this->logInfo(vsprintf('Migrated student holiday for inquiry %d (holiday %d)', [
				$iInquiryId,
				$iHolidayId
			]));

		}

	}

	private function searchSchoolHoliday($iSchoolId, $sFrom, $sUntil) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_absence`
			WHERE
				`item` = 'holiday' AND
				`item_id` = :school_id AND
			    `from` >= :from AND
			    `until` <= :until
			ORDER BY
			    `active` DESC,
				`id` DESC
		";

		$aResult = DB::getQueryRow($sSql, [
			'school_id' => $iSchoolId,
			'from' => $sFrom,
			'until' => $sUntil
		]);

		return $aResult;

	}

	private function searchOriginalService($iHolidayId, $sType, $iTypeId) {

		$sTable = 'kolumbus_inquiries_holidays_'.$sType.'save';
		$sField = 'inquiry_'.$sType.'_id';

		$sSql = "
			SELECT		
				*
			FROM
				{$sTable}
			WHERE
				`holiday_id` = :holiday_id AND
			    {$sField} = :type_id AND
			    `active` = 1
			ORDER BY
				`id` DESC
		";

		return (array)DB::getQueryRow($sSql, [
			'holiday_id' => $iHolidayId,
			'type_id' => $iTypeId
		]);

	}

}
