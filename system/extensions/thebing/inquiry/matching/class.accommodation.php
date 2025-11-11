<?php

class Ext_Thebing_Inquiry_Matching_Accommodation extends Ext_TS_Inquiry_Journey_Accommodation {
 
	protected $_sTableAlias = 'kia';
	
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sLang = $oSchool->getInterfaceLanguage();
		$sWhereShowWithoutInvoice	= Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ts_i`');
	
		$aSqlParts['select'] .= " , `tc_c`.`lastname`										`lastname`,
									`tc_c`.`firstname`										`firstname`,
									`tc_c`.`gender`											`customer_gender`,
									`dl_mothertongue`.`name_".$sLang."`			`customer_mother_tongue`,
									`dc_nationality`.`nationality_".$sLang."`		`nationality`,
									`tc_c_n`.`number`										`customerNumber`,
									`ts_i`.`status_id`										`status_id`,
									`ts_i`.`id` `inquiry_id`,
									`ts_i`.`number` `booking_number`,
									`ts_i`.`currency_id`,
									(
										ROUND(`ts_i`.`amount`, 2) +
										ROUND(`ts_i`.`amount_initial`, 2) -
										ROUND(`ts_i`.`amount_payed_prior_to_arrival`, 2) -
										ROUND(`ts_i`.`amount_payed_at_school`, 2) -
										ROUND(`ts_i`.`amount_payed_refund`, 2)
									) `amount_open_original`,
									`kam`.#short											`meal`,
									`kar`.`name_".$sLang."`									`roomtype`,
									`kac`.#short											`category`,
									CONCAT(
										`kac`.#short, ' / ',
										`kar`.#short, ' / ',
										`kam`.#short								
									)														`accommodation`,
									`kia`.`from`											`from`,
									`kia`.`until`											`until`,
									
									`ts_i_m_d`.`acc_comment`								`acc_comment`,
									`ts_i_m_d`.`acc_comment2`,
									`ts_i_m_d`.`acc_allergies`								`acc_allergies`,
									`k_a_p`.`id`											`payment_id`,					## Zahlungs ID einer Zahlung
									`ka`.`ext_2`											`agency_short`,
                                    `k_inb`.`short`                                         `inbox_short`,
                                    `k_inb`.`name`											`inbox_name`,
									GROUP_CONCAT(DISTINCT `kc`.`name_".$sLang."` SEPARATOR ', ') `additional_services`,

									CONCAT(`kg`.`short`, IF(`ts_j_t_d_guide`.`value` = 1, ' *', ''))	`group_short`,
									CONCAT(`kg`.`name`, IF(`ts_j_t_d_guide`.`value` = 1, ' *', ''))		`group_name`,
									CONCAT(`kg`.`number`) `group_number`,

									GROUP_CONCAT(DISTINCT `kaa`.`id`) `allocated_ids`,

									/* Filter: Zugewiesen/nicht zugewiesen */
									CONCAT( 
											`kaa`.`room_id`,
											'|'
									)														`allocated_room_ids`,   ## zugewiesene Räume
                                            
                                     CONCAT( 
                                            `kaa2`.`room_id`,
											'|'
									)														`not_allocated_room_ids`,   ## NICHT zugewiesene Räume
									(
										SELECT
											GROUP_CONCAT(DISTINCT `ktc`.`name_short` ORDER BY `ktc`.`name_short` ASC SEPARATOR '{||}')
										FROM
											`ts_inquiries_journeys` `ts_ij_sub`
											LEFT JOIN `ts_inquiries_journeys_courses` `ts_ijc` ON
												`ts_ijc`.`journey_id` = `ts_ij_sub`.`id` LEFT JOIN
											`kolumbus_tuition_courses` `ktc` ON
												`ktc`.`id` = `ts_ijc`.`course_id`
										WHERE
											`ts_ij_sub`.`id` = `kia`.`journey_id`
									) `course_names`,

									## Letzte durch System verursachte Änderungen
									(
										SELECT
											GROUP_CONCAT(
												CONCAT(
													`kaa3`.`id`, '{|}',
													UNIX_TIMESTAMP(`kaa3`.`from`), '{|}',
													UNIX_TIMESTAMP(`kaa3`.`until`), '{|}',
													`kr`.`name`, '{|}',
													`cdb4`.`ext_33`, '{|}',
													UNIX_TIMESTAMP(`kaa3`.`matching_canceled`), '{|}',
													`kaa3`.`status`
												)
												SEPARATOR '{||}'
											)
										FROM
											`kolumbus_accommodations_allocations` `kaa3` INNER JOIN
											`kolumbus_rooms` `kr` ON
												`kr`.`id` = `room_id` INNER JOIN
											`customer_db_4` `cdb4` ON
												`cdb4`.`id` = `kr`.`accommodation_id`
										WHERE
											`kaa3`.`inquiry_accommodation_id` = `kia`.`id` AND
											`kaa3`.`status` IN (1, 2) AND
											`kaa3`.`room_id` > 0
										ORDER BY
											`kaa3`.`matching_canceled` DESC
									) `family_change`,

									## Zusammenreisende Schüler
									(
										SELECT
											GROUP_CONCAT(
												DISTINCT CONCAT(
													`cdb1_2`.`lastname`, '{|}',
													`cdb1_2`.`firstname`
												)
												SEPARATOR '{||}'
											)
										FROM
											`kolumbus_roomsharing` `kr` INNER JOIN
											`ts_inquiries` `ki_2` ON
												`ki_2`.`id` = `kr`.`share_id` INNER JOIN
											`ts_inquiries_to_contacts` `ts_i_to_c` ON
												`ts_i_to_c`.`inquiry_id` = `ki_2`.`id` AND
												`ts_i_to_c`.`type` = 'traveller' INNER JOIN
											`tc_contacts` `cdb1_2` ON
												`cdb1_2`.`id` = `ts_i_to_c`.`contact_id`
										WHERE
											`kr`.`master_id` = `ts_i`.`id`
									) `share_with`,

									getAge(
										`tc_c`.`birthday`
									) `customer_age`
								"; 
		
		$aSqlParts['from'] .= "
								INNER JOIN
									`ts_inquiries_journeys` `ts_i_j` ON
										`ts_i_j`.`id` = `kia`.`journey_id` AND
										`ts_i_j`.`school_id` = :school_id AND
										`ts_i_j`.`active` = 1 AND
										`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
								INNER JOIN
									`ts_inquiries` `ts_i` ON
										`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
										`ts_i`.`active` = 1 AND
										`ts_i`.`canceled` <= 0
								INNER JOIN
									`ts_inquiries_to_contacts` `ts_i_to_c` ON
										`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
										`ts_i_to_c`.`type` = 'traveller'
								INNER JOIN
									`tc_contacts` `tc_c` ON
										`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
										`tc_c`.`active` = 1
								INNER JOIN
									`tc_contacts_numbers` `tc_c_n` ON
										`tc_c_n`.`contact_id` = `tc_c`.`id`
								LEFT JOIN
									`ts_inquiries_matching_data` `ts_i_m_d` ON
										`ts_i_m_d`.`inquiry_id` = `ts_i`.`id`
								LEFT JOIN
									`ts_journeys_travellers_detail` `ts_j_t_d_guide` ON
										`ts_j_t_d_guide`.`journey_id` = `ts_i_j`.`id` AND
										`ts_j_t_d_guide`.`traveller_id` = `tc_c`.`id` AND
										`ts_j_t_d_guide`.`type` = 'guide'
								INNER JOIN
									`kolumbus_accommodations_categories` `kac` ON
										`kac`.`id` = `kia`.`accommodation_id` AND
										`kac`.`type_id` = :category_type_id
								INNER JOIN
									`kolumbus_accommodations_meals` `kam` ON							## Mahlzeiten
										`kam`.`id` = `kia`.`meal_id`
								INNER JOIN
									`kolumbus_accommodations_roomtypes` `kar` ON							## Raumtypen
										`kar`.`id` = `kia`.`roomtype_id`
								LEFT JOIN
									`kolumbus_accommodations_allocations` `kaa` ON		## Zuweisungen
										`kaa`.`inquiry_accommodation_id` = `kia`.`id` AND
										`kaa`.`room_id` > 0 AND
										`kaa`.`active` = 1 AND
										`kaa`.`status` = 0
                                LEFT JOIN
									`kolumbus_accommodations_allocations` `kaa2` ON		## offene Zuweisungen
										`kaa2`.`inquiry_accommodation_id` = `kia`.`id` AND
										`kaa2`.`room_id` = 0 AND
										`kaa2`.`active` = 1 AND
										`kaa2`.`status` = 0
								LEFT JOIN
									`kolumbus_accommodations_payments` `k_a_p` ON					# Unterk. Zahlungen wird geholt (nur eine, da group_by)
										`k_a_p`.`inquiry_accommodation_id` = `kia`.`id` AND
										`k_a_p`.`active`= 1
								LEFT JOIN
									`ts_companies` `ka` ON
										`ka`.`id` = `ts_i`.`agency_id`
								LEFT JOIN
									`kolumbus_groups` `kg` ON
										`kg`.`id` = `ts_i`.`group_id`
                                LEFT JOIN
                                   `kolumbus_inboxlist` `k_inb` ON
                                        `k_inb`.`short` = `ts_i`.`inbox` AND
                                        `k_inb`.`active` = 1
								LEFT JOIN
									`data_countries` AS `dc_nationality` ON
										`dc_nationality`.`cn_iso_2` = `tc_c`.`nationality`
								LEFT JOIN
									`data_languages` `dl_mothertongue` ON
										`dl_mothertongue`.`iso_639_1` = `tc_c`.`language`
								LEFT JOIN
									`ts_inquiries_journeys_additionalservices` `ts_i_j_as` ON
										`ts_i_j_as`.`relation_id` = `kia`.`id`AND
										`ts_i_j_as`.`relation` = 'accommodation' AND
										`ts_i_j_as`.`active` = 1
								LEFT JOIN
									`kolumbus_costs` `kc` ON
										`kc`.`id` = `ts_i_j_as`.`additionalservice_id`AND
										`kc`.`active` = 1
							";

		$aSqlParts['where'] .= " AND `kia`.`for_matching` = 1 AND
								 `kia`.`visible` = 1 AND 
								 `ts_i`.`confirmed` > 0 
								 ".$sWhereShowWithoutInvoice."
								";

		$aSqlParts['groupby'] = " `kia`.`id`";
		
		//$aSqlParts['orderby'] = " `kia`.`from` ASC";
		
	}
	
}	
