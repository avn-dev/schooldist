<?php

namespace Ts\Service\Archive;

class Classes extends AbstractArchiveService {
	
	public function run() {
		
		$tables = [
			'kolumbus_tuition_classes',
			'kolumbus_tuition_classes_courses',
			'kolumbus_tuition_blocks',
			'kolumbus_tuition_blocks_days',
			'ts_tuition_blocks_daily_units',
			'kolumbus_tuition_blocks_inquiries_courses',
			'kolumbus_tuition_blocks_substitute_teachers',
			'kolumbus_tuition_blocks_to_rooms',
			'kolumbus_tuition_attendance',
			'ts_tuition_attendance_tracking_sessions',
		];

		foreach($tables as $table) {
			
			$backupSuccess = \Util::backupTable($table, false, 'archive_'.$table);
			
			if($backupSuccess === false) {
				throw new \RuntimeException('Backup of table "'.$table.'" failed!');
			}
			
		}
		
		$sqlQuery = "	
			DELETE 
				`ktc`,
				`ktcc`,
				`ktb`,
				`ktbd`,
				`ktbdc`,
				`ktbic`,
				`ktbst`,
				`ktbtr`,
				`kta`,
				`ts_tats`
			FROM
				`kolumbus_tuition_classes` `ktc` JOIN						
				`kolumbus_tuition_classes_courses` `ktcc` ON
					`ktc`.`id` = `ktcc`.`class_id` JOIN
				`kolumbus_tuition_blocks` `ktb` ON						
					`ktc`.`id` = `ktb`.`class_id` JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktb`.`id` = `ktbd`.`block_id` LEFT JOIN
				`ts_tuition_blocks_daily_units` `ktbdc` ON			
					`ktb`.`id` = `ktbdc`.`block_id` LEFT JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON		
					`ktb`.`id` = `ktbic`.`block_id` LEFT JOIN
				`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON		
					`ktb`.`id` = `ktbst`.`block_id` LEFT JOIN
				`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON				
					`ktb`.`id` = `ktbtr`.`block_id` LEFT JOIN
				`kolumbus_tuition_attendance` `kta` ON					
					`ktb`.`id` = `kta`.`allocation_id` LEFT JOIN
				`ts_tuition_attendance_tracking_sessions` `ts_tats` ON			
					`ktb`.`id` = `ts_tats`.`block_id`
			WHERE
				DATE_ADD(`ktc`.`start_week` , INTERVAL `ktc`.`weeks` week) < MAKEDATE(YEAR(DATE_SUB(NOW(), INTERVAL 5 YEAR)), 1)
		";
		
		\DB::executeQuery($sqlQuery);
		
		return true;
	}

}
