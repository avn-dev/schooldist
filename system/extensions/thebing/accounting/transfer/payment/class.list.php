<?php

/**
 * @property $id 	
 * @property $created
 * @property $changed
 * @property $active 		
 * @property $creator_id 		
 * @property $booked 		
 * @property $user_id 		
 * @property $transfer_type 		
 * @property $journey_id 		
 * @property $start
 * @property $end
 * @property $start_type
 * @property $end_type
 * @property $transfer_date
 * @property $transfer_time
 * @property $comment
 * @property $start_additional
 * @property $end_additional
 * @property $airline
 * @property $flightnumber
 * @property $pickup
 * @property $updated 		
 * @property $accommodation_confirmed 		
 * @property $customer_agency_confirmed
 * @property $provider_updated
 * @property $provider_confirmed
 * @property $provider_id 		
 * @property $provider_type 		
 * @property $driver_id
*/


// keine "richtige" WDBasic da wir hier keinen Einzelnen Dtaensatz betrachten
class Ext_Thebing_Accounting_Transfer_Payment_List extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_inquiries_journeys_transfers';

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {
		global $user_data;
		
		$aQueryData = array();

		$aQueryData['data'] = array();
		$aQueryData['data']['school_id'] = \Core\Handler\SessionHandler::getInstance()->get('sid');;

		$aQueryData['sql'] = "
				SELECT
					`ts_i_j_t`.`id`							`id`,
					`ts_i_j_t`.`id`							`inquiry_transfer_id`,
					`ts_i_j_t`.`transfer_type`				`transfer_type`,
					`ts_i_j_t`.`provider_id`				`provider_id`,
					`ts_i_j_t`.`provider_type`				`provider_type`,
					`ts_i_j_t`.`driver_id`					`driver_id`,
					`ts_i_j_t`.`transfer_date`				`transfer_date`,
					`ts_i_j_t`.`transfer_time`				`transfer_time`,
					`ts_i_j_t`.`active`						`active`,
					`ts_i_j_t`.`updated`					`updated`,
					`kg`.`short`							`group_short`,
					`kg`.`number`							`number`,
					`tc_c_n`.`number`						`customerNumber`,
					`tc_c`.`lastname`						`lastname`,
					`tc_c`.`firstname`						`firstname`,
					`ts_i_j`.`school_id`					`school_id`,
					`ktrpa`.`user_id`						`payment_user`,
					`ktrpa`.`created`						`payment_date`,
					`ktrpa`.`amount`						`payment_amount`,
					`ktrpa`.`payment_currency_id`			`payment_currency_id`,
					`ktrpa`.`payment_currency_id`			`currency_id`,
					`ktrpa`.`school_currency_id`			`school_currency_id`,
					`ktrpa`.`comment`						`comment`,
					`ktrpa`.`grouping_id`					`grouping_id`,
					IF(`ts_i_j_t`.`provider_type` = 'provider', `kc`.`bank_account_holder`, `cdb4`.`ext_68`) `bank_account_holder`,
					IF(`ts_i_j_t`.`provider_type` = 'provider', `kc`.`bank_account_number`, `cdb4`.`ext_70`) `bank_account_number`,
					IF(`ts_i_j_t`.`provider_type` = 'provider', `kc`.`bank_code`, `cdb4`.`ext_71`) `bank_code`,
					IF(`ts_i_j_t`.`provider_type` = 'provider', `kc`.`bank_name`, `cdb4`.`ext_69`) `bank_name`,
					IF(`ts_i_j_t`.`provider_type` = 'provider', `kc`.`bank_address`, `cdb4`.`ext_72`) `bank_address`,
					IF(`ts_i_j_t`.`provider_type` = 'provider', '', `cdb4`.`bank_account_iban`) `bank_account_iban`,
					IF(`ts_i_j_t`.`provider_type` = 'provider', '', `cdb4`.`bank_account_bic`) `bank_account_bic`,
					`ts_i`.`confirmed`,
				    `ts_i`.`inbox`,
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
					) `last_document_number`
				FROM
					`ts_inquiries_journeys_transfers` `ts_i_j_t` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `ts_i_j_t`.`journey_id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 AND
						`ts_i_j`.`school_id` = :school_id INNER JOIN
					`ts_inquiries` `ts_i` ON
						`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND		
						`ts_i`.`active` = 1 AND
						`ts_i`.`confirmed` > 0 AND
						`ts_i`.`has_invoice` = 1 INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `tc_c` ON
						`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
						`tc_c`.`active` = 1 INNER JOIN
					`tc_contacts_numbers` `tc_c_n` ON
						`tc_c_n`.`contact_id` = `tc_c`.`id` LEFT OUTER JOIN
					`kolumbus_groups` `kg` ON
						`kg`.`id` = `ts_i`.`group_id` LEFT OUTER JOIN
					`kolumbus_transfers_payments` `ktrpa` ON
						`ktrpa`.`inquiry_transfer_id` = `ts_i_j_t`.`id` AND
						`ktrpa`.`active` = 1 LEFT JOIN
					`kolumbus_companies` `kc` ON
						`kc`.`id` = `ts_i_j_t`.`provider_id` AND
						`ts_i_j_t`.`provider_type` = 'provider' AND
						`kc`.`active` = 1 LEFT JOIN
					`customer_db_4` `cdb4` ON
						`cdb4`.`id` = `ts_i_j_t`.`provider_id` AND
						`ts_i_j_t`.`provider_type` = 'accommodation' AND
						`cdb4`.`active` = 1
				WHERE
					/* Einmal bezahlt, immer bezahlt #-10007 */
					`ktrpa`.`id` IS NULL AND
					`ts_i_j_t`.`provider_id` > 0 AND
					(
						(
							`ts_i_j_t`.`active` = 1 AND
							`ts_i_j_t`.`booked` = 1
						)
						OR
						(
							`ts_i_j_t`.`active` <= 0 AND
							`ts_i_j_t`.`booked` = 1 AND
							IF(`ktrpa`.`amount` IS NULL, 1 = 0, `ktrpa`.`amount` > 0)
						)
					) AND (
						IF(
							UNIX_TIMESTAMP(`ts_i`.`canceled`) > 0,
							IF(
								`ktrpa`.`amount` > 0,
								1 = 1,								# Bezahlte gecancelte immer aufführen
								1 = 0								# Nicht bezahlte gecelte nicht aufführen
							),										# Gecancelte aber bezahlte werden aufgeführt
							1 = 1									# NICHT gecancelte immer aufführen
						)
					)
					
				GROUP BY
					`ts_i_j_t`.`id`

			";

		return $aQueryData;

	}

}
