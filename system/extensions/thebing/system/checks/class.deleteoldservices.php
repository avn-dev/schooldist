<?php


class Ext_Thebing_System_Checks_Deleteoldservices extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Delete old Client Services'; 
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Delete old Client Services';
		return $sDescription;
	}

	public function executeCheck(){ 

		$mSuccess1 = Ext_Thebing_Util::backupTable('ts_inquiries_journeys');
		$mSuccess2 = Ext_Thebing_Util::backupTable('ts_inquiries_journeys_courses');
		$mSuccess3 = Ext_Thebing_Util::backupTable('ts_inquiries_journeys_accommodations');
		$mSuccess4 = Ext_Thebing_Util::backupTable('ts_inquiries_journeys_transfers');
		
		if(
			!empty($mSuccess1) &&
			!empty($mSuccess2) &&
			!empty($mSuccess3) &&
			!empty($mSuccess4)
		){
			
			/**
			 * löscht alle gebuchten Course welche einen Schulkurs haben von einer Schule die nicht zum journey passt 
			 */
			$sSql = "
			DELETE `t1` FROM  
				`ts_inquiries_journeys_courses` `t1` INNER JOIN
				`ts_inquiries_journeys` `t2` ON
					`t2`.`id` = `t1`.`journey_id`
			WHERE 
				`t1`.`course_id` NOT IN ( 
					SELECT 
						`id` 
					FROM 
						`kolumbus_tuition_courses`
					WHERE 
						`school_id` = `t2`.`school_id`
					)

			";

			DB::executeQuery($sSql);

			/**
			 * löscht alle gebuchten Unterkünfte welche einen Schulunterkunft haben von einer Schule die nicht zum journey passt 
			 */
			$sSql = "
			DELETE `t1` FROM  
				`ts_inquiries_journeys_accommodations` `t1` INNER JOIN
				`ts_inquiries_journeys` `t2` ON
					`t2`.`id` = `t1`.`journey_id`
			WHERE 
				`t1`.`accommodation_id` NOT IN ( 
					SELECT 
						`id` 
					FROM 
						`kolumbus_accommodations_categories`
					WHERE 
						`school_id` = `t2`.`school_id`
					)

			";

			DB::executeQuery($sSql);

			/**
			 * löscht alle gebuchten Transfere welche einen SchulTransfere haben von einer Schule die nicht zum journey passt 
			 */
			$sSql = "
			DELETE `t1` FROM  
				`ts_inquiries_journeys_transfers` `t1` INNER JOIN
				`ts_inquiries_journeys` `t2` ON
					`t2`.`id` = `t1`.`journey_id`
			WHERE 
				`t1`.`start_type` = 'location' AND
				`t1`.`start` NOT IN ( 
					SELECT 
						`id` 
					FROM 
						`kolumbus_airports`
					WHERE 
						`idPartnerschool` = `t2`.`school_id`
					)

			";

			DB::executeQuery($sSql);

			/**
			 * löscht alle gebuchten Transfere welche einen SchulTransfere haben von einer Schule die nicht zum journey passt 
			 */
			$sSql = "
			DELETE `t1` FROM  
				`ts_inquiries_journeys_transfers` `t1` INNER JOIN
				`ts_inquiries_journeys` `t2` ON
					`t2`.`id` = `t1`.`journey_id`
			WHERE 
				`t1`.`end_type` = 'location' AND
				`t1`.`end` NOT IN ( 
					SELECT 
						`id` 
					FROM 
						`kolumbus_airports`
					WHERE 
						`idPartnerschool` = `t2`.`school_id`
					)

			";

			DB::executeQuery($sSql);


			/**
			 * löscht alle gebuchten kurse wo die Journey ID fehlerhaft ist ( nicht existiert )
			 */
			$sSql = "
			DELETE FROM  
				`ts_inquiries_journeys_courses`
			WHERE 
				`journey_id` NOT IN 
				( 
					SELECT 
						`id` 
					FROM 
						`ts_inquiries_journeys`
				)
			";

			DB::executeQuery($sSql);

			/**
			 * löscht alle gebuchten unterkünfte wo die Journey ID fehlerhaft ist ( nicht existiert )
			 */
			$sSql = "
			DELETE FROM  
				`ts_inquiries_journeys_accommodations`
			WHERE 
				`journey_id` NOT IN 
				( 
					SELECT 
						`id` 
					FROM 
						`ts_inquiries_journeys`
				)

			";

			DB::executeQuery($sSql);

			/**
			 * löscht alle gebuchten transfere wo die Journey ID fehlerhaft ist ( nicht existiert )
			 */
			$sSql = "
			DELETE FROM  
				`ts_inquiries_journeys_transfers`
			WHERE 
				`journey_id` NOT IN 
				( 
					SELECT 
						`id` 
					FROM 
						`ts_inquiries_journeys`
				)

			";

			DB::executeQuery($sSql);
			
		}

		return true;		
	}
}