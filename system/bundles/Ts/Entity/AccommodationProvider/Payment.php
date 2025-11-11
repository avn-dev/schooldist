<?php

namespace Ts\Entity\AccommodationProvider;

/**
 * 
 * @param $id
 * @param $changed
 * @param $created
 * @param $editor_id
 * @param $creator_id
 * @param $type
 * @param $accommodation_allocation_id
 * @param $payment_category_id
 * @param $from
 * @param $until
 * @param $amount
 * @param $amount_currency_id
 */
class Payment extends \Ext_Thebing_Basic {

	const ERROR_PAYMENT_CATEGORY_NOT_FOUND = 'error_payment_category_not_found';
	const ERROR_NO_MATCHING_PERIOD = 'error_no_matching_periods';
	const ERROR_NO_MATCHING_COST_PERIOD = 'error_no_matching_cost_periods';
	const ERROR_PAYMENT_COST_CATEGORY_NOT_FOUND = 'error_payment_cost_category_not_found';

	protected $_sTable = 'ts_accommodation_providers_payments';
	protected $_sTableAlias = 'ts_app';
	protected $_sEditorIdColumn = 'editor_id';

	protected $_aJoinedObjects = array(
		'periods' => array(
			'class'					=> '\Ts\Entity\AccommodationProvider\Payment\Category',
			'key'					=> 'category_id',
			'check_active'			=> true,
			'type'					=> 'parent'
		),
		'allocation' => array(
			'class' => 'Ext_Thebing_Accommodation_Allocation',
			'key' => 'accommodation_allocation_id',
			'check_active' => true,
			'type' => 'parent',
			'readonly' => true
		)
	);

