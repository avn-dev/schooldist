<?php

class Ext_Thebing_School_Tuition_Block_Students extends Ext_Thebing_Basic {

	/**
	 * query_id_column = inquiry_course_id
	 *
	 * @var string
	 */
	protected $_sTable = 'ts_inquiries_journeys_courses';

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		global $_VARS;

        $interfaceLanguage = \Ext_Thebing_School::fetchInterfaceLanguage();

		$sSearchGroup = '';

		if(is_numeric($_VARS['filter']['group'])) {

			$iSearchGroup = (int)$_VARS['filter']['group'];

			if ($iSearchGroup === 1) {
				$sSearchGroup = " AND `ki`.`group_id` != 0 ";
			} elseif ($iSearchGroup === 0) {
				$sSearchGroup = " AND `ki`.`group_id` = 0 ";
			}
		}

		$sSearchGroup .= " AND `ki`.`confirmed` > 0 ";

		$sDocumentTypeFilter = $_VARS['filter']['document_type_filter'];

		if ($sDocumentTypeFilter === 'invoice') {
			$sSearchGroup = " AND `ki`.`has_invoice` = 1";
		} elseif($sDocumentTypeFilter === 'proforma') {
			$sSearchGroup = " AND `ki`.`has_proforma` = 1 AND `ki`.`has_invoice` = 0 ";
		}

		if (!empty($_VARS['filter']['agency_filter'])) {
			$agencyIdFilter = $_VARS['filter']['agency_filter'];
			$sSearchGroup .= " AND `ki`.`agency_id` = ".$agencyIdFilter;
		}
		
