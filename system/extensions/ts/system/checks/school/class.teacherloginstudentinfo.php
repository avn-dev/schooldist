<?php

class Ext_TS_System_Checks_School_TeacherLoginStudentInfo extends GlobalChecks
{
	public function getTitle()
	{
		return 'Teacher Login';
	}

	public function getDescription()
	{
		return 'Sets the default columns for student information in the teacher portal';
	}

	public function executeCheck()
	{
		if (empty($schools = $this->getSchools())) {
			return true;
		}

		if (!\Util::backupTable('wdbasic_attributes')) {
			__pout('Backup error');
			return false;
		}

		$payload = json_encode(['age', 'gender', 'nationality']);

		foreach ($schools as $id) {
			\DB::insertData('wdbasic_attributes', [
				'entity' => 'customer_db_2',
				'entity_id' => $id,
				'key' => 'teacherlogin_student_informations',
				'value' => $payload,
			]);
		}

		return true;
	}

	private function getSchools(): array
	{
		$sql = "
			SELECT 
				`cdb2`.`id`
			FROM
			    `customer_db_2` `cdb2` LEFT JOIN 
			    `wdbasic_attributes` `attr` ON 
			    	`attr`.`entity` = 'customer_db_2' AND
			    	`attr`.`key` = 'teacherlogin_student_informations' AND
			    	`attr`.`entity_id` = `cdb2`.`id`
			WHERE
			    `cdb2`.`active` = 1 AND
			    `attr`.`value` IS NULL
		";

		return (array)\DB::getQueryCol($sql);
	}

}
