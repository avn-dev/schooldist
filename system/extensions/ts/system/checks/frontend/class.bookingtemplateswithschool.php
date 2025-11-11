<?php

class Ext_TS_System_Checks_Frontend_BookingTemplatesWithSchool extends GlobalChecks {

	public function getTitle() {
		return 'Add school field to template bookings (new pre-filled V3 booking forms)';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		Util::backupTable('ts_frontend_booking_templates');

		DB::addField('ts_frontend_booking_templates', 'school_id', 'SMALLINT UNSIGNED NOT NULL', 'form_id', 'INDEX');

		DB::addField('ts_frontend_booking_templates', 'description', 'VARCHAR(512) NOT NULL', 'school_id');

		// Schule anhand des ausgewählten Kurses setzen
		DB::executeQuery("
			UPDATE
				ts_frontend_booking_templates ts_fbt INNER JOIN
				kolumbus_tuition_courses ktc ON
					ktc.id = ts_fbt.course_id INNER JOIN
				kolumbus_forms_schools kfs ON
					kfs.form_id = ts_fbt.form_id AND
					kfs.school_id = ktc.school_id
			SET
				ts_fbt.school_id = ktc.school_id,
			    ts_fbt.changed = ts_fbt.changed
			WHERE
				ts_fbt.school_id = 0
		");

		return true;

	}

}