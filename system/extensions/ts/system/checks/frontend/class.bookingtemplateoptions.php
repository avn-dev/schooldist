<?php

class Ext_TS_System_Checks_Frontend_BookingTemplateOptions extends GlobalChecks
{
	public function getTitle()
	{
		return 'Add new options to booking templates';
	}

	public function getDescription()
	{
		return 'Course field can be disabled or hidden, and a course language can be selected and disabled or hidden.';
	}

	public function executeCheck()
	{
		$table = DB::describeTable('ts_frontend_booking_templates', true);
		if (!str_contains($table['course_id_locked']['DATA_TYPE'], 'int')) {
			return true;
		}

		Util::backupTable('ts_frontend_booking_templates');

		DB::executeQuery("ALTER TABLE `ts_frontend_booking_templates` CHANGE course_id_locked course_id_locked_ TINYINT(1) NOT NULL DEFAULT '0'");

		DB::addField('ts_frontend_booking_templates', 'course_id_locked', "ENUM('no','disabled','hidden') NOT NULL DEFAULT 'no'", 'course_id');

		DB::addField('ts_frontend_booking_templates', 'courselanguage_id', "SMALLINT UNSIGNED NULL DEFAULT NULL");

		DB::addField('ts_frontend_booking_templates', 'courselanguage_id_locked', "ENUM('no','disabled','hidden') NOT NULL DEFAULT 'no'");

		DB::executeQuery("
			UPDATE
				ts_frontend_booking_templates
			SET
			    course_id_locked = 'hidden'
			WHERE
			    course_id_locked_ = 1
		");

		DB::executeQuery("ALTER TABLE ts_frontend_booking_templates DROP course_id_locked_");

		return true;
	}
}