<?php

/**
 * @TODO: Es darf keine zwei Klassen für dieselbe Entität geben
 */
class Ext_TS_Accounting_Receivable extends Ext_TC_Basic {

	protected $_sTable = 'kolumbus_inquiries_documents';
	
	protected $_sTableAlias = 'kid';

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
				
		$sLanguage = Ext_Thebing_School::fetchInterfaceLanguage();

		// 1024 Zeichen sind zu wenig für service_amount_data
		$sSql = "SET SESSION group_concat_max_len = 1048576";
		DB::executeQuery($sSql);

		$aSqlParts['select'] .= ",
			`kid`.`editor_id` `user_id`, /* Default Columns */
			`ts_i`.`currency_id` `currency_id`, /* Ext_Thebing_Gui2_Format_Amount */
			`ts_i`.`inbox`,
			`ts_i`.`payment_method` `payment_method`,
			`ts_i`.`amount` + `ts_i`.`amount_initial` `amount_total`,
			`ts_i`.`amount_payed`,
			`tc_cn`.`number` `contact_number`,
			`tc_c`.`firstname`,
			`tc_c`.`lastname`,
			`kg`.`short` `group_name`,
			`ka`.`ext_1` `agency_name`,
			`ts_i`.`status_id`,
			`ts_an`.`number` `agency_number`,
			`ts_dvp`.`date` `amount_finalpay_due`,
			COALESCE(
				(
					SELECT
						ROUND(
							SUM(
								IF(
									INSTR(`kid`.`type`, 'netto') = 0,
									`sub_kidvpi`.`amount_gross` - `sub_kidvpi`.`amount_discount_gross`,
									`sub_kidvpi`.`amount_net` - `sub_kidvpi`.`amount_discount_net`
								)
							),
						2)
					FROM
						`kolumbus_inquiries_documents_versions_priceindex` `sub_kidvpi`
					WHERE
						`sub_kidvpi`.`version_id` = `kid`.`latest_version`
				), 0) `expected_amount`,

			COALESCE(
				(
					SELECT 
						ROUND(SUM(`sub_kipi`.`amount_inquiry`), 2)
					FROM 
						`kolumbus_inquiries_payments_items` `sub_kipi` INNER JOIN
						`kolumbus_inquiries_payments` `sub_kip` ON
							`sub_kip`.`id` = `sub_kipi`.`payment_id` AND
							`sub_kip`.`active` = 1 INNER JOIN
						`kolumbus_inquiries_documents_versions_items` `sub_kidvi`	 ON
							`sub_kipi`.`item_id` = `sub_kidvi`.`id` AND
							`sub_kipi`.`active` = 1 INNER JOIN
						`kolumbus_inquiries_documents_versions` `s_kidv` ON
							`s_kidv`.`id` = `sub_kidvi`.`version_id`
					WHERE 
						`s_kidv`.`id` = `kid`.`latest_version` AND
						`sub_kidvi`.`active` = 1 AND
						`sub_kipi`.`active` = 1
				) , 0) `payed_amount`,

			GROUP_CONCAT(DISTINCT `ktc`.`name_".$sLanguage."` SEPARATOR '{||}') `course_names`,
			GROUP_CONCAT(DISTINCT `ktc`.`name_short` SEPARATOR '{||}') `course_names_short`,
			GROUP_CONCAT(DISTINCT `ts_i_j_c`.`from` SEPARATOR '{||}') `course_dates_from`,			
			GROUP_CONCAT(DISTINCT `ts_i_j_c`.`until` SEPARATOR '{||}') `course_dates_until`,
			GROUP_CONCAT(DISTINCT CONCAT(`kar`.`name_".$sLanguage."`, ' / ', `kam`.`name_".$sLanguage."`, ' / ', `kac`.`name_".$sLanguage."`) SEPARATOR '{||}') `accommodation_names`,
			GROUP_CONCAT(DISTINCT CONCAT(`kar`.`short_".$sLanguage."`, ' / ', `kam`.`short_".$sLanguage."`, ' / ', `kac`.`short_".$sLanguage."`) SEPARATOR '{||}') `accommodation_names_short`,	
			GROUP_CONCAT(DISTINCT `ts_i_j_a`.`id` SEPARATOR '{||}') `journey_accommodations`,
			GROUP_CONCAT(DISTINCT `ts_i_j_a`.`from` SEPARATOR '{||}') `accommodation_dates_from`,			
			GROUP_CONCAT(DISTINCT `ts_i_j_a`.`until` SEPARATOR '{||}') `accommodation_dates_until`,
			GROUP_CONCAT(DISTINCT CONCAT(`kidvi`.`id`, '{|}', `kidvi`.`type`, '{|}', `kipi`.`amount_inquiry`, '{|}', `kidvi`.`type_id`, '{|}', `kidvi`.`amount_net`, '{|}', `kidvi`.`amount_discount`, '{|}', `kidvi`.`parent_id`) SEPARATOR '{||}') `service_amount_data`,
			GROUP_CONCAT(DISTINCT CONCAT(`kidvi`.`id`, '{|}', `kidvi`.`type`, '{|}', `kidvi`.`amount`, '{|}', `kidvi`.`type_id`, '{|}', `kidvi`.`amount_net`, '{|}', `kidvi`.`amount_discount`, '{|}', `kidvi`.`parent_id`) SEPARATOR '{||}') `service_item_amount_data`
		";
		
