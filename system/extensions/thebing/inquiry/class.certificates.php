<?php

/**
 * @TODO Es darf nicht mehr als eine Klasse für eine Entität geben
 */
class Ext_Thebing_Inquiry_Certificates extends Ext_Thebing_Basic
{
	// Tabellenname
	protected $_sTable = 'ts_inquiries';

	public function getListQueryData($oGui=null)
	{

		$aQueryData	= array();
		$oSchool					= Ext_Thebing_School::getSchoolFromSession();
		$sWhereShowWithoutInvoice	= Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ki`');

		$aWherePart[] = '`ts_i_j`.`school_id` = '.$oSchool->id;
		$aWherePart[] = '`ki`.`active` = 1';
		$aWherePart[] = '`ki`.`canceled` <= 0';
		$aWherePart[] = substr(trim($sWhereShowWithoutInvoice), 3);
		$aWherePart[] = "`kic`.`active` = 1";
		$aWherePart[] = "`kic`.`visible` = 1";
		$aWherePart[] = "`cdb1`.`active` = 1";
		$aWherePart[] = "`ktc`.`active` = 1";

		$sWherePart = implode(' AND ', $aWherePart);

		$sSubPartProgress = Ext_Thebing_Tuition_Progress::getSqlSubPart('<= :filter_until_1');
		
		$sSqlAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', array(
			'inquiry_id' => '`ki`.`id`'
		));

		$aQueryData['sql'] = "
			SELECT
				`ki`.*,
				`tc_c_n`.`number` `customerNumber`,
				`cdb1`.`lastname`,
				`cdb1`.`firstname`,
				`ki`.`checkin`,
				`d_l_cl`.`name_".System::getInterfaceLanguage()."` `corresponding_language`,
				calcWeeksPart(MIN(`kic`.`from`), MAX(`kic`.`until`)) `weeks_sum`,
				MIN(`kic`.`from`) `course_from`,
				MAX(`kic`.`until`)`course_until`,
				## Kursliste
				GROUP_CONCAT(
					DISTINCT CONCAT(
						`ktc`.`name_".\Ext_Thebing_School::fetchInterfaceLanguage()."`
					)
					SEPARATOR ', '
				) `courses`,
				## Kurskategorien
				GROUP_CONCAT(
					DISTINCT CONCAT(
						`ktcc`.`id`
					)
				) `courses_categories`,
				## level
				GROUP_CONCAT(
					DISTINCT CONCAT(
						IFNULL(
							`ktul_progress`.#name_field,
							`ktul_placement`.#name_field
						)
					)
					SEPARATOR ', '
				) `course_niveau`,
				## attendance
				(
					".$sSqlAttendance."
				) `attendance_all`,
				`kvs`.`name` `visum_title`,
				## certificates
				(
					SELECT
						`sub_kidv`.`path`
					FROM
						`kolumbus_inquiries_documents` `sub_kid` INNER JOIN
						`kolumbus_inquiries_documents_versions` `sub_kidv` ON
							`sub_kidv`.`document_id` = `sub_kid`.`id` INNER JOIN
						`kolumbus_pdf_templates` `sub_kpt` ON
							`sub_kpt`.`id` = `sub_kidv`.`template_id`
					WHERE
						`sub_kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
						`sub_kid`.`entity_id` = `ki`.`id` AND
						`sub_kid`.`active` = 1 AND
						`sub_kidv`.`active` = 1 AND
						`sub_kid`.`type` = 'additional_document' AND
						`sub_kpt`.`type` = 'document_certificates'
					ORDER BY
						`sub_kid`.`created` DESC,
						`sub_kidv`.`version` DESC
					LIMIT 1
				) `pdf_certificate`,
				`ts_j_t_v_d`.`status` `visa_status`,
				UNIX_TIMESTAMP(`ki`.`created`) `created`,
				UNIX_TIMESTAMP(`ki`.`created`) `changed`,
				`kg`.`name` `group_name`, -- Feld holen für HAVING-Suche
				`kg`.`short` `group_short`,
				`kg`.`number` `group_number`
			FROM
				`".$this->_sTable."` `ki` LEFT JOIN
				`kolumbus_groups` `kg` ON
					`kg`.`id` = `ki`.`group_id` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ki`.`id` AND
					`ts_i_j`.`type` & '".Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `cdb1` ON
					`ts_i_to_c`.`contact_id` = `cdb1`.`id` INNER JOIN
				`tc_contacts_numbers` `tc_c_n` ON
					`tc_c_n`.`contact_id` = `cdb1`.`id` LEFT JOIN
				`ts_journeys_travellers_visa_data` `ts_j_t_v_d` ON
					`ts_j_t_v_d`.`journey_id` = `ts_i_j`.`id` AND
					`ts_j_t_v_d`.`traveller_id` = `cdb1`.`id` LEFT JOIN
				`kolumbus_visum_status` `kvs` ON
					`ts_j_t_v_d`.`status` = `kvs`.`id` INNER JOIN
				`ts_inquiries_journeys_courses` `kic` ON
					`ts_i_j`.`id` = `kic`.`journey_id` INNER JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`program_id` = `kic`.`program_id` AND
					`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
					`ts_tcps`.`active` = 1 INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ts_tcps`.`type_id` = `ktc`.`id` AND
					`ktc`.`per_unit` != ".Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." LEFT JOIN
				`ts_tuition_courses_to_courselanguages` `ts_tctc` ON
					`ts_tctc`.`course_id` = `ktc`.`id` LEFT JOIN
				`ts_tuition_courselanguages` `ktlg` ON
					`ktlg`.`id` = `ts_tctc`.`courselanguage_id` AND
					`ktlg`.`active` = 1 LEFT JOIN
				`ts_tuition_coursecategories` `ktcc` ON
					`ktcc`.`id` = `ktc`.`category_id` AND
					`ktcc`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_progress` `ktp` ON
					`ktp`.`inquiry_id` = `ki`.`id` AND
					`ktp`.`courselanguage_id` = `ktlg`.`id` AND
					`ktp`.`program_service_id` = `ts_tcps`.`id` AND
					`ktp`.`active` = 1 AND
					`ktp`.`week` = (
						".$sSubPartProgress."
					) LEFT JOIN
				`ts_placementtests_results` `kptr` ON
					`kptr`.`inquiry_id` = `ki`.`id` AND
					`kptr`.`active` = 1 LEFT JOIN
				`ts_tuition_levels` `ktul_progress` ON
					`ktul_progress`.`id` = `ktp`.`level` AND
					`ktul_progress`.`active` = 1 LEFT JOIN
				`ts_tuition_levels` `ktul_placement` ON
					`ktul_placement`.`id` = `kptr`.`level_id` AND
					`ktul_placement`.`active` = 1 LEFT JOIN
				`data_languages` `d_l_cl` ON
					`d_l_cl`.`iso_639_1` = `cdb1`.`corresponding_language`
			WHERE
				".$sWherePart."
			GROUP BY
				`ki`.`id`
		";

		return $aQueryData;
	}

}
