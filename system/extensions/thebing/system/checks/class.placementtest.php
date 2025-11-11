<?php

class Ext_Thebing_System_Checks_Placementtest extends GlobalChecks
{
	public function executeCheck()
	{
		$sSqlResultsInquiryCourseTable = "
			CREATE TABLE IF NOT EXISTS `kolumbus_placementtests_results_inquirycourse` (
			  `placementtest_result_id` int(11) NOT NULL,
			  `inquiry_course_id` int(11) NOT NULL,
			  `active` tinyint(1) NOT NULL default '1',
			  KEY `results_inquirycourse_fk_placementtest_result_id` (`placementtest_result_id`),
			  KEY `results_inquirycourse_inquiry_course_id` (`inquiry_course_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		";
		DB::executeQuery($sSqlResultsInquiryCourseTable);

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_pt_results`
		";
		$aResult = DB::getPreparedQueryData($sSql, array());

		Ext_Thebing_Util::backupTable('kolumbus_pt_results');

		// Tabelle ändern
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` DROP COLUMN `inquiry_id`';
		DB::executeQuery($sSqlTable);
		$sSqlTable = "ALTER TABLE `kolumbus_pt_results` CHANGE `active` `active` TINYINT(1) NOT NULL DEFAULT '1'";
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` ADD COLUMN `user_id` INT NOT NULL AFTER `created`';
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` ADD COLUMN `placementtest_date` DATE NOT NULL AFTER `user_id`';
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` ADD COLUMN `placementtest_result_date` DATE NOT NULL AFTER `placementtest_date`';
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` CHANGE `level` `level_id` INT NOT NULL';
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` CHANGE `mark` `mark` TINYINT(4) NULL';
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` ADD COLUMN `score` VARCHAR(255) NOT NULL';
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` ADD COLUMN `comment` text NOT NULL';
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'ALTER TABLE `kolumbus_pt_results` ADD COLUMN `examiner_name` VARCHAR(255) NOT NULL';
		DB::executeQuery($sSqlTable);
		$sSqlTable = 'RENAME TABLE `kolumbus_pt_results` TO `kolumbus_placementtests_results`';
		DB::executeQuery($sSqlTable);

		if(is_array($aResult))
		{
			foreach($aResult as $aDataOld)
			{
				$iPlacementtestID	= $aDataOld['id'];
				$iInquiryID			= $aDataOld['inquiry_id'];
				$iOldCreated		= strtotime($aDataOld['created']);
				if(false !== $iOldCreated)
				{
					$dOldCreated		= date('Y-m-d', $iOldCreated);
					$sSql = "
						UPDATE 
							`kolumbus_placementtests_results`
						SET 
							`placementtest_result_date` = :old_created
						WHERE
							`id` = :placementtest_result_id
					";
					DB::executePreparedQuery($sSql, array(
						'old_created'				=> $dOldCreated,
						'placementtest_result_id'	=> $iPlacementtestID,
					));
				}

				$aInquiryCourses	= Ext_TS_Inquiry_Journey_Course::getInquiryCourses($iInquiryID, false);

				if( 0 < $iPlacementtestID )
				{
					foreach($aInquiryCourses as $aDataInquiryCourse)
					{
						$sSql = "
							INSERT INTO
								`kolumbus_placementtests_results_inquirycourse` (`placementtest_result_id`,`inquiry_course_id`)
							VALUES(:placementtest_result_id,:inquiry_course_id)
						";

						$aSql = array(
							'placementtest_result_id'	=> $iPlacementtestID,
							'inquiry_course_id'			=> $aDataInquiryCourse['id']
						);

						DB::executePreparedQuery($sSql, $aSql);
					}
				}
			}
		}

		return true;
	}

	public function getTitle()
	{
		return 'Placementtests Results Import';
	}

	public function  getDescription()
	{
		return 'Import old placementtest results into the new table.';
	}
}
?>