	/**
	 * Gibt einem die Möglichkeit die einzelnen SQL Parts zu verändern
	 * @see Ext_Gui2_Data
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= ", 
					`k_inb`.`name` `inbox`,
					`tc_cn`.`number` `customer_number`,
					`tc_c`.`id` `customer_id`,
					`tc_c`.`firstname` `customer_firstname`,
					`tc_c`.`lastname` `customer_lastname`,
					`ts_i`.`id` `inquiry_id`,
					`ts_ij`.`school_id`,
					`ts_ija`.`id` `inquiry_accommodation_id`,
					`ts_ija`.`roomtype_id`,
					`ts_ija`.`meal_id`,
					`cdb4`.`id` `accommodation_provider_id`,
					`cdb4`.`ext_68` `bank_account_holder`,
					`cdb4`.`ext_70` `bank_account_number`,
					`cdb4`.`ext_71` `account_bank_number`,
					`cdb4`.`ext_69` `bank_name`,
					`cdb4`.`ext_72` `bank_address`,
					`cdb4`.`bank_account_iban`,
					`cdb4`.`bank_account_bic`,
					GROUP_CONCAT(DISTINCT `cdb4`.`ext_33` SEPARATOR ', ') `provider`,
					`cdb4`.`ext_63` `provider_address`,
					`cdb4`.`ext_64` `provider_zip`,
					`cdb4`.`ext_65` `provider_city`,
					`cdb4`.`ext_103` `provider_firstname`,
					`cdb4`.`ext_104` `provider_lastname`,
					`ts_appc`.`name` `payment_category_name`,
					`ts_ija`.`roomtype_id` `booked_room`,
					`ts_ija`.`meal_id` `meal`,
					`ts_ija`.`accommodation_id` `booked_category`,
					`cdb4`.`default_category_id`,
					`kr`.`id` `allocated_room`,
					`kr`.`type_id` `allocated_roomtype_id`,
					`kg`.`short` `group`,
					`kg`.`number` `group_number`,
					`kacc`.`id` `cost_category_id`,
					`kacc`.`name` `cost_category_name`,
					`kacc`.`cost_type`,
					`kaa`.`id` `allocation_id`,
					`kaa`.`from` `allocation_from`,
					`kaa`.`until` `allocation_until`,
					(
						/* Da hier mehrere Einträge drin stehen können, muss das ein Subquery sein */
						SELECT
							GROUP_CONCAT(`ts_actap`.`accommodation_category_id`)
						FROM
							`kolumbus_accommodations_allocations` `kaa` INNER JOIN
							`kolumbus_rooms` AS `kr` ON
								`kr`.`id` = `kaa`.`room_id` INNER JOIN
							`ts_accommodation_categories_to_accommodation_providers` `ts_actap` ON
								`ts_actap`.`accommodation_provider_id` = `kr`.`accommodation_id`
						WHERE
							`kaa`.`id` = `ts_app`.`accommodation_allocation_id`
					) `accommodation_category_ids`
				";

		$aSqlParts['from'] .= " JOIN
					`kolumbus_accommodations_allocations` `kaa` ON
						`ts_app`.`accommodation_allocation_id` = `kaa`.`id` JOIN
					`ts_inquiries_journeys_accommodations` `ts_ija` ON
						`ts_ija`.`id` = `kaa`.`inquiry_accommodation_id` JOIN
					`ts_inquiries_journeys` `ts_ij` ON
						`ts_ija`.`journey_id` = `ts_ij`.`id` JOIN
					`ts_inquiries` `ts_i` ON
						`ts_ij`.`inquiry_id` = `ts_i`.`id` JOIN
					`kolumbus_inboxlist` `k_inb` ON
						`k_inb`.`short` = `ts_i`.`inbox` AND
						`k_inb`.`active` = 1 JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' LEFT JOIN
					`tc_contacts` `tc_c` ON
						`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
						`tc_c`.`active` = 1	JOIN
					`tc_contacts_numbers` `tc_cn` ON
						`tc_cn`.`contact_id` = `tc_c`.`id` JOIN
					`kolumbus_rooms` AS `kr` ON
						`kr`.`id` = `kaa`.`room_id` JOIN
					`customer_db_4` `cdb4` ON
						`kr`.`accommodation_id` = `cdb4`.`id` LEFT JOIN
					`ts_accommodation_providers_payment_categories` `ts_appc` ON
						`ts_app`.`payment_category_id` = `ts_appc`.`id` LEFT JOIN
					`kolumbus_groups` `kg` ON
						`kg`.`id` = `ts_i`.`group_id` LEFT JOIN
					`kolumbus_accommodations_costs_categories` `kacc` ON
						`ts_app`.`cost_category_id` = `kacc`.`id`
			";

		// Gruppierung ist optional (aktuell nur in Liste)
		if($sView !== 'single') {
			$aSqlParts['select'] .= ",
					COUNT(`ts_app`.`id`) `count`,
					SUM(`ts_app`.`amount`) `amount`,
					MIN(`ts_app`.`from`) `from`,
					MAX(`ts_app`.`until`) `until`,
					GROUP_CONCAT(CONCAT(`ts_app`.`from`, ',', `ts_app`.`until`) SEPARATOR '{|}') `periods`,
					GROUP_CONCAT(`ts_app`.`additional` SEPARATOR '{|}') `additional`,
					`ts_app`.`changed` `changed_original`,
					`ts_app`.`created` `created_original`
				";
			$aSqlParts['groupby'] .= "`ts_app`.`groupby`";
		} else {
			$aSqlParts['groupby'] .= "`ts_app`.`id`";
		}

	}

	/**
	 * Löscht die Entität und setzt den Zahlungsstatus der verbundenen Zuweisung zurück
	 */
	public function delete() {
		
		$oAccommodationAllocation = \Ext_Thebing_Accommodation_Allocation::getInstance($this->accommodation_allocation_id);

		if(
			$oAccommodationAllocation instanceof \Ext_Thebing_Accommodation_Allocation &&
			$oAccommodationAllocation->exist()
		) {
			$oAccommodationAllocation->payment_generation_completed = null;
			$oAccommodationAllocation->bSkipUpdatePaymentStack = true;
			$oAccommodationAllocation->save();
			$oAccommodationAllocation->bSkipUpdatePaymentStack = false;
		}

		parent::delete();
		
	}
	
}