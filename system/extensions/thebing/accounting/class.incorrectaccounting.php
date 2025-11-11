<?php


class Ext_Thebing_Accounting_Incorrectaccounting extends Ext_TS_Inquiry {

	protected $_sTableAlias = 'ts_i';

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {

		$aQueryData = array();
		$aQueryData['data']['school_id'] = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool()->id;

		$sLanguage = Ext_TC_System::getInterfaceLanguage();

		// 1024 Zeichen sind zu wenig für service_amount_data
		\Ext_TC_Util::setMySqlGroupConcatMaxLength();

		$aQueryData['sql'] = "
				SELECT
					`ts_i`.*
					{FORMAT},
					`tc_c`.`lastname`,
					`tc_c`.`firstname`,
					`ts_i_j`.`school_id` `school_id`,
					`ts_an`.`number` `agency_number`,
					`ts_i`.`currency_id` `currency_id`,
					`ts_i`.`payment_method` `payment_method`,
					`tc_cn`.`number` `customer_number`,
					`kg`.`short` `group_short`,
					`kg`.`name` `group_name`,
					`k_a`.`ext_1` `agency_name`,
					`k_a`.`ext_2` `agency_short`,
					GROUP_CONCAT(DISTINCT `ktc`.`name_".$sLanguage."` SEPARATOR '{||}') `course_names`,				
					GROUP_CONCAT(DISTINCT `ktc`.`name_short` SEPARATOR '{||}') `course_names_short`,
					GROUP_CONCAT(DISTINCT `ts_ijc`.`from` SEPARATOR '{||}') `course_dates_from`,			
					GROUP_CONCAT(DISTINCT `ts_ijc`.`until` SEPARATOR '{||}') `course_dates_until`,
					GROUP_CONCAT(DISTINCT `cd_4`.`ext_33` SEPARATOR '{||}') `accommodation_names`,					
					GROUP_CONCAT(DISTINCT CONCAT(`kar`.`name_".$sLanguage."`, ' / ', `kam`.`name_".$sLanguage."`, ' / ', `kac`.`name_".$sLanguage."`) SEPARATOR '{||}') `accommodation_names`,
					GROUP_CONCAT(DISTINCT CONCAT(`kar`.`short_".$sLanguage."`, ' / ', `kam`.`short_".$sLanguage."`, ' / ', `kac`.`short_".$sLanguage."`) SEPARATOR '{||}') `accommodation_names_short`,
					GROUP_CONCAT(DISTINCT `ts_ija`.`id` SEPARATOR '{||}') `journey_accommodations`,
					GROUP_CONCAT(DISTINCT `ts_ija`.`from` SEPARATOR '{||}') `accommodation_dates_from`,			
					GROUP_CONCAT(DISTINCT `ts_ija`.`until` SEPARATOR '{||}') `accommodation_dates_until`,
					GROUP_CONCAT(DISTINCT CONCAT(`kidvi`.`id`, '{|}', `kidvi`.`type`, '{|}', `kipi`.`amount_inquiry`, '{|}', `kidvi`.`type_id`, '{|}', 0, '{|}', 0, '{|}', `kidvi`.`parent_id`) SEPARATOR '{||}') `service_amount_data`,
					-- Overpayments als Subquery summieren, da der Query viel zu viele Joins für ein korrektes SUM() hat
					(
						SELECT
							SUM(`kipo_sub`.`amount_inquiry`)
						FROM
							`kolumbus_inquiries_payments_overpayment` `kipo_sub`
						WHERE
							`kipo_sub`.`payment_id` = `kip`.`id` AND
							`kipo_sub`.`active` = 1
					) `overpayment_amount`,
					(
						SELECT
							`document_number`
						FROM
							`kolumbus_inquiries_documents`
						WHERE
							`active` = 1 AND
							`entity` = '".Ext_TS_Inquiry::class."' AND
							`entity_id` = `ts_i`.`id` AND
							`type` IN (".Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('invoice').")
						ORDER BY
							`created` DESC
						LIMIT
							1
					) `document_number`
				FROM
					`ts_inquiries` `ts_i` INNER JOIN
					`kolumbus_inquiries_payments` `kip` ON
						`kip`.`inquiry_id` = `ts_i`.`id` AND
						`kip`.`active` = 1 INNER JOIN
					`kolumbus_inquiries_payments_overpayment` `kipo` ON
						`kipo`.`payment_id` = `kip`.`id` AND
						`kipo`.`active` = 1 LEFT JOIN
					`kolumbus_inquiries_payments_items` `kipi` ON
						`kipi`.`payment_id` = `kip`.`id` AND
						`kipi`.`active` = 1 LEFT JOIN
					`kolumbus_inquiries_documents_versions_items` `kidvi` ON
						`kidvi`.`id` = `kipi`.`item_id` AND
						`kidvi`.`active` = 1 INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `tc_c` ON
						`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
						`tc_c`.`active` = 1 INNER JOIN
					`tc_contacts_numbers` `tc_cn` ON
						`tc_cn`.`contact_id` = `tc_c`.`id` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 LEFT JOIN
					`kolumbus_groups` `kg` ON
						`kg`.`id` = `ts_i`.`group_id` AND
						`kg`.`active` = 1 LEFT JOIN
					`ts_companies` `k_a` ON
						`k_a`.`id` = `ts_i`.`agency_id` AND
						`k_a`.`active` = 1 LEFT JOIN
					`ts_companies_numbers` `ts_an` ON
						`ts_an`.`company_id` = `k_a`.`id` LEFT JOIN
					`ts_inquiries_journeys_courses` `ts_ijc` ON
						`ts_ijc`.`journey_id` = `ts_i_j`.`id` AND
						`ts_ijc`.`active` = 1 LEFT JOIN 
					`kolumbus_tuition_courses` `ktc` ON
						`ktc`.`id` = `ts_ijc`.`course_id` AND
						`ktc`.`active` = 1 LEFT JOIN
					`ts_inquiries_journeys_accommodations` `ts_ija` ON
						`ts_ija`.`journey_id` = `ts_i_j`.`id` AND
						`ts_ija`.`active` = 1 LEFT JOIN
					`kolumbus_accommodations_roomtypes` `kar` ON
						`kar`.`id` = `ts_ija`.`roomtype_id` AND
						`kar`.`active` = 1 LEFT JOIN
					`kolumbus_accommodations_meals` `kam` ON
						`kam`.`id` = `ts_ija`.`meal_id` AND
						`kam`.`active` = 1 LEFT JOIN
					`kolumbus_accommodations_categories` `kac` ON
						`kac`.`id` = `ts_ija`.`accommodation_id` AND
						`kac`.`active` = 1 LEFT JOIN
					`customer_db_4` `cd_4` ON
						`cd_4`.`id` = `ts_ija`.`accommodation_id` AND
						`cd_4`.`active` = 1 LEFT JOIN 
					`tc_contacts_to_addresses` `tc_cta` ON
						`tc_cta`.`contact_id` = `tc_c`.`id` LEFT JOIN
					`tc_addresses` `tc_a` ON
						`tc_a`.`id` = `tc_cta`.`address_id` AND
						`tc_a`.`active` = 1
				WHERE
					`ts_i`.`active` = 1 AND
					`ts_i_j`.`school_id` = :school_id
				GROUP BY
					`ts_i`.`id`
				HAVING
					`overpayment_amount` > 0
		";

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY `ts_i`.`id` ASC "; 
		}

		
		$aQueryData['sql'] = str_replace(
			'{FORMAT}',
			$this->_formatSelect(),
			$aQueryData['sql']
		);

		return $aQueryData;
	}

}