		$sSubSqlForLevel			= Ext_Thebing_Tuition_Progress::getSqlSubPart('<= :filter_week');
		$sWhereShowWithoutInvoice	= Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ki`');

		if($sView == 'allocated') {
			$sAttendanceJoinPart = "
				kta.allocation_id = ktbic.id
			";
		} else {
			$sAttendanceJoinPart = "
				kta.journey_course_id = kic.id AND
				kta.course_id = ktc_2.id AND
				kta.week = :filter_week
			";
		}
		
		$aSqlParts['select'] = "
					## ==================================================
					## Nachfolgende Werte werden für die Sortierung in der Klassenplanung gebraucht.
					## Es ist nicht weiter schlimm, wenn irgendwann diese 'Column-Names' nochmal gebraucht werden im Verlauf dieser Query.
					## In diesem Fall werden die Nullen einfach mit dem tatsächlichen Wert überschrieben.

					0 `current_week`,
					0 `all_weeks`,
					0 `crs_time_from`,
					0 `crs_time_to`,

					## ==================================================

					cdb1.lastname,
					cdb1.firstname,
					cdb1.gender,
					tc_c_n.number customerNumber,
					getAge(
						`cdb1`.`birthday`
					) `age`,
					`cdb1`.`language` mother_tongue_iso,
					`cdb1`.`nationality` nationality_iso,
					ki.id id,
					ki.inbox inbox,
					`ki`.`checkin` `checkin`,
					kic.id inquiry_course_id,
					kic.flexible_allocation,
					`kic`.`state` `journey_course_state`,
					`kic`.`lessons_catch_up_original_until` `lessons_catch_up_original_until`,
					ktbic.id allocation_id,
					ts_i_j.school_id,
					ki.status_id status_id,
					kg.short as group_short,
					kg.name as group_name,
					kg.number as group_number,
					kg.course_closed,
					ktc_2.name_".$interfaceLanguage.",
					/* TODO: Wird level überhaupt noch benötigt? In den Columns wird nur ktp.level_id benutzt */
					/*COALESCE(
						IF(
							ktul.name_short != '',
							ktul.name_short,
							ktul.#language_name
						),
						COALESCE(
							IF(
								ktul_test.name_short != '',
								ktul_test.name_short,
								ktul_test.#language_name
							),
							''
						)
					) level,*/
					IF (
								ktc_2.`name_short` != '',
								ktc_2.`name_short`,
								ktc_2.#language_name
					) course_type,
					/* 
						Tatsächliche Anzahl an verfügbaren Lektionen
						Falls wir in der durch Klassenausfall verlängerten letzten Woche der Kursbuchung sind müssen
				    	hier alle noch verfügbaren Lektionen angezeigt werden 
				    */
					IF (
						`titi`.`state` & '".Ext_TS_Inquiry_TuitionIndex::STATE_LAST."' AND
						`kic`.`state` & '".\Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION."' AND
						`ts_ijclc`.`lessons_unit` = 'per_week',
						/* Schauen wie viele 'Pro Woche' Lektionen bisher in den verlängerten Kurswochen gebraucht wurden */
						`ts_ijclc`.`cancelled` - ((`tijcti`.`current_week` - `kic`.`weeks` - 1) * `ts_ijclc`.`lessons`),
						`ts_ijclc`.`lessons`
					) `course_lessons`,
					IF (
						`kic`.`state` & '".\Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION."' AND
						:filter_week > `kic`.`lessons_catch_up_original_until`,
						0,
						`ts_ijclc`.`lessons`
					) `booked_lessons`,
					COALESCE(
						IF(
							`ts_ijclc`.`lessons_unit` = 'per_week',
							/* Bei Wochenkursen kann direkt der Wert aus dem Join benutzt werden */
							`tijcti`.`allocated_lessons`,
							/* Bei Lektionskursen muss das gesamte Kontingent betrachtet werden */
							`ts_ijclc`.`used`
						)
					, 0) `allocation_lessons`,
					COALESCE(
						IF(
							`ts_ijclc`.`lessons_unit` = 'per_week',
							/* Bei Wochenkursen kann direkt der Wert aus dem Join benutzt werden */
							`tijcti`.`cancelled_lessons`,
							/* Bei Lektionskursen muss das gesamte Kontingent betrachtet werden */
							`ts_ijclc`.`cancelled`
						)
					, 0) `cancelled_lessons`,
					ktcl.name AS blockname,
					/*IF (ktc.combination = 1, kcc.course_id, kic.course_id) AS course_id,*/
					# Das muss über ktc_2 kommen, da kcc.course_id jede Kurs-ID sein kann, wenn Results ausgeschlossen werden durch bereits zugewiesen o.ä. #.6906
					`ktc_2`.`id` `course_id`,

					/* Wurden als Subquery umgeschrieben, da bei vielen Zuweisungen eben auch viele Einträge hier rauskommen können */
					IFNULL(
						(
							SELECT
								GROUP_CONCAT(DISTINCT `kta`.`score` SEPARATOR ', ')
							FROM
								`kolumbus_tuition_attendance` `kta`
							WHERE
								`kta`.`active` = 1 AND
								{$sAttendanceJoinPart}
						),
						`ptr`.`score`
					) `score`,
					IFNULL(
						(
							SELECT
								GROUP_CONCAT(DISTINCT `kta_last_week`.`score` SEPARATOR ', ')
							FROM
								`kolumbus_tuition_attendance` `kta_last_week`
							WHERE
								kta_last_week.active = 1 AND
								kta_last_week.journey_course_id = kic.id AND
								kta_last_week.program_service_id = ts_tcps.id AND
								kta_last_week.week = DATE_SUB(:filter_week, INTERVAL 1 WEEK)
						),
						`ptr`.`score`
					) `score_last_week`,

					IF(COALESCE(kic2.id, 0)>0, 1, 0) parallel_course,
					UNIX_TIMESTAMP(`ts_ih`.`from`) `holiday_from`,
					UNIX_TIMESTAMP(`ts_ih`.`until`) `holiday_to`,
					`ts_ih`.`weeks` `holiday_weeks`,
					ROUND(ABS(DATEDIFF(`ts_ih`.`from`, :course_from)) / 7) + 1 `holiday_current_week`,
					ktp.level `level_id`, /* Wehe, hier fügt irgendjemand ein IF hinzu! */
					`kic`.`comment`,
					`ts_ptr`.`comment` `placementtest_comment`,
					`ts_ptr`.`id` `placementtest_result_id`,
					`ktul_external`.`name_short` `external_level`,
					IF(ktclc.course_id IS NULL, 0, 1) `course_allocated`,
					IF(:current_date BETWEEN `kic`.`from` AND `kic`.`until`, 1, 0) `between_course_date`,
					`ktcc`.`name_".$interfaceLanguage."` `course_category`,
					:view `view`,

					`titi`.`state` `tuition_inquiry_state`,
					`titi`.`from` `tuition_inquiry_from`,
					`titi`.`until` `tuition_inquiry_until`,
					`titi`.`current_week` `tuition_inquiry_current_week`,
					`titi`.`total_weeks` `tuition_inquiry_total_weeks`,
					`tijcti`.`state` `tuition_course_state`,
					`tijcti`.`from` `tuition_course_from`,
					`tijcti`.`until` `tuition_course_until`,
					`tijcti`.`current_week` `tuition_course_current_week`,
					`tijcti`.`total_weeks` `tuition_course_total_weeks`,
					
					`ts_tcps`.`id` `program_service_id`,
					
					`ki`.`amount` + 
					`ki`.`amount_initial` `amount_total_original`,
					`ki`.`amount` + 
					`ki`.`amount_initial` - 
					`ki`.`amount_payed_prior_to_arrival` - 
					`ki`.`amount_payed_at_school` - 
					`ki`.`amount_payed_refund` `amount_open_original`,
					`ki`.`currency_id`,
					`ktlg`.`name_".$interfaceLanguage."` `courselanguage_name`,
					`ts_ijclc`.`absolute` `lesson_contingent_absolute`,
					`ts_ijclc`.`used` `lesson_contingent_used`,
					(`ts_ijclc`.`absolute` - `ts_ijclc`.`used`) `lesson_contingent_remaining`,
					`ts_ijclc`.`lessons` `lesson_contingent_lessons`,
					`ts_ijclc`.`lessons_unit` `lesson_contingent_lessons_unit`
				";

		if(
			isset($_VARS['orderby']) &&
			isset($_VARS['orderby']['db_column']) &&
			$_VARS['orderby']['db_column'] == 'remaining_lessons'
		)
		{
			$sSqlRemainingLessons = $this->getSubPartRemainingLessons();

			$aSqlParts['select'] .= ', ' . $sSqlRemainingLessons;
		}

		$aSqlParts['from'] = "
					`ts_inquiries` ki INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` cdb1 ON
						`ts_i_to_c`.`contact_id` = `cdb1`.`id` AND
						`cdb1`.`active` = 1 LEFT JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `cdb1`.`id` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`inquiry_id` = `ki`.`id` AND
						`ts_i_j`.`active` = 1 AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
					`ts_inquiries_journeys_courses` kic ON
						ts_i_j.id = kic.journey_id AND
						kic.active = 1 AND
						kic.for_tuition = 1 AND
						kic.visible = 1 LEFT JOIN
					`ts_inquiries_tuition_index` `titi` ON
						`titi`.`inquiry_id` = `ki`.`id` AND
						`titi`.`week` = :filter_week LEFT JOIN
					`ts_placementtests_results` `ts_ptr` ON
						`ts_ptr`.`inquiry_id` = `ki`.`id` AND
						`ts_ptr`.`active` LEFT JOIN
					`kolumbus_groups` `kg` FORCE INDEX (PRIMARY) ON
						`kg`.`id` = `ki`.`group_id` LEFT OUTER JOIN
					(
						`kolumbus_tuition_blocks_inquiries_courses` ktbic JOIN
						`kolumbus_tuition_blocks` ktb ON
							 ktb.id = ktbic.block_id AND
							 ktb.active = 1 LEFT JOIN
						`kolumbus_tuition_classes_courses` ktclc ON
							ktb.class_id = ktclc.class_id AND
							ktbic.course_id = ktclc.course_id LEFT JOIN
						`kolumbus_tuition_classes` `ktcl` ON
							ktcl.id = ktb.class_id
					) ON
						 (ktb.week = :filter_week) AND
						kic.id = ktbic.inquiry_course_id AND
						ktbic.active = 1 LEFT OUTER JOIN
    				`ts_tuition_courses_programs_services` `ts_tcps` ON ";