		$aSqlParts['from'] .= " INNER JOIN
			`ts_inquiries` `ts_i` ON
				`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
				`ts_i`.`id` = `kid`.`entity_id` AND
				`ts_i`.`active` = 1 INNER JOIN
			`ts_inquiries_journeys` `ts_i_j` ON
				`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`ts_i_j`.`active` = 1 INNER JOIN
			`kolumbus_inquiries_documents_versions` `kidv` ON
				`kidv`.`id` = `kid`.`latest_version` INNER JOIN
			`kolumbus_inquiries_documents_versions_items` `kidvi` ON
				`kidvi`.`version_id` = `kid`.`latest_version` AND
				`kidvi`.`active` = 1 AND
				`kidvi`.`onPdf` = 1 LEFT JOIN
			`kolumbus_inquiries_payments_items` `kipi` ON
				`kipi`.`item_id` = `kidvi`.`id` AND
				`kipi`.`active` = 1 LEFT JOIN
			`kolumbus_inquiries_payments` `kip` ON
				`kip`.`id` = `kipi`.`payment_id` AND
				`kip`.`active` = 1 LEFT JOIN
			`ts_documents_versions_paymentterms` `ts_dvp` ON
				`ts_dvp`.`version_id` = `kidv`.`id` AND
				`ts_dvp`.`active` = 1 AND
				`ts_dvp`.`type` = 'final' INNER JOIN
			`ts_inquiries_to_contacts` `ts_itc` ON
				`ts_itc`.`inquiry_id` = `ts_i`.`id` AND
				`ts_itc`.`type` = 'traveller' INNER JOIN
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_itc`.`contact_id` AND
				`tc_c`.`active` = 1 LEFT JOIN
			`tc_contacts_to_addresses` `tc_cta` ON
				`tc_cta`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`tc_addresses` `tc_a` ON
				`tc_a`.`id` = `tc_cta`.`address_id` AND
				`tc_a`.`active` = 1 LEFT JOIN
			`tc_contacts_numbers` `tc_cn` ON
				`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`kolumbus_groups` `kg` ON
				`kg`.`id` = `ts_i`.`group_id` AND
				`kg`.`active` = 1 LEFT JOIN
			`ts_companies` `ka` ON
				`ka`.`id` = `ts_i`.`agency_id` AND
				`ka`.`active` = 1 LEFT JOIN
			`ts_companies_numbers` `ts_an` ON
				`ts_an`.`company_id` = `ka`.`id` LEFT JOIN
			`ts_inquiries_journeys_courses` `ts_i_j_c` ON
				`ts_i_j_c`.`journey_id` = `ts_i_j`.`id` AND
				`ts_i_j_c`.`active` = 1 LEFT JOIN 
			`kolumbus_tuition_courses` `ktc` ON
				`ktc`.`id` = `ts_i_j_c`.`course_id` AND
				`ktc`.`active` = 1 LEFT JOIN
			`ts_inquiries_journeys_accommodations` `ts_i_j_a` ON
				`ts_i_j_a`.`journey_id` = `ts_i_j`.`id` AND
				`ts_i_j_a`.`active` = 1 LEFT JOIN
			`kolumbus_accommodations_roomtypes` `kar` ON
				`kar`.`id` = `ts_i_j_a`.`roomtype_id` AND
				`kar`.`active` = 1 LEFT JOIN
			`kolumbus_accommodations_meals` `kam` ON
				`kam`.`id` = `ts_i_j_a`.`meal_id` AND
				`kam`.`active` = 1 LEFT JOIN
			`kolumbus_accommodations_categories` `kac` ON
				`kac`.`id` = `ts_i_j_a`.`accommodation_id` AND
				`kac`.`active` = 1 LEFT JOIN 
			`ts_inquiries_journeys_transfers` `ts_i_j_t` ON
				`ts_i_j_t`.`journey_id` = `ts_i_j`.`id` AND
				`ts_i_j_t`.`active` = 1
			
		";

		$aSqlParts['groupby'] .= " `kid`.`id` ";
		
		$aSqlParts['having'] .= "
			`amount_total` > `ts_i`.`amount_payed` AND
			`expected_amount` != `payed_amount`
		";
		
	}
	
}
