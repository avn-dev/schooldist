<?php

/**
 * keine "richtige" WDBasic da wir hier keinen einzelnen Dtaensatz betrachten
 */
class Ext_Thebing_Accounting_Teacher_Payment_List extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_teachers';

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {
		
		$aQueryData = array();

		$aQueryData['data'] = array();
		$aQueryData['data']['school_id'] = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');

		$aQueryData['sql'] = "

					SELECT
						`union`.*,
							(
								(
									(
										`union`.`amount` *
										IF(
											`union`.`lesson_school_option` = 1,
											(
												`union`.`lessons` *
												`union`.`lession_durration` /
												60
											),
											`union`.`lessons`
										)
									) *
									IF(
										`union`.`select_type` = 'month' ,
										IF(
											`union`.`days_subtract` > DAY(`union`.`timepoint` + INTERVAL 1 MONTH - INTERVAL 1 SECOND) OR
											`union`.`days_subtract` <= 0 ,
												DAY(`union`.`timepoint` + INTERVAL 1 MONTH - INTERVAL 1 SECOND),
												`union`.`days_subtract`
										) / (
											DAY(`union`.`timepoint` + INTERVAL 1 MONTH - INTERVAL 1 SECOND)
										),
										1
									)
								) +
								IF(
									`union`.`select_type` = 'month' ,
									0,
									(
										`union`.`amount_holiday` *
										(
											IF(
												`union`.`lesson_school_option` = 1,
												COALESCE(`union`.`lessons_holiday`, 0) *
												`union`.`lession_durration` /
												60,
												COALESCE(`union`.`lessons_holiday`, 0)
											)
										)
									)
								)
						) `amount`,
						IF(
							`union`.`select_type` = 'week' ,
							COALESCE(`union`.`lessons`, 0) + COALESCE(`union`.`lessons_holiday`, 0),
							0
						) `lessons`,
						IF(
							`union`.`select_type` = 'week',
							(
								`union`.`lessons` * `union`.`lession_durration` +
								`union`.`lessons_holiday` * `union`.`lession_durration`
							),
							0
						) `hours`,
						`union`.`lessons` `lessons_normal`,
						`union`.`amount` `single_amount`,
						`union`.`amount_holiday` `single_amount_holiday`,
						`union`.`amount` `cost`,
						/*`ktep`.`grouping_id` `grouping_id`,
						`ktep`.`imported` `imported`,
						`ktep`.`comment` `comment`,*/
						`union`.`building_name` `building_name`
						/*GROUP_CONCAT(`ktep`.`comment`) `payed_comment`,
						GROUP_CONCAT(`ktep_additional`.`comment`) `payed_additional_comment`,
						(
							SELECT
								SUM(`ktep`.`amount`)
							FROM
								`ts_teachers_payments` `ktep` 
							WHERE
								`ktep`.`teacher_id` = `union`.`teacher_id` AND
								`ktep`.`block_id` = `union`.`block_id` AND
								`ktep`.`active` = 1 AND
								IF(
									`ktep`.`payment_type` = 'month',
									(
										DATE(`ktep`.`timepoint`) BETWEEN
											DATE(`union`.`timepoint`) AND
											DATE(
												(
													`union`.`timepoint` +
													INTERVAL 1 MONTH
												) -
												INTERVAL 1 SECOND
											)
									),
									`ktep`.`timepoint` = DATE(`union`.`timepoint`)
								)
						) `payed_amount`*/
					FROM
						(

							/* FESTGEHALT pro WOCHE */

							(

								SELECT
									1 `calculation`,
									'fix_week' `select_type`,
									WEEK(`week_table`.`week`,3) `select_value`,
									`week_table`.`week` `timepoint`,
									'' `select_group`,
									`u1_kt`.`id` `teacher_id`,
									`u1_tts`.`school_id` `idSchool`,
									`u1_cdb2`.`currency_teacher` `currency_id`,
									`u1_cdb2`.`id` `school_id`,
									`u1_kt`.`firstname` `firstname`,
									`u1_kt`.`lastname` `lastname`,
									1 `lessons`,
									0 `lessons_holiday`,
									60 `lession_durration`,
									COALESCE(`u1_kts`.`salary`, 0) `amount_holiday`,
									COALESCE(`u1_kts`.`salary`, 0) `amount`,
									'' `days`,
									'' `days_holiday`,
									'' `classname`,
									'' `substitute_days`,
									''  `lessons_of_day`,
									'' `lessons_of_day_holiday`,
									-1 `costcategory_id`,
									`u1_kts`.`id` `salary`,
									`u1_kts`.`salary_period` `salary_period`,
									`u1_kts`.`lessons_period` `salary_lessons_period`,
									`u1_kts`.`lessons` `salary_lessons`,
									'' `course_list`,
									IF(
										`u1_kts`.`valid_until` <= 0 ,
										0,
										DATEDIFF(
											`u1_kts`.`valid_until`,
											IF(
												`u1_kts`.`valid_from` < `week_table`.`week`,
												`week_table`.`week`,
												`u1_kts`.`valid_from`
											)
										)
									) `days_subtract`,
									0 `substitute_teacher`,
									0 `block_id`,
									'' `block_list`,
									`u1_kt`.`account_holder` `bank_account_holder`,
									`u1_kt`.`account_number` `bank_account_number`,
									`u1_kt`.`adress_of_bank` `bank_code`,
									`u1_kt`.`name_of_bank` `bank_name`,
									'' `building_name`,
									`u1_cdb2`.`teacher_payment_type` `lesson_school_option`,
									NULL AS `teacher_absence`,
									NULL AS `count_bookings`
								FROM
									`ts_teachers` `u1_kt` INNER JOIN
									`ts_teachers_to_schools` `u1_tts` ON
										`u1_kt`.`id` = `u1_tts`.`teacher_id` INNER JOIN
									#temp_week_table `week_table` ON
										`week_table`.`week` BETWEEN
											:from AND
											:until LEFT JOIN
									`kolumbus_teacher_salary` `u1_kts` ON
										`u1_kts`.`id` = (
											SELECT
												`id`
											FROM
												`kolumbus_teacher_salary`
											WHERE
												(
													:from < IF(
														`valid_until` <= 0,
											( NOW() + INTERVAL 10 YEAR ),
														`valid_until`
										) AND
													:until > `valid_from`
												) AND
												`school_id` = :school_id AND
												`teacher_id` = `u1_kt`.`id` AND
												`active` = 1
											ORDER BY
												`valid_until` DESC
											LIMIT
												1
										) INNER JOIN
									`customer_db_2` `u1_cdb2` ON
										`u1_cdb2`.`id` = :school_id
								WHERE
									`u1_kts`.`costcategory_id` = -1 AND
									`u1_kts`.`salary_period` = 'week' AND
									`u1_kts`.`active` = 1 AND
									`u1_kt`.`active` = 1 AND
									`u1_tts`.`school_id` = :school_id
							)

								UNION ALL

							/* FESTGEHALT pro MONAT */

							(

								SELECT
									2 `calculation`,
									'month' `select_type`,
									MONTH(`month_table`.`month`) `select_value`,
									`month_table`.`month` `timepoint`,
									'' `select_group`, /* CONCAT(MONTH(`month_table`.`month`), '_', `u2_kt`.`id`) `select_group`, */
									`u2_kt`.`id` `teacher_id`,
									`u2_tts`.`school_id` `idSchool`,
									`u2_cdb2`.`currency_teacher` `currency_id`,
									`u2_cdb2`.`id` `school_id`,
									`u2_kt`.`firstname` `firstname`,
									`u2_kt`.`lastname` `lastname`,
									1 `lessons`,
									0 `lessons_holiday`,
									60 `lession_durration`,
									COALESCE(`u2_kts`.`salary`, 0) `amount_holiday`,
									COALESCE(`u2_kts`.`salary`, 0) `amount`,
									'' `days`,
									'' `days_holiday`,
									'' `classname`,
									'' `substitute_days`,
									''  `lessons_of_day`,
									'' `lessons_of_day_holiday`,
									-1 `costcategory_id`,
									`u2_kts`.`id` `salary`,
									`u2_kts`.`salary_period` `salary_period`,
									`u2_kts`.`lessons_period` `salary_lessons_period`,
									`u2_kts`.`lessons` `salary_lessons`,
									'' `course_list`,
									IF(
										`u2_kts`.`valid_until` <= 0 ,
										0,
										DATEDIFF(
											`u2_kts`.`valid_until`,
											IF(
												`u2_kts`.`valid_from` < `month_table`.`month`,
												`month_table`.`month`,
												`u2_kts`.`valid_from`
											)
										)
									) `days_subtract`,
									0 `substitute_teacher`,
									0 `block_id`,
									'' `block_list`,
									`u2_kt`.`account_holder` `bank_account_holder`,
									`u2_kt`.`account_number` `bank_account_number`,
									`u2_kt`.`adress_of_bank` `bank_code`,
									`u2_kt`.`name_of_bank` `bank_name`,
									'' `building_name`,
									`u2_cdb2`.`teacher_payment_type` `lesson_school_option`,
									NULL AS `teacher_absence`,
									NULL AS `count_bookings`
								FROM
									`ts_teachers` `u2_kt` INNER JOIN
									`ts_teachers_to_schools` `u2_tts` ON
										`u2_kt`.`id` = `u2_tts`.`teacher_id` INNER JOIN
									#temp_month_table `month_table` ON
										`month_table`.`month` BETWEEN
											:from AND
											:until  INNER JOIN
									`kolumbus_teacher_salary` `u2_kts` ON
										`u2_kts`.`teacher_id` = `u2_kt`.`id` AND
										`u2_kts`.`school_id` = :school_id LEFT JOIN
									`customer_db_2` `u2_cdb2` ON
										`u2_cdb2`.`id` = :school_id
								WHERE
									`u2_kts`.`active` = 1 AND
									`u2_kts`.`costcategory_id` = -1 AND
									`u2_kts`.`salary_period` = 'month' AND
									`u2_kt`.`active` = 1 AND
									(
										`month_table`.`month` BETWEEN
										/* immer auf den 1ten des Monats ändern da auch vertäge die mittendrin wechseln aufgeführt werden müssen */
										DATE(
											CONCAT(
												YEAR(`u2_kts`.`valid_from`),
												'-',
												MONTH(`u2_kts`.`valid_from`),
												'-01'
							)
										) AND
										IF(
											`u2_kts`.`valid_until` <= 0,
											( NOW() + INTERVAL 10 YEAR ),
											`u2_kts`.`valid_until`
										)
									) AND
									`u2_tts`.`school_id` = :school_id
							)

							UNION ALL

							/* Gehalt JE LEKTION PRO WOCHE */

							(

								SELECT
									3 `calculation`,
									'week' `select_type`,
									WEEK(`u3_ktb`.`week`,3) `select_value`,
									DATE(`u3_ktb`.`week`) `timepoint`,
									`u3_ktb`.`id` `select_group`,
									`u3_kt`.`id` `teacher_id`,
									`u3_tts`.`school_id` `idSchool`,
									`u3_cdb2`.`currency_teacher` `currency_id`,
									`u3_cdb2`.`id` `school_id`,
									`u3_kt`.`firstname` `firstname`,
									`u3_kt`.`lastname` `lastname`,
									(
										/*
											Alle Lektionen, die nicht in Ferien und an Wochenenden sind
										*/ 
										(
											(
												SELECT
													SUM(`lessons`)
												FROM
													`kolumbus_tuition_templates`
												WHERE
													`id` = `u3_ktb`.`template_id`
											) * COUNT(DISTINCT `u3_ktbd`.`day`)
										)
										-
										(
											IF(
												/* Wenn Lehrer abwesend ist, darf Vertretungslehrer nicht geprüft werden */
												`u3_kab`.`id` IS NOT NULL,
												COALESCE(
													(
														SELECT
															SUM(`lessons`)
														FROM
															`kolumbus_tuition_templates`
														WHERE
															`id` = `u3_ktb`.`template_id`
													) * COUNT(DISTINCT `u3_kab`.`id`)
													, 0
												),
												COALESCE(
													(
														SELECT
															SUM(`lessons`)
														FROM
															`kolumbus_tuition_blocks_substitute_teachers`
														WHERE
															`block_id` = `u3_ktb`.`id` AND
															`active` = 1 AND
															`day` NOT IN (
																SELECT
																	`ktbd_h`.`day`
																FROM
																	`kolumbus_tuition_blocks_days` AS `ktbd_h`
																WHERE
																	`ktbd_h`.`block_id` = `u3_ktb`.`id` AND
																	(
																		COALESCE(
																			(
																				SELECT 
																					COUNT(*)
																				FROM 
																					`kolumbus_holidays_public` `khp` INNER JOIN 
																					`kolumbus_holidays_public_schools` `khps` ON
																						`khps`.`holiday_id` = `khp`.`id`
																				WHERE (
																					(
																						`khp`.`annual` = 1 AND
																						DAY(`khp`.`date`) = DAY(DATE(`u3_ktb`.`week` + INTERVAL (`ktbd_h`.`day` - 1) DAY)) AND
																						MONTH(`khp`.`date`) = MONTH(DATE(`u3_ktb`.`week` + INTERVAL (`ktbd_h`.`day` - 1) DAY))
																					) OR (
																						`khp`.`annual` = 0 AND
																						`khp`.`date` = DATE(`u3_ktb`.`week` + INTERVAL (`ktbd_h`.`day` - 1) DAY)
																					)
																				) AND
																				`khp`.`active` = 1 AND
																				`khps`.`school_id` = :school_id
																			), 0
																		) > 0 OR (
																			IFNULL(`u3_kckt`.`account_as_holiday`, 0) AND
																			`ktbd_h`.`day` IN (6, 7)
																		)
																	)
															)
													)
													, 0
												)
											)
										)
									) `lessons`,
									(
										/*
											Alle Lektionen in Ferien und an Wochenenden
										*/ 
										(
											(
												SELECT
													SUM(`lessons`)
												FROM
													`kolumbus_tuition_templates`
												WHERE
													`id` = `u3_ktb`.`template_id`
											) * COUNT(DISTINCT `u3_ktbd_holiday`.`day`)
										)
										-
										/*
											Abwesenheiten und Vertretungen subtrahieren
										*/ 
										(
											IF(
												/* Wenn Lehrer abwesend ist, darf Vertretungslehrer nicht geprüft werden */
												`u3_kab`.`id` IS NOT NULL,
												COALESCE(
													(
														SELECT
															SUM(`lessons`)
														FROM
															`kolumbus_tuition_templates`
														WHERE
															`id` = `u3_ktb`.`template_id`
													) * COUNT(DISTINCT `u3_kab`.`id`)
													, 0
												),
												COALESCE(
													(
	
														SELECT
															SUM(`lessons`)
														FROM
															`kolumbus_tuition_blocks_substitute_teachers`
														WHERE
															`block_id` = `u3_ktb`.`id` AND
															`active` = 1 AND
															`day` NOT IN (
																SELECT
																	`ktbd_h`.`day`
																FROM
																	`kolumbus_tuition_blocks_days` AS `ktbd_h`
																WHERE
																	`ktbd_h`.`block_id` = `u3_ktb`.`id` AND
																	(
																		COALESCE(
																			(
																				SELECT 
																					COUNT(*)
																				FROM 
																					`kolumbus_holidays_public` `khp` INNER JOIN
																					`kolumbus_holidays_public_schools` `khps` ON
																						`khps`.`holiday_id` = `khp`.`id`
																				WHERE (
																					(
																						`khp`.`annual` = 1 AND
																						DAY(`khp`.`date`) = DAY(DATE(`u3_ktb`.`week` + INTERVAL (`ktbd_h`.`day` - 1) DAY)) AND
																						MONTH(`khp`.`date`) = MONTH(DATE(`u3_ktb`.`week` + INTERVAL (`ktbd_h`.`day` - 1) DAY))
																					) OR (
																						`khp`.`annual` = 0 AND
																						`khp`.`date` = DATE(`u3_ktb`.`week` + INTERVAL (`ktbd_h`.`day` - 1) DAY)
																					)
																				) AND 
																				`khp`.`active` = 1 AND
																				`khps`.`school_id` = :school_id
																			), 0
																		) <= 0 AND
																		!(
																			IFNULL(`u3_kckt`.`account_as_holiday`, 0) AND
																			`ktbd_h`.`day` IN (6, 7)
																		)
																	)
															)
													)
													, 0
												)
											)
										)
									) `lessons_holiday`,
									`u3_ktcl`.`lesson_duration` `lession_durration`,
									`u3_kct`.`amount_holiday` `amount_holiday`,
									`u3_kct`.`amount` `amount`,
									GROUP_CONCAT(DISTINCT `u3_ktbd`.`day`) `days`,
									GROUP_CONCAT(DISTINCT `u3_ktbd_holiday`.`day`) `days_holiday`,
									`u3_ktcl`.`name` `classname`,
									(
										
										SELECT
											GROUP_CONCAT(CONCAT(`day`,'_',`lessons`))
										FROM
											`kolumbus_tuition_blocks_substitute_teachers`
										WHERE
											`block_id` = `u3_ktb`.`id` AND
											`active` = 1

									) `substitute_days`,
									GROUP_CONCAT(
										CONCAT(
											`u3_ktbd`.`day`,
											'_',
											`u3_ktt`.`lessons`
										)
									) `lessons_of_day`,
									GROUP_CONCAT(
										CONCAT(
											`u3_ktbd_holiday`.`day`,
											'_',
											`u3_ktt`.`lessons`
										)
									) `lessons_of_day_holiday`,
									`u3_kts`.`costcategory_id`,
									`u3_kts`.`id` `salary`,
									`u3_kts`.`salary_period` `salary_period`,
									`u3_kts`.`lessons_period` `salary_lessons_period`,
									`u3_kts`.`lessons` `salary_lessons`,
									GROUP_CONCAT(DISTINCT `u3_ktbic`.`course_id`) `course_list`,
									0 `days_subtract`,
									0 `substitute_teacher`,
									`u3_ktb`.`id` `block_id`,
									`u3_ktb`.`id` `block_list`,
									`u3_kt`.`account_holder` `bank_account_holder`,
									`u3_kt`.`account_number` `bank_account_number`,
									`u3_kt`.`adress_of_bank` `bank_code`,
									`u3_kt`.`name_of_bank` `bank_name`,
									`u3_ksb`.`title` `building_name`,
									`u3_cdb2`.`teacher_payment_type` `lesson_school_option`,
									GROUP_CONCAT(
										DISTINCT IF(
											`u3_kab`.`id` IS NOT NULL,
											`u3_ktbd`.`day`,
											''
										)
									)`teacher_absence`,
									COUNT(
										DISTINCT CONCAT(
											`u3_ktb`.`id`,
											'_',
											`u3_ki`.`id`
										)
									) `count_bookings`
								FROM
									`ts_teachers` `u3_kt` INNER JOIN
									`ts_teachers_to_schools` `u3_tts` ON
										`u3_kt`.`id` = `u3_tts`.`teacher_id` INNER JOIN
									`kolumbus_tuition_blocks` `u3_ktb` ON
										`u3_ktb`.`teacher_id` = `u3_kt`.`id` LEFT JOIN
									`kolumbus_tuition_classes` `u3_ktcl` ON
										`u3_ktcl`.`id` = `u3_ktb`.`class_id` LEFT JOIN
									`kolumbus_teacher_salary` `u3_kts` ON
										`u3_kts`.`school_id` = :school_id AND
										`u3_kts`.`teacher_id` = `u3_kt`.`id` AND
										`u3_kts`.`active` = 1 AND
										`u3_ktb`.`week` <
										IF(
											`u3_kts`.`valid_until` <= 0,
											( NOW() + INTERVAL 10 YEAR ),
											`u3_kts`.`valid_until`
										) AND
										(
											`u3_ktb`.`week` +
											INTERVAL 1 WEEK -
											INTERVAL 1 SECOND
										) >
										`u3_kts`.`valid_from` LEFT JOIN
									`kolumbus_costs_kategorie_teacher` `u3_kckt` ON
										`u3_kckt`.`id` = `u3_kts`.`costcategory_id` AND
										`u3_kts`.`costcategory_id` != -1 LEFT JOIN
									`kolumbus_tuition_blocks_days` `u3_ktbd` ON
										`u3_ktbd`.`block_id` = `u3_ktb`.`id` AND
										COALESCE(
											(
												SELECT
													COUNT(*)
												FROM
													`kolumbus_holidays_public` `khp` INNER JOIN
													`kolumbus_holidays_public_schools` `khps` ON
														`khps`.`holiday_id` = `khp`.`id`
												WHERE
														(
														(
															`khp`.`annual` = 1 AND
															DAY(`khp`.`date`) = DAY(DATE(`u3_ktb`.`week` + INTERVAL (`u3_ktbd`.`day` - 1) DAY)) AND
															MONTH(`khp`.`date`) = MONTH(DATE(`u3_ktb`.`week` + INTERVAL (`u3_ktbd`.`day` - 1) DAY))
														) OR
														(
															`khp`.`annual` = 0 AND
															`khp`.`date` = DATE(`u3_ktb`.`week` + INTERVAL (`u3_ktbd`.`day` - 1) DAY)
														)
													) AND
													`khp`.`active` = 1 AND
													`khps`.`school_id` = :school_id
											), 0
										)  <= 0 
										AND
											!(
												IFNULL(`u3_kckt`.`account_as_holiday`, 0) AND
												`u3_ktbd`.`day` IN (6,7)
											)
										LEFT JOIN
									`kolumbus_tuition_blocks_days` `u3_ktbd_holiday` ON
										`u3_ktbd_holiday`.`block_id` = `u3_ktb`.`id` AND
										(
											COALESCE(
												(
													SELECT
														COUNT(*)
													FROM
														`kolumbus_holidays_public` `khp` INNER JOIN
														`kolumbus_holidays_public_schools` `khps` ON
															`khps`.`holiday_id` = `khp`.`id`
													WHERE
														(
															(
																`khp`.`annual` = 1 AND
																DAY(`khp`.`date`) = DAY(DATE(`u3_ktb`.`week` + INTERVAL (`u3_ktbd_holiday`.`day` - 1) DAY)) AND
																MONTH(`khp`.`date`) = MONTH(DATE(`u3_ktb`.`week` + INTERVAL (`u3_ktbd_holiday`.`day` - 1) DAY))
															) OR
															(
																`khp`.`annual` = 0 AND
																`khp`.`date` = DATE(`u3_ktb`.`week` + INTERVAL (`u3_ktbd_holiday`.`day` - 1) DAY)
															)
														) AND
														`khp`.`active` = 1 AND
														`khps`.`school_id` = :school_id
												), 0
											)  > 0
											OR
											(
												IFNULL(`u3_kckt`.`account_as_holiday`, 0) AND
												`u3_ktbd_holiday`.`day` IN (6,7)
											)
										) INNER JOIN
									`kolumbus_tuition_blocks_inquiries_courses` `u3_ktbic` ON
										`u3_ktbic`.`block_id` = `u3_ktb`.`id` AND
										`u3_ktbic`.`active` = 1 LEFT JOIN
									`kolumbus_classroom` `u3_kc` ON
										`u3_kc`.`id` = `u3_ktbic`.`room_id` LEFT JOIN
									`kolumbus_school_floors` `u3_ksf` ON
										`u3_ksf`.`id` = `u3_kc`.`floor_id` LEFT JOIN
									`kolumbus_school_buildings` `u3_ksb` ON
										`u3_ksb`.`id` = `u3_ksf`.`building_id` LEFT JOIN
									`kolumbus_tuition_courses` `u3_ktc` ON
										`u3_ktc`.`id` = `u3_ktbic`.`course_id` LEFT JOIN
									`ts_inquiries_journeys_courses` `u3_kic` ON
										`u3_kic`.`id` = `u3_ktbic`.`inquiry_course_id` INNER JOIN
									`ts_inquiries_journeys` `u3_ts_i_j` ON
										`u3_ts_i_j`.`id` = `u3_kic`.`journey_id` AND
										`u3_ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
										`u3_ts_i_j`.`active` = 1 INNER JOIN
									`ts_inquiries` `u3_ki` ON
										`u3_ki`.`id` = `u3_ts_i_j`.`inquiry_id` AND
										`u3_ki`.`active` = 1 
										".Ext_Thebing_System::getWhereFilterStudentsByClientConfig('u3_ki')." LEFT JOIN
									`kolumbus_tuition_templates` `u3_ktt` ON
										`u3_ktt`.`id` = `u3_ktb`.`template_id` LEFT JOIN
									`ts_tuition_blocks_daily_units` `u3_tbdu` ON
										`u3_tbdu`.`block_id` = `u3_ktbd`.`block_id` AND
										`u3_tbdu`.`day` = `u3_ktbd`.`day` LEFT JOIN
									`customer_db_2` `u3_cdb2` ON
										`u3_cdb2`.`id` = :school_id LEFT JOIN
									`kolumbus_periods` `u3_kp` ON
										`u3_kp`.`id` =
										(
											SELECT
												`id`
											FROM
												`kolumbus_periods`
											WHERE
												(
													`u3_ktb`.`week` BETWEEN
														`valid_from` AND
														`valid_until`
												) AND
												`active` = 1 AND
												`saison_for_teachercost` = 1 AND
												`idPartnerschool` = :school_id
											ORDER BY
												DATEDIFF(
													`valid_until`,
													`valid_from`
												) ASC
											LIMIT 1
										) LEFT JOIN
									`kolumbus_costprice_teacher` `u3_kct` ON
										`u3_kct`.`costkategorie_id` = `u3_kts`.`costcategory_id` AND
										`u3_kct`.`school_id` = :school_id AND
										`u3_kct`.`currency_id` = `u3_cdb2`.`currency_teacher` AND
										`u3_kct`.`course_id` = `u3_ktbic`.`course_id` AND
										`u3_kct`.`saison_id` = `u3_kp`.`id` LEFT JOIN
									`kolumbus_absence` `u3_kab` ON
										`u3_kab`.`item_id` = `u3_kt`.`id` AND
										`u3_kab`.`item` = 'teacher' AND
										`u3_kab`.`active` = 1 AND
										DATE(
											`u3_ktb`.`week` + INTERVAL (`u3_ktbd`.`day` - 1) DAY
										) BETWEEN `u3_kab`.`from` AND `u3_kab`.`until`
								WHERE
									(
										`u3_kts`.`id` IS NULL OR (
											`u3_kts`.`costcategory_id` != -1 AND
											`u3_kckt`.`grouping` = 'week'
										)
									) AND (
										DATE(`u3_ktb`.`week`) BETWEEN
											:from AND
											:until
									) AND
									`u3_kt`.`active` = 1 AND
									`u3_ktb`.`active` = 1 AND
									`u3_kic`.`active` = 1 AND
									`u3_tts`.`school_id` = :school_id AND
									`u3_ktb`.`school_id` = :school_id AND
									(
										`u3_tbdu`.`id` IS NULL OR
										`u3_tbdu`.`state` != " . \TsTuition\Entity\Block\Unit::STATE_CANCELLED . "
									)
								GROUP BY 
									`u3_kt`.`id`,
									`u3_ktb`.`id`,
									WEEK(`u3_ktb`.`week`)
								HAVING
									`lessons` > 0 OR
									`lessons_holiday` > 0
							) UNION ALL

							/* Gehalt JE LEKTION PRO MONAT */

							(
							
								SELECT
									5 `calculation`,
									'week' `select_type`, /* Muss auf Woche stehen, damit der ganze Format-Kram funktioniert */
									MONTH(`month_table`.`month`) `select_value`,
									`month_table`.`month` `timepoint`,
									'' `select_group`,
									`u5_kt`.`id` `teacher_id`,
									`u5_tts`.`school_id` `idSchool`,
									`u5_cdb2`.`currency_teacher` `currency_id`,
									`u5_cdb2`.`id` `school_id`,
									`u5_kt`.`firstname` `firstname`,
									`u5_kt`.`lastname` `lastname`,
									(
										".$this->getPerLessonMonthlyGroupedQueryPart()."
									) `lessons`,
									(
										".$this->getPerLessonMonthlyGroupedQueryPart(true)."
									) `lessons_holiday`,
									`u5_ktcl`.`lesson_duration` `lession_durration`,
									`u5_kct`.`amount_holiday` `amount_holiday`,
									`u5_kct`.`amount` `amount`,
									'' `days`,
									'' `days_holiday`,
									GROUP_CONCAT(DISTINCT `u5_ktcl`.`name`) `classname`,
									'' `substitute_days`,
									'' `lessons_of_day`,
									'' `lessons_of_day_holiday`,
									`u5_kts`.`costcategory_id`,
									`u5_kts`.`id` `salary`,
									`u5_kts`.`salary_period` `salary_period`,
									`u5_kts`.`lessons_period` `salary_lessons_period`,
									`u5_kts`.`lessons` `salary_lessons`,
									GROUP_CONCAT(DISTINCT `u5_ktbic`.`course_id`) `course_list`,
									0 `days_subtract`,
									0 `substitute_teacher`,
									0 `block_id`,
									GROUP_CONCAT(DISTINCT `u5_ktb`.`id`) `block_list`,
									`u5_kt`.`account_holder` `bank_account_holder`,
									`u5_kt`.`account_number` `bank_account_number`,
									`u5_kt`.`adress_of_bank` `bank_code`,
									`u5_kt`.`name_of_bank` `bank_name`,
									`u5_ksb`.`title` `building_name`,
									`u5_cdb2`.`teacher_payment_type` `lesson_school_option`,
									''`teacher_absence`,
									COUNT(DISTINCT `u5_ki`.`id`) `count_bookings`
								FROM
									`ts_teachers` `u5_kt` INNER JOIN
									`ts_teachers_to_schools` `u5_tts` ON
										`u5_kt`.`id` = `u5_tts`.`teacher_id` INNER JOIN
									#temp_month_table_2 `month_table` ON
										`month_table`.`month` BETWEEN
										:from AND
										:until  INNER JOIN
									`kolumbus_tuition_blocks` `u5_ktb` ON
										`u5_ktb`.`teacher_id` = `u5_kt`.`id` LEFT JOIN
									`kolumbus_tuition_classes` `u5_ktcl` ON
										`u5_ktcl`.`id` = `u5_ktb`.`class_id` INNER JOIN
									`kolumbus_tuition_blocks_inquiries_courses` `u5_ktbic` ON
										`u5_ktbic`.`block_id` = `u5_ktb`.`id` AND
										`u5_ktbic`.`active` = 1 LEFT JOIN
									`kolumbus_classroom` `u5_kc` ON
										`u5_kc`.`id` = `u5_ktbic`.`room_id` LEFT JOIN
									`kolumbus_school_floors` `u5_ksf` ON
										`u5_ksf`.`id` = `u5_kc`.`floor_id` LEFT JOIN
									`kolumbus_school_buildings` `u5_ksb` ON
										`u5_ksb`.`id` = `u5_ksf`.`building_id` LEFT JOIN
									`kolumbus_tuition_courses` `u5_ktc` ON
										`u5_ktc`.`id` = `u5_ktbic`.`course_id` LEFT JOIN
									`ts_inquiries_journeys_courses` `u5_kic` ON
										`u5_kic`.`id` = `u5_ktbic`.`inquiry_course_id` INNER JOIN
									`ts_inquiries_journeys` `u5_ts_i_j` ON
										`u5_ts_i_j`.`id` = `u5_kic`.`journey_id` AND
										`u5_ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
										`u5_ts_i_j`.`active` = 1 INNER JOIN
									`ts_inquiries` `u5_ki` ON
										`u5_ki`.`id` = `u5_ts_i_j`.`inquiry_id` AND
										`u5_ki`.`active` = 1 
										".Ext_Thebing_System::getWhereFilterStudentsByClientConfig('u5_ki')." LEFT JOIN
									`customer_db_2` `u5_cdb2` ON
										`u5_cdb2`.`id` = :school_id INNER JOIN
									/* Unten redundant */
									`kolumbus_teacher_salary` `u5_kts` ON
										`u5_kts`.`school_id` = :school_id AND
										`u5_kts`.`teacher_id` = `u5_kt`.`id` AND
										`u5_kts`.`active` = 1 AND
										`u5_ktb`.`week` <
										IF(
											`u5_kts`.`valid_until` <= 0,
											( NOW() + INTERVAL 10 YEAR ),
											`u5_kts`.`valid_until`
										) AND
										(
											`u5_ktb`.`week` +
											INTERVAL 1 WEEK -
											INTERVAL 1 SECOND
										) >
										`u5_kts`.`valid_from` INNER JOIN
									`kolumbus_costs_kategorie_teacher` `u5_kckt` ON
										`u5_kckt`.`id` = `u5_kts`.`costcategory_id` AND
										`u5_kts`.`costcategory_id` != -1 LEFT JOIN
									`kolumbus_periods` `u5_kp` ON
										`u5_kp`.`id` =
										(
											SELECT
												`id`
											FROM
												`kolumbus_periods`
											WHERE
												(
													`u5_ktb`.`week` BETWEEN
														`valid_from` AND
														`valid_until`
												) AND
												`active` = 1 AND
												`saison_for_teachercost` = 1 AND
												`idPartnerschool` = :school_id
											ORDER BY
												DATEDIFF(
													`valid_until`,
													`valid_from`
												) ASC
											LIMIT 1
										) LEFT JOIN
									`kolumbus_costprice_teacher` `u5_kct` ON
										`u5_kct`.`costkategorie_id` = `u5_kts`.`costcategory_id` AND
										`u5_kct`.`school_id` = :school_id AND
										`u5_kct`.`currency_id` = `u5_cdb2`.`currency_teacher` AND
										`u5_kct`.`course_id` = `u5_ktbic`.`course_id` AND
										`u5_kct`.`saison_id` = `u5_kp`.`id`
								WHERE
									`u5_kckt`.`id` IS NOT NULL AND
									`u5_kckt`.`grouping` = 'month' AND (
										DATE(`u5_ktb`.`week`) BETWEEN
											`month_table`.`month` - INTERVAL 7 DAY AND
											LAST_DAY(`month_table`.`month`) + INTERVAL 7 DAY
									) AND
									`u5_kt`.`active` = 1 AND
									`u5_ktb`.`active` = 1 AND
									`u5_kic`.`active` = 1 AND
									`u5_ktb`.`school_id` = :school_id AND
									`u5_tts`.`school_id` = :school_id
								GROUP BY 
									`u5_kt`.`id`,
									`month_table`.`month`,
									`u5_kts`.`id`
								HAVING
									`lessons` > 0 OR
									`lessons_holiday` > 0
							
							) UNION ALL

							/* Vertretungslehrer je Lektion pro Woche */

							(

								SELECT
									4 `calculation`,
									'week' `select_type`,
									WEEK(`u4_ktb`.`week`) `select_value`,
									DATE(`u4_ktb`.`week`) `timepoint`,
									CONCAT(`u4_ktb`.`id`, '_', `u4_ktbst`.`day`) `select_group`,
									`u4_kt`.`id` `teacher_id`,
									`u4_tts`.`school_id` `idSchool`,
									`u4_cdb2`.`currency_teacher` `currency_id`,
									`u4_cdb2`.`id` `school_id`,
									`u4_kt`.`firstname` `firstname`,
									`u4_kt`.`lastname` `lastname`,
									`u4_ktbst`.`lessons`,
									0 `lessons_holiday`,
									`u4_ktcl`.`lesson_duration` `lession_durration`,
									`u4_kct`.`amount_holiday` `amount_holiday`,
									`u4_kct`.`amount` `amount`,
									`u4_ktbst`.`day` `days`,
									'' `days_holiday`,
									`u4_ktcl`.`name` `classname`,
									'' `substitute_days`,
									'' `lessons_of_day`,
									'' `lessons_of_day_holiday`,
									`u4_kts`.`costcategory_id`,
									`u4_kts`.`id` `salary`,
									`u4_kts`.`salary_period` `salary_period`,
									`u4_kts`.`lessons_period` `salary_lessons_period`,
									`u4_kts`.`lessons` `salary_lessons`,
									GROUP_CONCAT(DISTINCT `u4_kic`.`course_id`) `course_list`,
									0 `days_subtract`,
									1 `substitute_teacher`,
									`u4_ktb`.`id` `block_id`,
									`u4_ktb`.`id` `block_list`,
									`u4_kt`.`account_holder` `bank_account_holder`,
									`u4_kt`.`account_number` `bank_account_number`,
									`u4_kt`.`adress_of_bank` `bank_code`,
									`u4_kt`.`name_of_bank` `bank_name`,
									`u4_ksb`.`title` `building_name`,
									`u4_cdb2`.`teacher_payment_type` `lesson_school_option`,
									NULL AS `teacher_absence`,
									COUNT(
										DISTINCT CONCAT(
											`u4_ktb`.`id`,
											'_',
											`u4_ki`.`id`
										)
									) `count_bookings`
								FROM
									`kolumbus_tuition_blocks` `u4_ktb` INNER JOIN
									`kolumbus_tuition_blocks_substitute_teachers` `u4_ktbst` ON
										`u4_ktb`.`id` = `u4_ktbst`.`block_id` LEFT JOIN
									`ts_teachers` `u4_kt` ON
										`u4_kt`.`id` = `u4_ktbst`.`teacher_id` INNER JOIN
									`ts_teachers_to_schools` `u4_tts` ON
										`u4_kt`.`id` = `u4_tts`.`teacher_id` LEFT JOIN
									`kolumbus_classroom` `u4_kc` ON
										`u4_kc`.`id` = `u4_ktb`.`class_id` LEFT JOIN
									`kolumbus_school_floors` `u4_ksf` ON
										`u4_ksf`.`id` = `u4_kc`.`floor_id` LEFT JOIN
									`kolumbus_school_buildings` `u4_ksb` ON
										`u4_ksb`.`id` = `u4_ksf`.`building_id` LEFT JOIN
									`kolumbus_tuition_classes` `u4_ktcl` ON
										`u4_ktcl`.`id` = `u4_ktb`.`class_id` INNER JOIN
									`kolumbus_tuition_blocks_inquiries_courses` `u4_ktbic` ON
										`u4_ktbic`.`block_id` = `u4_ktb`.`id` AND
										`u4_ktbic`.`active` = 1 LEFT JOIN
									`ts_inquiries_journeys_courses` `u4_kic` ON
										`u4_kic`.`id` = `u4_ktbic`.`inquiry_course_id` INNER JOIN
									`ts_inquiries_journeys` `u4_ts_i_j` ON
										`u4_ts_i_j`.`id` = `u4_kic`.`journey_id` AND
										`u4_ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
										`u4_ts_i_j`.`active` = 1 INNER JOIN
									`ts_inquiries` `u4_ki` ON
										`u4_ki`.`id` = `u4_ts_i_j`.`inquiry_id` AND
										`u4_ki`.`active` = 1 
										".Ext_Thebing_System::getWhereFilterStudentsByClientConfig('u4_ki')." LEFT JOIN
									`customer_db_2` `u4_cdb2` ON
										`u4_cdb2`.`id` = :school_id LEFT JOIN
									`kolumbus_teacher_salary` `u4_kts` ON
										`u4_kts`.`school_id` = :school_id AND
										`u4_kts`.`teacher_id` = `u4_kt`.`id` AND
										`u4_kts`.`active` = 1 AND
										`u4_ktb`.`week` <
										IF(
											`u4_kts`.`valid_until` <= 0,
											( NOW() + INTERVAL 10 YEAR ),
											`u4_kts`.`valid_until`
										) AND
										(
											`u4_ktb`.`week` +
											INTERVAL 1 WEEK -
											INTERVAL 1 SECOND
										) >
										`u4_kts`.`valid_from` LEFT JOIN
									`kolumbus_periods` `u4_kp` ON
										`u4_kp`.`id` =
										(
											SELECT
												`id`
											FROM
												`kolumbus_periods`
											WHERE
												(
													`u4_ktb`.`week` BETWEEN
														`valid_from` AND
														`valid_until`
												) AND
												`active` = 1 AND
												`saison_for_teachercost` = 1 AND
												`idPartnerschool` = :school_id
											ORDER BY
												DATEDIFF(
													`valid_from`,
													`valid_until`
												) ASC
											LIMIT 1
										) LEFT JOIN
									`kolumbus_costprice_teacher` `u4_kct` ON
										`u4_kct`.`costkategorie_id` = `u4_kts`.`costcategory_id` AND
										`u4_kct`.`school_id` = :school_id AND
										`u4_kct`.`currency_id` = `u4_cdb2`.`currency_teacher` AND
										`u4_kct`.`course_id` = `u4_kic`.`course_id` AND
										`u4_kct`.`saison_id` = `u4_kp`.`id`
								WHERE
									(
										DATE(`u4_ktb`.`week`) BETWEEN
											:from AND
											:until
									) AND
									IF(
										`u4_kts`.`id` IS NULL,
										1 = 1  ,
										`u4_kts`.`costcategory_id` != -1
									) AND
									`u4_kt`.`active` = 1 AND
									`u4_ktb`.`active` = 1 AND
									`u4_kic`.`active` = 1 AND
									`u4_tts`.`school_id` = :school_id AND
									`u4_ktb`.`school_id` = :school_id
								GROUP BY
									`u4_kt`.`id`,
									`u4_ktb`.`id`,
									`u4_ktbst`.`day`

							)

						) `union` LEFT JOIN
						`ts_teachers_payments` `ktep` ON
							`ktep`.`teacher_id` = `union`.`teacher_id` AND
							`ktep`.`block_id` = `union`.`block_id` AND
							`ktep`.`active` = 1 AND
							IF(
								`ktep`.`payment_type` = 'month',
									(
										DATE(`ktep`.`timepoint`) BETWEEN
											DATE(`union`.`timepoint`) AND
											DATE(
												(
													`union`.`timepoint` +
													INTERVAL 1 MONTH
												) -
												INTERVAL 1 SECOND
											)
									),
									`ktep`.`timepoint` = DATE(`union`.`timepoint`)
							) AND
							`ktep`.`parent_id` = 0 LEFT JOIN
						`ts_teachers_payments` `ktep_additional` ON
							`ktep_additional`.`teacher_id` = `union`.`teacher_id` AND
							`ktep_additional`.`block_id` = `union`.`block_id` AND
							`ktep_additional`.`active` = 1 AND
							IF(
								`ktep_additional`.`payment_type` = 'month',
									(
										DATE(`ktep_additional`.`timepoint`) BETWEEN
											DATE(`union`.`timepoint`) AND
											DATE(
												(
													`union`.`timepoint` +
													INTERVAL 1 MONTH
												) -
												INTERVAL 1 SECOND
											)
									),
									`ktep_additional`.`timepoint` = DATE(`union`.`timepoint`)
							) AND
							`ktep_additional`.`parent_id` > 0
						WHERE
							`union`.`idSchool` = :school_id AND
							/* Einmal bezahlt, immer bezahlt #-10007 */
							`ktep`.`id` IS NULL
						GROUP BY 
							`teacher_id`,
							`timepoint`,
							`select_type`,
							`select_group`
			";

		return $aQueryData;

	}

	/**
	 * @param bool $bHolidayDays
	 * @return string
	 */
	private function getPerLessonMonthlyGroupedQueryPart($bHolidayDays = false) {

		$sHolidayPart = "";
		if(!$bHolidayDays) {
			$sHolidayPart = "NOT";
		}

		return "
		SELECT 
			SUM(`lessons`)
		FROM (
			SELECT
				`ktb_sub`.`teacher_id`,
				`month_table`.`month`,
				COALESCE(
					(
						`ktt_sub`.`lessons` *
						(
							COUNT(DISTINCT `ktbd_sub`.`day`) -
							COUNT(DISTINCT CONCAT_WS(`kab_sub`.`id`, `ktbd_sub`.`day`))
						)
					) -
					COALESCE(
						(
						  	SELECT
								COALESCE(SUM(`lessons`), 0)
							FROM
								`kolumbus_tuition_blocks_substitute_teachers` `ktbst` INNER JOIN
								/* Hier muss nochmal zurückgejoined werden, da das taggenau abgefragt werden muss */
								`kolumbus_tuition_blocks` `ktb` ON
									`ktb`.`id` = `ktbst`.`block_id` AND
									`ktb`.`id` = :school_id LEFT JOIN
								/* Hier muss nochmal die Abwesenheit abgefragt werden, sonst werden bei Abwesenheit zu viele Stunden abgezogen */
								`kolumbus_absence` `kab` ON
									`kab`.`active` = 1 AND
									`kab`.`item` = 'teacher' AND
									`kab`.`item_id` = `ktb`.`teacher_id` AND
									DATE(`ktb`.`week` + INTERVAL (`ktbst`.`day` - 1) DAY) BETWEEN `kab`.`from` AND `kab`.`until`
							WHERE
								`ktbst`.`active` = 1 AND
								`ktbst`.`block_id` = `ktb_sub`.`id` AND
								DATE(`ktb`.`week` + INTERVAL (`ktbst`.`day` - 1) DAY) = DATE(`ktb_sub`.`week` + INTERVAL (`ktbd_sub`.`day` - 1) DAY) AND
								`kab`.`id` IS NULL
						)
					, 0)
				, 0) `lessons`
			FROM
				`kolumbus_tuition_blocks` `ktb_sub` INNER JOIN
				`kolumbus_teacher_salary` `kts_sub` ON
				    /* Oben redundant */
					`kts_sub`.`school_id` = :school_id AND
				    `kts_sub`.`teacher_id` = `ktb_sub`.`teacher_id` AND
				    `kts_sub`.`active` = 1 AND
				    `ktb_sub`.`week` < IF(
						`kts_sub`.`valid_until` <= 0,
						( NOW() + INTERVAL 10 YEAR ),
						`kts_sub`.`valid_until`
					) AND (
						`ktb_sub`.`week` +
						INTERVAL 1 WEEK -
						INTERVAL 1 SECOND
					) > `kts_sub`.`valid_from` INNER JOIN
				`kolumbus_costs_kategorie_teacher` `kckt_sub` ON 
				    `kckt_sub`.`id` = `kts_sub`.`costcategory_id` JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic_sub` ON
					`ktbic_sub`.`block_id` = `ktb_sub`.`id` AND
					`ktbic_sub`.`active` = 1 JOIN
				`kolumbus_tuition_blocks_days` `ktbd_sub` ON
					`ktbd_sub`.`block_id` = `ktb_sub`.`id` AND
					{$sHolidayPart} EXISTS (
						SELECT
							*
						FROM
							`kolumbus_holidays_public` `khp` INNER JOIN
							`kolumbus_holidays_public_schools` `khps` ON
								`khps`.`holiday_id` = `khp`.`id`
						WHERE
							`khp`.`active` = 1 AND
							`khps`.`school_id` = :school_id AND (
								(
									`khp`.`annual` = 0 AND
									`khp`.`date` = DATE(`ktb_sub`.`week` + INTERVAL (`ktbd_sub`.`day` - 1) DAY)
								) OR (
									`khp`.`annual` = 1 AND
									DAY(`khp`.`date`) = DAY(DATE(`ktb_sub`.`week` + INTERVAL (`ktbd_sub`.`day` - 1) DAY)) AND
									MONTH(`khp`.`date`) = MONTH(DATE(`ktb_sub`.`week` + INTERVAL (`ktbd_sub`.`day` - 1) DAY))
								)
							) OR (
								IFNULL(`kckt_sub`.`account_as_holiday`, 0) AND
								`ktbd_sub`.`day` IN (6, 7)
							)
					) INNER JOIN
				`kolumbus_tuition_templates` `ktt_sub` ON
					`ktt_sub`.`id` = `ktb_sub`.`template_id` INNER JOIN
				#temp_month_table_".($bHolidayDays ? '3' : '4')." `month_table` ON
					`month_table`.`month` BETWEEN
						:from AND
						:until LEFT JOIN
				`kolumbus_absence` `kab_sub` ON
					`kab_sub`.`active` = 1 AND
					`kab_sub`.`item` = 'teacher' AND
					`kab_sub`.`item_id` = `ktb_sub`.`teacher_id` AND
					`ktb_sub`.`week` + INTERVAL (`ktbd_sub`.`day` - 1) DAY BETWEEN `kab_sub`.`from` AND `kab_sub`.`until` LEFT JOIN
				`ts_tuition_blocks_daily_units` `tbdu_sub` ON
					`tbdu_sub`.`block_id` = `ktbd_sub`.`block_id` AND
					`tbdu_sub`.`day` = `ktbd_sub`.`day`
			WHERE
				`ktb_sub`.`school_id` = :school_id AND
				`ktb_sub`.`active` = 1 AND
				`ktb_sub`.`week` BETWEEN
					`month_table`.`month` - INTERVAL 7 DAY AND
					LAST_DAY(`month_table`.`month`) + INTERVAL 7 DAY AND
				DATE(`ktb_sub`.`week` + INTERVAL (`ktbd_sub`.`day` - 1) DAY) BETWEEN
					`month_table`.`month` AND
				LAST_DAY(`month_table`.`month`) AND
				(
					`tbdu_sub`.`id` IS NULL OR
					`tbdu_sub`.`state` != " . \TsTuition\Entity\Block\Unit::STATE_CANCELLED . "
				)
			GROUP BY
				`ktb_sub`.`id`,
				`month_table`.`month`
			) `subsql_lessons`
		WHERE
			teacher_id = `u5_kt`.`id` AND
			`subsql_lessons`.`month` = `month_table`.`month`
		";

	}

}