		if($sView == 'allocated') {
			$aSqlParts['from'] .= " `ts_tcps`.`id` = `ktbic`.`program_service_id` AND ";
		} else {
			$aSqlParts['from'] .= "";
		}

		$aSqlParts['from'] .= "
						`ts_tcps`.`program_id` = `kic`.`program_id` AND
						`ts_tcps`.`active` = 1 AND
						`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
						(
							(
								`ts_tcps`.`from` IS NULL AND
								`ts_tcps`.`until` IS NULL
							) OR (
								`ts_tcps`.`from` <= :course_until AND 
								`ts_tcps`.`until` >= :course_from
							)
						) LEFT OUTER JOIN
					`kolumbus_tuition_courses` ktc_2 ON ";

		if($sView == 'allocated') {
			$aSqlParts['from'] .= " `ktc_2`.`id` = `ktbic`.`course_id`";
		} else {
			$aSqlParts['from'] .= " 
				`ktc_2`.`id` = `ts_tcps`.`type_id` AND 
				`ktc_2`.`per_unit` != ".Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." 		
			";
		}

		$aSqlParts['from'] .= " LEFT JOIN
					`ts_tuition_coursecategories` `ktcc` ON
						`ktcc`.`id` = `ktc_2`.`category_id` AND
						`ktcc`.`active` = 1 LEFT JOIN
					`ts_tuition_courses_to_courselanguages` `ts_tctc` ON
						`ts_tctc`.`course_id` = `ktc_2`.`id`LEFT JOIN
					`ts_tuition_courselanguages` `ktlg` ON
						`ktlg`.`id` = `kic`.`courselanguage_id` LEFT JOIN
					`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc` ON
						`ts_ijclc`.`journey_course_id` = `kic`.`id` AND
						`ts_ijclc`.`program_service_id` = `ts_tcps`.`id` LEFT JOIN
					`kolumbus_tuition_progress` ktp ON
						`ktp`.`inquiry_id` = `ki`.`id` AND
						`ktp`.`inquiry_course_id` = `kic`.`id` AND
						`ktp`.`program_service_id` = `ts_tcps`.`id` AND
						`ktp`.`courselanguage_id` = `ktlg`.`id` AND
						`ktp`.`active` = 1 AND
						`ktp`.`week` = (
							".$sSubSqlForLevel."
						) LEFT JOIN
					`ts_inquiries_journeys_courses_tuition_index` `tijcti` ON
						`tijcti`.`journey_course_id` = `kic`.`id` AND
						`tijcti`.`program_service_id` = `ts_tcps`.`id` AND
						`tijcti`.`week` = :filter_week LEFT JOIN
					/* Wird noch für Filter verwendet (aber auch richtig?) */
					`ts_tuition_levels` `ktul` ON
						 ktp.level = ktul.id LEFT OUTER JOIN
					`ts_inquiries_journeys_courses` kic2 ON
						ts_i_j.id = kic2.journey_id AND
						kic2.active = 1 AND
						kic2.visible = 1 AND
						kic2.for_tuition = 1 AND
						kic2.id != kic.id AND
						kic2.from <= :course_until AND kic2.until >= :course_from  LEFT JOIN
					`ts_inquiries_holidays` `ts_ih` ON
						`ts_ih`.`inquiry_id` = `ki`.`id` AND
						`ts_ih`.`type` = 'student' AND
						`ts_ih`.`active` = 1 LEFT JOIN
					`ts_inquiries_holidays_splitting` `ts_ihs` ON
						`ts_ihs`.`holiday_id` = `ts_ih`.`id` AND
						`ts_ihs`.`active` = 1 AND
						`ts_ihs`.`journey_course_id` IS NOT NULL AND
						(
							(
								`ts_ih`.`from` <= :course_until AND
								`ts_ih`.`until` >= :course_from
							) OR (
								`ts_ih`.`from` <= :lastweek_time_until AND
								`ts_ih`.`until` >= :lastweek_time_from
							)
						) LEFT JOIN
					/* Für Score (Level ist nur noch interner Progress) */
					`ts_placementtests_results` AS ptr ON
						`ptr`.`inquiry_id` = `ki`.`id` AND
						`ptr`.`active` = 1 LEFT OUTER JOIN
					/*`ts_tuition_levels` `ktul_test` ON
						`ktul_test`.`id` = `ptr`.`level_id` AND `ktul_test`.`active` = 1 LEFT OUTER JOIN*/
					`ts_tuition_levels` `ktul_external` ON
						`ktul_external`.`id` = `kic`.`level_id`
					";

		$aSqlParts['where'] = "
					`ki`.`active` = 1 AND
					`ki`.`canceled` <= 0 AND
					`ktc_2`.`per_unit` != ".Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." 					
					".$sWhereShowWithoutInvoice." AND
					`ts_i_j`.`id` IN(:journey_ids)
					".$sSearchGroup."
		";

	}
	
	public function getSubPartRemainingLessons()
	{
		$sSubPartSumOfLessons		= Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql('kic.id', 'ktc_2.id', ':filter_week', false);
		$sSubPartSumOfLessonsUnit	= Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql('kic.id', 'ktc_2.id', null, false);

		// Falls wir in der letzten Woche der Kursbuchung sind und diese durch Klassenausfall verlängert wurde müssen
		// hier alle noch verfügbaren Lektionen angezeigt werden
		$sSql = "
			(
				IF (
					`titi`.`state` & '".Ext_TS_Inquiry_TuitionIndex::STATE_LAST."' AND 
					`kic`.`state` & '".\Ext_TS_Inquiry_Journey_Course::STATE_EXTENDED_DUE_CANCELLATION."' AND
		 			`ts_ijclc`.`lessons_unit` = 'per_week', 
		 			/* Schauen wie viele 'Pro Woche' Lektionen bisher in den verlängerten Kurswochen gebraucht wurden */
					`ts_ijclc`.`cancelled` - ((`tijcti`.`current_week` - `kic`.`weeks` - 1) * `ts_ijclc`.`lessons`), 
					`ts_ijclc`.`lessons`
				) -
				IF (
					`ts_ijclc`.`lessons_unit` = 'per_week',
					COALESCE(
						(
							".$sSubPartSumOfLessons."
						)
						, 0),
					COALESCE(
						(
							".$sSubPartSumOfLessonsUnit."
						)
						, 0)
				)
			) `remaining_lessons`
		";
		
		return $sSql;
	}

}
