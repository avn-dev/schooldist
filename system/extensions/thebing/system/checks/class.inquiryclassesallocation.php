<?php

class Ext_Thebing_System_Checks_InquiryClassesAllocation extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Update inquiry courses to classes allocation';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$sSql = "
			SELECT
				`ktbic`.`id` `id`,
				IF(cd3.combination = 1, `kcc`.`course_id`, `kic`.`course_id`) `course_id`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktbic`.`block_id` = `ktb`.`id` JOIN
				`kolumbus_inquiries_courses` `kic` ON
					`kic`.`id` = `ktbic`.`inquiry_course_id` JOIN
				`customer_db_3` cd3 ON
					 cd3.id = kic.course_id LEFT OUTER JOIN
				`kolumbus_course_combination` kcc ON
					kcc.master_id = kic.course_id AND
					 cd3.combination = 1 LEFT OUTER JOIN

				`kolumbus_tuition_classes_courses` `ktcc` ON
					`ktcc`.`class_id` = `ktb`.`class_id` AND
					IF(cd3.combination = 1, `kcc`.`course_id`, `kic`.`course_id`) = `ktcc`.`course_id`  LEFT OUTER JOIN
    			
				`customer_db_3` cd3_2 ON
					 cd3_2.id = kcc.course_id

			WHERE
				`ktbic`.`active` = 1 AND
				`ktbic`.`course_id` = 0
			";
		$aItems = DB::getQueryRows($sSql);

		$iItems = 0;
		foreach((array)$aItems as $aItem) {

			$aData = array();

			$aData['course_id'] = (int)$aItem['course_id'];

			DB::updateData('kolumbus_tuition_blocks_inquiries_courses', $aData, '`id` = '.(int)$aItem['id']);

			$iItems++;

		}

		echo '<p>'.$iItems.' items updated.</p>';

		return true;

	}

}
