<?php

class Ext_Thebing_System_Checks_Tuition_Examination_TemplateTerms extends GlobalChecks {

	public function getTitle() {
		return 'Tuition Examimation Templates';
	}
	
	public function getDescription() {
		return 'Migration of configured terms';
	}

	public function executeCheck() {

		if(!DB::getDefaultConnection()->checkTable('kolumbus_examination_templates_terms')) {
			return true;
		}

		if(!Util::backupTable('kolumbus_examination_templates_terms')) {
			throw new RuntimeException('Table backup has failed');
		}

		DB::begin(__CLASS__);

		// Da Spalte früher mal dynamisch durch GUI angelegt wurde…
		$sCreatorIdField = " `ket`.`creator_id` ";
		if(!DB::getDefaultConnection()->checkField('kolumbus_examination_templates', 'creator_id')) {
			$sCreatorIdField = " 0 ";
		}

		DB::executeQuery("
			INSERT INTO
				`ts_examinations_templates_terms` (
					`created`,
					`changed`,
					`creator_id`,
					`editor_id`,
					`template_id`,
					`type`,
					`period`,
					`period_length`,
					`period_unit`,
					`start_from`
				)
			SELECT
				`ket`.`created`,
				NOW(),
				{$sCreatorIdField},
				`ket`.`user_id`,
				`examination_template_id`,
				'individual',
				IF(
					`period` = 2,
					'recurring',
					'one_time'

				),
				`period_length`,
				IF(
					`unit` = 2,
					'weeks',
					'days'
				),
				IF(
					`begin_from` = 2,
					'before_course_end',
					'after_course_start'
				)
			FROM
				`kolumbus_examination_templates_terms` `kett` INNER JOIN
				`kolumbus_examination_templates` `ket` ON
					`ket`.`id` = `kett`.`examination_template_id`
			WHERE
				`ket`.`active` = 1
		");

		DB::commit(__CLASS__);

		DB::executeQuery(" DROP TABLE `kolumbus_examination_templates_terms` ");

		return true;

	}

}