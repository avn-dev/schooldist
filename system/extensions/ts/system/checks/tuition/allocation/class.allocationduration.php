<?php

/**
 * lesson_duration von Tuition Allocations neu berechnen
 *
 * @see \Ext_Thebing_School_Tuition_Allocation::$lesson_duration
 */
class Ext_TS_System_Checks_Tuition_Allocation_AllocationDuration extends GlobalChecks {

	public function getTitle() {
		return 'Tuition Allocation Check';
	}

	public function getDescription() {
		return 'Check allocated lesson count of tuition allocations.';
	}

	public function executeCheck() {

		// Dieser Check ist nicht korrekt, da der Leistungszeitraum beachtet werden muss (Blockwoche länger als Kurs)
		return false;

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');
		
		if(!Util::backupTable('kolumbus_tuition_blocks_inquiries_courses')) {
			__pout('Could not backup table!');
			return false;
		}
		
//		$aColumns = DB::describeTable('kolumbus_tuition_blocks_inquiries_courses', true);
//		if(!isset($aColumns['lesson_duration'])) {
//			$rRes = DB::addField('kolumbus_tuition_blocks_inquiries_courses', 'lesson_duration', 'DECIMAL( 10, 2 ) NOT NULL DEFAULT \'0.00\'');
//
//			if(!$rRes) {
//				__pout('Couldnt add inquiry_id field!');
//				return false;
//			} else {
//				$sCacheKey = 'wdbasic_table_description_kolumbus_tuition_blocks_inquiries_courses';
//				WDCache::delete($sCacheKey);
//			}
//		}
		
		$sSql = "
			SELECT
				`ktbic`.`id`,
				`ktbic`.`lesson_duration`,
				DATE(`ktbic`.`created`) `created`,
				(
					(
						SELECT
							COUNT(*)
						FROM
							`kolumbus_tuition_blocks_days` `ktbd`
						WHERE
							`ktbd`.`block_id` = `ktb`.`id`
					) * `ktt`.`lessons` * `ktc`.`lesson_duration`
				) `duration`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ktbic`.`course_id` AND
					`ktc`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 1 INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id` AND
					`ktt`.`active` = 1
			WHERE
				`ktbic`.`active` = 1
			HAVING
				`ktbic`.`lesson_duration` != `duration`
		";
		
		$oDB = DB::getDefaultConnection();
		
		$oCollection = $oDB->getCollection($sSql);

		$this->logInfo('Found '.count($oCollection).' entries with wrong duration');

		foreach($oCollection as $aRowData) {

			$aData = ['lesson_duration' => (int)$aRowData['duration']];
			DB::updateData('kolumbus_tuition_blocks_inquiries_courses', $aData, ['id' => $aRowData['id']], true);

			$this->logInfo('ktbic '.$aRowData['id'].': Set durations from '.$aRowData['lesson_duration'].' to '.$aRowData['duration'].' (created: '.$aRowData['created'].')');

		}

		$this->logInfo('Finished');

		return true;

	}

}
