<?php

$this->setFilterValues($aFilter);

$oSchool = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);
if(!$oSchool) {
	__pout('no School found');
	die();
}
$aSchools = array($oSchool->id);

$bCheckWDSearch = $this->_oGui->checkWDSearch();

$sLanguage = $oSchool->getInterfaceLanguage();

$iAddressLabelContactAddress = (int)Ext_TS_AddressLabel::getContactAdressLabelId();

$sSql = '';
$aSql = array();
$aSql['client_id'] = (int)$user_data['client'];
$aSql['short_field'] = 'short_'.$sLanguage;
$aSql['name_field'] = 'name_'.$sLanguage;
$aSql['address_label_id'] = $iAddressLabelContactAddress;

$aColumnList = $this->_oGui->getColumnList(); 
	
$sOrderByOption = $this->_oGui->getOption('orderby');



$sSelectAddon	= '';
$sWherePart		= '';
$sGroupPart		= '';
$sHavingPart	= '';
$iLimit			= 30;
$iEnd			= 0;

// Limit
if($this->_oGui->_aTableData['limit']){
	$iLimit = $this->_oGui->_aTableData['limit'];
}

$sJoinFilterInquiryAccommodation = "
	AND `kia`.`visible` = 1
	AND `kia`.`active` = 1
";

$sWhereShowWithoutInvoice	= Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ki`');

$sWherePart = " WHERE
					`ki`.`active` = 1 AND
					`ts_i_j`.`active` = 1
			";

if($sOrderByOption == 'payments'){
	$sSelectAddon .= '
						(
							SELECT
								`kip_payments`.`date` `received`
							FROM
								`kolumbus_inquiries_payments` `kip_payments` INNER JOIN
								`kolumbus_inquiries_payments_items` `kipi_payments` ON
									`kipi_payments`.`payment_id` = `kip_payments`.`id` INNER JOIN
								`kolumbus_inquiries_documents_versions_items` `kidvi_payments` ON
									`kidvi_payments`.`id` = `kipi_payments`.`item_id` INNER JOIN
								`kolumbus_inquiries_documents_versions` `kidv_payments` ON
									`kidv_payments`.`id` = `kidvi_payments`.`version_id` INNER JOIN
								`kolumbus_inquiries_documents` `kid_payments` ON
									`kid_payments`.`id` = `kidv_payments`.`document_id`
							WHERE
								`kid_payments`.`active` = 1 AND
								`kid_payments`.`inquiry_id` = `ki`.`id`
							ORDER BY
								`kip_payments`.`date` DESC
							LIMIT 1
						) `received`, ';
}

$sSelectPart = ' , `ki`.`id` `inquiry_id` ';

if($this->_oGui->getOption('query_transfer_location')) {
/*
	$sSelectPart .= ", 
						getTransferLocation(`kit_arr`.`start_type`, `kit_arr`.`start`) `transfer_start_location`,
						getTransferLocation(`kit_arr`.`end_type`, `kit_arr`.`end`) `transfer_end_location` ";
*/
}

if($this->_oGui->getOption('query_accommodation_communication')) {

	$sJoinFilterInquiryAccommodation = "";

	$sSelectPart .= ", `kia`.`id` `id_ija`, `kaal`.`id` `id_kaa` ";
	
	$sSelectPart .= "
									,
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
											`kr`.`master_id` = `ki`.`id`
									) `share_with`";
	
	$sSelectPart .= "
				,
					(
						SELECT
							GROUP_CONCAT(
								`kaal`.`id`
								SEPARATOR ','
							)
						FROM
							`kolumbus_accommodations_allocations` AS `kaal`
						WHERE
							`kaal`.`inquiry_accommodation_id` = `kia`.`id` AND
							`kaal`.`status` = 0  AND
							`kaal`.`active` = 1 AND
							`kaal`.`room_id` > 0
					) `active_accommodation_allocations`";

	$sSelectPart .= "
				,
					(
						SELECT
							GROUP_CONCAT(
								`kaal`.`id`
								SEPARATOR ','
							)
						FROM
							`kolumbus_accommodations_allocations` AS `kaal` LEFT JOIN
							`ts_inquiries_journeys_accommodations` `kia_sub` ON
								`kia_sub`.`id` = `kaal`.`inquiry_accommodation_id` AND
								`kia_sub`.`active` = 1 AND
								`kia_sub`.`visible` = 1 AND
								`kia_sub`.`for_matching` = 1
						WHERE
							`kaal`.`inquiry_accommodation_id` = `kia`.`id` AND
							(
								(
									`kaal`.`status` IN(1,2)  AND
									`kaal`.`active` = 0
								) OR
								(
									`kaal`.`status` = 0 AND
									`kaal`.`active` = 1 AND
									(
										`kaal`.`accommodation_canceled` > 0 OR
										`kaal`.`customer_agency_canceled` > 0
									)
								)
							)
					) `inactive_accommodation_allocations`";

	$sSelectPart .= "
				,
					(
						SELECT
							GROUP_CONCAT(
								`kaal`.`id`
								SEPARATOR ','
							)
						FROM
							`kolumbus_accommodations_allocations` AS `kaal`
						WHERE
							`kaal`.`inquiry_accommodation_id` = `kia`.`id` AND
							`kaal`.`status` = 0  AND
							`kaal`.`active` = 1 AND
							`kaal`.`accommodation_confirmed` > 0
					) `active_accommodation_confirmed`";

	$sSelectPart .= "
			,
				(
					SELECT
						GROUP_CONCAT(
							`kaal`.`accommodation_confirmed`
							SEPARATOR ','
						)
					FROM
						`kolumbus_accommodations_allocations` AS `kaal`
					WHERE
						`kaal`.`inquiry_accommodation_id` = `kia`.`id` AND
						`kaal`.`status` IN(1,2)  AND
						`kaal`.`active` = 0 AND
						`kaal`.`accommodation_confirmed` > 0
					ORDER BY
						`kaal`.`accommodation_confirmed`
					DESC
				) `inactive_accommodation_confirmed`";

	$sSelectPart .= "
				,
					(
						SELECT
							GROUP_CONCAT(
								`kaal`.`id`
								SEPARATOR ','
							)
						FROM
							`kolumbus_accommodations_allocations` AS `kaal`
						WHERE
							`kaal`.`inquiry_accommodation_id` = `kia`.`id` AND
							`kaal`.`status` = 0  AND
							`kaal`.`active` = 1 AND
							`kaal`.`customer_agency_confirmed` > 0
					) `active_customer_agency_confirmed`";

	$sSelectPart .= "
			,
				(
					SELECT
						GROUP_CONCAT(
							`kaal`.`customer_agency_confirmed`
							SEPARATOR ','
						)
					FROM
						`kolumbus_accommodations_allocations` AS `kaal`
					WHERE
						`kaal`.`inquiry_accommodation_id` = `kia`.`id` AND
						`kaal`.`active` = 0 AND
						`kaal`.`status` IN(1,2)  AND
						`kaal`.`customer_agency_confirmed` > 0
					ORDER BY
						`kaal`.`customer_agency_confirmed`
					DESC
				) `inactive_customer_agency_confirmed`";

	$sSelectPart .= "
		,(
			SELECT
				`kaal_sub`.`id`
			FROM
				`kolumbus_accommodations_allocations` AS `kaal_sub`
			WHERE
				`kaal_sub`.`inquiry_accommodation_id` = `kia`.`id` AND
				`kaal_sub`.`active` = 0 AND
				`kaal_sub`.`status` IN(1,2)  AND
				(
					`kaal_sub`.`accommodation_confirmed` > 0 OR
					`kaal_sub`.`customer_agency_confirmed` > 0
				) AND
				`kaal_sub`.`from` = `kaal`.`from` AND
				`kaal_sub`.`until` = `kaal`.`until`
			ORDER BY
				IF(
					`kaal_sub`.`accommodation_confirmed` > 0,
					`kaal_sub`.`accommodation_confirmed`,
					`kaal_sub`.`customer_agency_confirmed`
				) DESC
			LIMIT
				1
		) `last_accommodation_confirmed`
	";

	$sSelectPart .= ", GROUP_CONCAT(DISTINCT `ktc`.`name_short` ORDER BY `ktc`.`name_short` ASC) `course_names`";

	$sSelectPart .= "
			,
				(
					SELECT
						COUNT(*)
					FROM
						`kolumbus_accommodations_allocations` AS `kaal`
					WHERE
						`kaal`.`inquiry_accommodation_id` = `kia`.`id`
					GROUP BY
						`kaal`.`inquiry_accommodation_id`
				) `all_matchings`";

	$sSelectPart .= " ## Prüfen ob Anreisedaten vorhanden sind
				,
					(
						IF(
							`ts_i_j`.`transfer_mode` & ".Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL."
							,
								IF(
									`kit`.`transfer_date` != '0000-00-00' AND
									`kit`.`transfer_time` IS NOT NULL AND
									`kit`.`flightnumber` != ''
									,
									1
									,
									0
								)
							,
							-1

						)

					) `arrival_transferdata_exist`
					";

	$sSelectPart .= " ,`kaal`.`status` `allocation_status` ";

	$sJoinPart .= " LEFT JOIN
				`kolumbus_accommodations_allocations` `kaal` ON
					`kaal`.`inquiry_accommodation_id` = `kia`.`id` ";

	if($this->_oGui->getOption('query_accommodation_customers')){
		// Simple View der Unterkunftsliste
		$sJoinPart .= " AND
					
							`kaal`.`active` = 1 AND
							`kaal`.`status` = 0 AND
							`kaal`.`room_id` > 0
						";
	} else {
		// Unterkunftskommunikation
		$sJoinPart .= " AND
					(
						(
							`kaal`.`active` = 1 AND
							`kaal`.`status` = 0
						)
						OR
						(
							`kaal`.`active` = 0 AND
							`kaal`.`status` IN(1,2)
						)
					) ";
	}

	$sJoinPart .= " LEFT JOIN
				`kolumbus_rooms` AS `kr` FORCE INDEX (PRIMARY) ON
					`kr`.`id` = `kaal`.`room_id` AND
					`kr`.`active` = 1 LEFT JOIN
				`customer_db_4` AS `cdb4` FORCE INDEX (PRIMARY) ON
					`cdb4`.`id` = `kr`.`accommodation_id` AND
					`cdb4`.`active` = 1 LEFT JOIN
				`ts_accommodations_numbers` `ts_an` ON
					`ts_an`.`accommodation_id` = `cdb4`.`id`
				";

	$sJoinPart .= ' LEFT JOIN
			`kolumbus_tuition_courses` `ktc` ON
				`ktc`.`id` = `kic`.`course_id` AND
				`ktc`.`active` = 1
		';

	$sSelectPart .= "
				,
				`ts_i_j`.`inquiry_id`								`accommodation_inquiry_id`,
				`kaal`.`id`											`allocation_id`,
				`kaal`.`comment` `allocation_comment`,
				
				## nicht schön aber: prüfen ob unterkunftszuweisung noch active ist (Redmine t4152)
				IF(`kaal`.`active` = 1, `kaal`.`room_id`, '')				`allocation_room_id`,
				IF(`kaal`.`active` = 1, `cdb4`.`id`, '')					`accommodation_id`,
				IF(`kaal`.`active` = 1, `ts_an`.`number`, '')				`accommodation_number`,
				IF(`kaal`.`active` = 1, `cdb4`.`ext_33`, '')				`accommodation_name`,
				IF(`kaal`.`active` = 1, `cdb4`.`ext_63`, '')				`accommodation_street`,
				IF(`kaal`.`active` = 1, `cdb4`.`address_addon`, '')			`accommodation_address_addon`,
				IF(`kaal`.`active` = 1, `cdb4`.`ext_64`, '')				`accommodation_plz`,
				IF(`kaal`.`active` = 1, `cdb4`.`ext_65`, '')				`accommodation_city`,
				IF(`kaal`.`active` = 1, `cdb4`.`ext_67`, '')				`accommodation_phone`,
				IF(`kaal`.`active` = 1, `cdb4`.`ext_77`, '')				`accommodation_mobile`,
				IF(`kaal`.`active` = 1, `cdb4`.`family_description_{$sLanguage}`, '') `accommodation_family_description`,
				IF(`kaal`.`active` = 1, `cdb4`.`email`, '') `accommodation_email`,

				UNIX_TIMESTAMP(`kaal`.`customer_agency_confirmed`)			`accommodation_customer_agency_confirmed`,
				UNIX_TIMESTAMP(`kaal`.`accommodation_confirmed`)			`accommodation_accommodation_confirmed`,
				UNIX_TIMESTAMP(`kaal`.`accommodation_transfer_confirmed`)	`accommodation_transfer_confirmed`,
				UNIX_TIMESTAMP(`kaal`.`accommodation_canceled`)				`accommodation_canceled`,
				UNIX_TIMESTAMP(`kaal`.`customer_agency_canceled`)			`customer_agency_canceled`,
				UNIX_TIMESTAMP(`kaal`.`allocation_changed`)					`accommodation_changed`,
				`kr`.`name`											`room_name`,

				IF(`kaal`.`id` IS NULL, UNIX_TIMESTAMP(`kia`.`from`), UNIX_TIMESTAMP(`kaal`.`from`))	`inquiry_accommodation_from`,
				IF(`kaal`.`id` IS NULL, UNIX_TIMESTAMP(`kia`.`until`), UNIX_TIMESTAMP(`kaal`.`until`))	`inquiry_accommodation_until`,
               
                ## ist scheise aber ich hab nicht rausgefunden wo das problem ist siehe t3742 ##
                IF(`kaal`.`id` IS NULL, UNIX_TIMESTAMP(`kia`.`from`), UNIX_TIMESTAMP(`kaal`.`from`))	`inquiry_accommodation_from_2`,
				IF(`kaal`.`id` IS NULL, UNIX_TIMESTAMP(`kia`.`until`), UNIX_TIMESTAMP(`kaal`.`until`))	`inquiry_accommodation_until_2`,

				`kia`.`active` `inquiry_accommodation_active`,
				`kia`.`visible` `inquiry_accommodation_visible`

			";

	// Cancelte Kunden nur aufführen wenn sie mal gematched waren
	
	$sHavingPart .= " HAVING 
						IF(
							`canceled` = 0 AND `inquiry_accommodation_active`=1 AND `inquiry_accommodation_visible`=1,
							1 = 1,
							`inactive_accommodation_allocations` IS NOT NULL
						) AND
						IF(
							`allocation_status` IN (1,2),
							`active_accommodation_allocations` IS NULL AND last_accommodation_confirmed = `allocation_id`,
							1
						)
					";
	 
	 

}

if($this->_oGui->getOption('query_accommodation_customers')){
	$sSelectPart .= "
		,
		GROUP_CONCAT(DISTINCT CONCAT(`cdb1_shared`.`firstname`,' ', `cdb1_shared`.`lastname`) SEPARATOR ',') `acco_booking_shares`,
		(
			SELECT
				GROUP_CONCAT(CONCAT(`cdb1_shared`.`firstname`,' ', `cdb1_shared`.`lastname`) SEPARATOR ',')
			FROM
				`kolumbus_accommodations_allocations` `kaal_shared` INNER JOIN
				`ts_inquiries_journeys_accommodations` `kia_shared` ON
					`kia_shared`.`id` = `kaal_shared`.`inquiry_accommodation_id`	INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `kia_shared`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 LEFT JOIN
				`ts_inquiries` `ki_shared` ON 
					`ki_shared`.`id` = `ts_i_j`.`inquiry_id` AND
					`ki_shared`.`active` = 1 INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ki_shared`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `cdb1_shared` ON
					`cdb1_shared`.`id` = `ts_i_to_c`.`contact_id` AND
					`cdb1_shared`.`active` = 1
			WHERE
				`kaal_shared`.`room_id` = `kr`.`id` AND 
				`kaal_shared`.`id` != `kaal`.`id` AND
				`kaal_shared`.`status` = 0  AND
				(
					`kaal`.`from` BETWEEN `kaal_shared`.`from` AND `kaal_shared`.`until` OR
					`kaal`.`until` BETWEEN `kaal_shared`.`from` AND `kaal_shared`.`until`
				)
		)`acco_allocation_shares`,
		`kr`.`name` `room_name`,
		`kia`.`weeks` `acco_weeks`
	";

	$sJoinPart .= "
				LEFT JOIN
			`kolumbus_roomsharing` `kro` ON
				`kro`.`master_id` = `ki`.`id` LEFT JOIN
			`ts_inquiries` `ki_shared` ON
				`ki_shared`.`id` = `kro`.`share_id` AND
				`ki_shared`.`active` = 1 LEFT JOIN
			`ts_inquiries_to_contacts` `ts_i_to_c_shared` ON
				`ts_i_to_c_shared`.`inquiry_id` = `ki_shared`.`id` AND
				`ts_i_to_c_shared`.`type` = 'traveller' LEFT JOIN
			`tc_contacts` `cdb1_shared` ON
				`cdb1_shared`.`id` = `ts_i_to_c_shared`.`contact_id` AND
				`cdb1_shared`.`active` = 1
	";
}

if($this->_oGui->sView == 'transfer') { 

	$sSelectPart .= "
					,
					## Transfer
					`ts_i_j`.`inquiry_id`											`transfer_inquiry_id`,					## Id of inquiry from transfer
					`kit`.`id`														`inquiry_transfer_id`,					## Inquiry Transfer id
					`kit`.`provider_type`											`provider_type`,						## Provider Type id
					`kit`.`provider_id`												`provider_id`,							## Provider id
					`kit`.`driver_id`												`driver_id`,							## driver id
					`kit`.`start`													`transfer_start`,						## Start des Transfers
					`kit`.`start_type`												`start_type`,							## Start Type
					`kit`.`end`														`transfer_end`,							## Ende des Transfers
					`kit`.`end_type`												`end_type`,								## End Type
					`kit`.`comment`													`specific_transfer_comment`,			## Kommentar vom spezifischen Transfer
					UNIX_TIMESTAMP(`kit`.`provider_updated`)						`provider_updated`,						## Provider wurde zugewiesen
					UNIX_TIMESTAMP(`kit`.`provider_confirmed`)						`provider_confirmed`,					## Provider wurde über zuweisung informiert
					UNIX_TIMESTAMP(`kit`.`accommodation_confirmed`)					`accommodation_confirmed`,				## Unterkunft wurde über zuweisung informiert
					UNIX_TIMESTAMP(`kit`.`customer_agency_confirmed`)				`customer_agency_confirmed`,			## Kunde bzw. Agentur über Transfer informiert
					UNIX_TIMESTAMP(`kit`.`updated`)									`transfer_updated`,						## Transfer wurde über Student Record verändert
					GROUP_CONCAT(DISTINCT `kit`.`transfer_type` SEPARATOR '|')		`transfer_type`,						## Transfer Art
					## Transferzeit
					CONCAT(
						IF(
							`kit`.`transfer_time` IS NULL,
							'',
							`kit`.`transfer_time`
						)
					) `transfer_time`,
					## Pickupzeit
					CONCAT(
						IF(
							`kit`.`pickup` IS NULL,
							'',
							`kit`.`pickup`
						)
					) `pickup`,
					## Transferdaten (Datum)
					`kit`.`transfer_date` `transfer_date`,
					## Fluggesellschaft
					`kit`.`airline` `airline`,
					## Flugnummer
					`kit`.`flightnumber` `flightnumber`,
					
					## Transfer wurde angefragt bei Provider/Unterkunft
					MAX(`kitpr`.`created`) `transfer_requested`,
					
					## Provider-Info für Transferliste, Abhängigkeit in Ext_TS_Pickup_Gui2_Icon_Active
					(
						SELECT
							GROUP_CONCAT(
								DISTINCT
								CONCAT(
									`cdb4`.`ext_33`,
									'{|}',
									`cdb4`.`ext_63`,
									'{|}',
									`cdb4`.`ext_64`,
									'{|}',
									`cdb4`.`ext_65`,
									'{|}',
									`cdb4`.`ext_67`,
									'{|}',
									`cdb4`.`ext_76`,
									'{|}',
									`cdb4`.`ext_77`,
									'{|}',
									`cdb4`.`email`,
									'{|}',
									`cdb4`.`address_addon`
								)
								SEPARATOR '{||}'
							)
						FROM
							`kolumbus_accommodations_allocations` AS `kaal` INNER JOIN
							`kolumbus_rooms` `kr` ON
								`kr`.`id` = `kaal`.`room_id` INNER JOIN
							`customer_db_4` `cdb4` ON
								`cdb4`.`id` = `kr`.`accommodation_id`
						WHERE
							`kaal`.`inquiry_accommodation_id` = `kia`.`id` AND
							`kaal`.`status` = 0  AND
							`kaal`.`active` = 1 AND
							`kaal`.`room_id` > 0
					) `accommodation_info`
					
					";

	$sJoinPart .= " LEFT JOIN
				`kolumbus_inquiries_transfers_provider_request` AS `kitpr` ON
					`kitpr`.`transfer_id` = `kit`.`id` AND
					`kitpr`.`active` = 1
					";

}

if($this->_oGui->getOption('select_visum_fields') == 1) {
	$sSelectPart .= ',
		`ts_j_t_v_d`.`required` `visum_required`,
		`ts_j_t_v_d`.`servis_id` `visum_servis_id`,
		`ts_j_t_v_d`.`tracking_number` `visum_tracking_number`,
		`ts_j_t_v_d`.`passport_number` `visum_passport_number`,
		`ts_j_t_v_d`.`passport_date_of_issue` `visum_passport_date_of_issue`,
		`ts_j_t_v_d`.`date_from` `visum_date_from`,
		`ts_j_t_v_d`.`date_until` `visum_date_until`,
		`ts_j_t_v_d`.`status` `visum_status_id`
	';
}

if(
	isset($aFilter['special_filter']) &&
	!empty($aFilter['special_filter'])
) {
	$sJoinPart .= " LEFT JOIN
		`ts_inquiries_to_special_positions` `ts_i_to_sp` ON
			`ts_i_to_sp`.`inquiry_id` = `ki`.`id` LEFT JOIN
		`kolumbus_inquiries_positions_specials` `kips` ON
			`ts_i_to_sp`.`special_position_id` = `kips`.`id` AND
			`kips`.`active` = 1	
	";
}

// Die interface Sprache holen
$sSystemLanguage = Ext_TC_System::getInterfaceLanguage();
// Die Nationalität abhängig von der interace Sprache hinzufügen
$aSql['customer_nationality_full'] = 'nationality_'.$sSystemLanguage;
$aSql['customer_country'] = 'nationality_'.$sSystemLanguage;
$aSql['customer_mother_tongue'] = 'name_'.$sSystemLanguage;

// Selectionen schreiben
$sSql = "SELECT
					AUTO_SQL_CALC_FOUND_ROWS

					`d_c`.#customer_nationality_full								`customer_nationality_full`,
					`ki`.`editor_id`												`editor_id`,
					`tc_c_n`.`number`												`customerNumber`,
					`tc_e`.`email`													`customer_mail`,
					/*`tc_c_d_comment`.`value`										`customer_comment`,
					`tc_c_d_phone`.`value`											`customer_phone`,
					`tc_c_d_mobile`.`value`											`customer_mobile`,*/
					`cdb1`.`id` `traveller_id`,
					`cdb1`.`lastname`												`lastname`,
					`cdb1`.`firstname`												`firstname`,
					`cdb1`.`gender`													`customer_gender`,
					`cdb1`.`birthday`												`customer_birthday`,
					`cdb1`.`birthday`												`customer_age`,
					`cdb1`.`nationality`											`customer_nationality`,
					`d_l`.#customer_mother_tongue                                   `customer_mother_tongue`,
					`d_l2`.#customer_mother_tongue                                  `corresponding_language`,
					`tc_a`.`address`												`customer_address`,						## Adresse
					`tc_a`.`address_addon`											`customer_address2`,					## Adresse
					`tc_a`.`zip`													`customer_zip`,							## ZIP
					`tc_a`.`city`													`customer_city`,						## City
					`tc_a`.`state`													`customer_state`,						## State
					`d_c`.#customer_country         								`customer_country`,						## Land

					CONCAT(`cdb1`.`lastname`, ',', `cdb1`.`firstname`)				`customer_name`,						## Zusammenselektiert um Suchen zu ermöglichen über 2 spalten
					`ki`.`creator_id`												`creator_id`,							## Ersteller Id
					`ts_i_j`.`school_id`											`school_id`,							## Schule
                    `ki`.`inbox`                                                    `inbox`,                                ## Inbox
                    `ki`.`checkin`													`checkin`,								## Checkin
					`ki`.`agency_id`												`agency_id`,							## Agentur
					`ki`.`payment_method`											`payment_method`,						## Bezahlmethode
					`ki`.`currency_id`												`currency_id`,							## Währung
					IF(`ki`.`canceled` > 0, 'cancelled', '')						`invoice_status`,						## Für Row Style (Index-Adaption)
					`ki`.`status_id`												`status_id`,							## Status
					`ts_i_j`.`transfer_mode`										`transfer_mode`,	
					`wa_journey`.`value`											`transfer_comment`,					
					`ki`.`referer_id`												`referer`,								## wie sind Sie auf uns aufmersam geworden
					`ts_i_m_d`.`acc_allergies`										`acc_allergies`,						## Allergien
					`ts_i_m_d`.`acc_comment`										`acc_comment`,							## Unterkunft Kommentar
					`ts_i_m_d`.`acc_comment2`										`acc_comment2`,							## Unterkunft Kommentar2
					`ts_j_t_v_d`.`passport_due_date`								`visum_passport_due_date`,				## Pass Ablaufdatum
					
					`ki`.`amount_credit`											`amount_credit`,
					`ki`.`amount_payed`												`amount_payed`,							## Gesamtbetrag aller Payments
					`ki`.`amount_payed_at_school`									`payments_local`,						## Vorort bezahlt
					`ki`.`amount_payed_prior_to_arrival`							`payments`,								## Vor Anreise bezahlt
					`ki`.`amount_payed_refund`										`payments_refund`,						## Erstattung

					`ki`.`amount_initial`											`amount_initial`,						## Betrag der aktuellen Rechnung (vor ort) - Betrag in Schule
					IF(
						`ki`.`canceled_amount` = 0,
						`ki`.`amount`,
						`ki`.`canceled_amount`
					) `amount`,														## Betrag der aktuellen Rechnung (vor anreise)
					`ki`.`transfer_data_requested`									`transfer_data_requested`,				## Transferdaten wurden angefragt (Unterkunftskommunikation)

					UNIX_TIMESTAMP(`ki`.`confirmed`)								`confirmed`,							## Rechnung wurde bestätigt
					UNIX_TIMESTAMP(`ki`.`canceled`)									`canceled`,								## Rechnung wurde storniert
					UNIX_TIMESTAMP(`ki`.`created`)									`created`,
					UNIX_TIMESTAMP(`ki`.`changed`)									`changed`,

					## --------------------------------------------------

					IF(
						IF(
							`ki`.`canceled_amount` = 0,
							`ki`.`amount` - `ki`.`amount_payed_prior_to_arrival`,
							`ki`.`canceled_amount` - `ki`.`amount_payed_prior_to_arrival`
						) > 0,
						IF(
							`ki`.`canceled_amount` = 0,
							`ki`.`amount` - `ki`.`amount_payed_prior_to_arrival`,
							`ki`.`canceled_amount` - `ki`.`amount_payed_prior_to_arrival`
						),
						0
					) `amount_due_arrival`,

					IF(
						(`ki`.`amount_initial` - `ki`.`amount_payed_at_school`) > 0,
						(`ki`.`amount_initial` - `ki`.`amount_payed_at_school`),
						0
					) `amount_due_at_school`,

					(
						IF(
							`ki`.`canceled_amount` = 0,
							`ki`.`amount`,
							`ki`.`canceled_amount`
						) + `ki`.`amount_initial` - `ki`.`amount_payed`
					) `amount_due_general`,

					## --------------------------------------------------

					kia.roomtype_id,

					GROUP_CONCAT(
						DISTINCT
						CONCAT(
							`kit`.`transfer_type`,
							'{|}',
							`kit`.`id`,
							'{|}',
							IFNULL(`kit`.`transfer_date`, ''),
							'{|}',
							IFNULL(`kit`.`transfer_time`, ''),
							'{|}',
							`kit`.`airline`,
							'{|}',
							`kit`.`flightnumber`,
							'{|}',
							`kit`.`comment`,
							'{|}',
							`kit`.`accommodation_confirmed`,
							'{|}',
							`kit`.`customer_agency_confirmed`,
							'{|}',
							`kit`.`updated`,
							'{|}',
							`kit`.`start`,
							'{|}',
							`kit`.`end`,
							'{|}',
							`kit`.`start_type`,
							'{|}',
							`kit`.`end_type`
						)
						SEPARATOR '{||}'
					) `transfer_data`,
					

					CONCAT(`kg`.`short`, IF(`ts_j_t_d`.`value` = 1, ' *', ''))		`group_short`,
					`kg`.`name` `group_name`,
					`kg`.`number` `group_number`,

					GROUP_CONCAT(`kidvic`.`inquiry_id`)								`document_version_item_change`,			## Wenn es Veränderungen im SR gibt
					
					(
						SELECT
							`document_number`
						FROM
							`kolumbus_inquiries_documents`
						WHERE
							`active` = 1 AND
							`entity` = '".Ext_TS_Inquiry::class."' AND
							`entity_id` = `ki`.`id` AND
							`type` IN (".Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('invoice').")
						ORDER BY
							`created` DESC
						LIMIT
							1
					) `document_number`,																					## Letzte Documentnummer

";

$sSql .= "
					## Agenturinfos
					`ka`.`ext_1` 							`agency_full_name`,
					`ka`.`ext_2` 							`agency_name`,
					`ka_n`.`number` 						`agency_number`,
					IF(`ka`.`invoice` = 2, 1, 0) 			`pdf_net`,
					IF(`ka`.`invoice` = 1, 1, 0) 			`pdf_gross`,
					`ka`.`ext_29` 							`pdf_loa`,
					
					## Kursinformationen
					UNIX_TIMESTAMP(`kic`.`from`)				`inquiry_course_from`,
					UNIX_TIMESTAMP(`kic`.`until`)				`inquiry_course_until`,


					## Unterkunftsinformationen
					UNIX_TIMESTAMP(`kia`.`from`)				`inquiry_accommodation_from`,
					UNIX_TIMESTAMP(`kia`.`until`)				`inquiry_accommodation_until` ,
					`kia`.`weeks` ,


					## Begin des ersten Kurses
					MIN(`kic`.`from`) `first_course_start`,

					## Ende des letzten Kurses
					MAX(`kic`.`until`) `last_course_end`,

					## Beginn der ersten Unterkunft
					MIN(`kia`.`from`) `first_accommodation_start`,
					
					## Ende der letzten Unterkunft
					MAX(`kia`.`until`) `last_accommodation_end`,
";

if(
	$this->_oGui->checkColumn('pdf_student_card')
){
	$sSql .= "
					## StudentCards
					## Analog zu Ext_Thebing_Inquiry_Document_Search->searchAdditional()
					(
						SELECT
							`kidv`.`path`
						FROM
							`kolumbus_inquiries_documents` `kid` INNER JOIN
							`kolumbus_inquiries_documents_versions` `kidv` ON
								`kid`.id = kidv.document_id AND
								`kidv`.active = 1 INNER JOIN
							`kolumbus_pdf_templates` `kpt` ON
								`kpt`.`id` = `kidv`.`template_id` AND
								`kpt`.`type` = 'document_student_cards'
						WHERE
							`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
							`kid`.`entity_id` = `ki`.`id` AND
							`kid`.`active` = 1
						ORDER BY
							`kid`.`created` DESC,
							`kid`.`document_number` ASC
						LIMIT
							1
					) `pdf_student_card`,
					
	";
}

$sSql .= "
					## Buchung mit Proforma OHNE Rechnung
					IF(
						`ki`.`has_proforma` = 1 AND 
						`ki`.`canceled` <= 0 AND 
						`ki`.`has_invoice` = 0,
						1,
						0
					) `only_proforma`,

					## Sonstiges

					`ts_j_t_v_d`.`status` 								`visum_name`,
";

if(
	$this->_oGui->checkColumn('accommodation_id', 'kia')
){
	
	$sSql .= "GROUP_CONCAT(
						DISTINCT
						CONCAT(
							IFNULL(`kia`.`accommodation_id`, ''),
							'{|}',
							IFNULL(`kia`.`roomtype_id`, ''),
							'{|}',
							IFNULL(`kia`.`meal_id`, '')
						)
						SEPARATOR '{||}'
					) `accommodation_fulllist`,";
}

if(
	$this->_oGui->checkColumn('from', 'kic') ||
	$this->_oGui->checkColumn('until', 'kic') ||
	$this->_oGui->checkColumn('level_id', 'kic') ||
	$this->_oGui->checkColumn('course_id', 'kic') ||
	$this->_oGui->checkColumn('name_'.$sLanguage, 'ktc') ||
	$this->_oGui->checkColumn('first_course_start', 'ktc')
){
	$sSql .= "
		GROUP_CONCAT(
			DISTINCT CONCAT(
				`kic`.`id`,
				'_',
				`kic`.`from`,
				'_',
				`kic`.`until`,
				'_',
				`kic`.`course_id`,
				'_',
				`kic`.`level_id`,
				'_',
				`kic`.`weeks`
			) ORDER BY `kic`.`from` ASC SEPARATOR '#'
		) `course_data`,
	";
}

if(
	$this->_oGui->checkColumn('from', 'kia') ||
	$this->_oGui->checkColumn('until', 'kia')
){
	$sSql .= "
	## Alloccation Data -------------------------------------------------------------------	
	
		GROUP_CONCAT(
			DISTINCT CONCAT(
				`kia`.`from`,
				'{=}',
				`kia`.`until`
			) SEPARATOR ','
		) `acc_times_fulllist`,
	";
}

$sSql .= "
## Alloccation Data -------------------------------------------------------------------
##					`ts_i_m_d`.`accommodation_data` `kaal_data`,
## ------------------------------------------------------------------------------------

";

if(
	$this->_oGui->checkColumn('departure_day', 'ki') ||
	$this->_oGui->sView == 'departure_list'
){
	$sSql .= "			
						## Abreisedatum
						GREATEST(
							IF (
								UNIX_TIMESTAMP(MAX(`ki`.`departure_date`)) > 0,
								UNIX_TIMESTAMP(MAX(`ki`.`departure_date`)),
								0
							),
							IF (
								UNIX_TIMESTAMP(MAX(`kia`.`until`)) > 0 ,
								UNIX_TIMESTAMP(MAX(`kia`.`until`)),
								0
							),
								IF (
								UNIX_TIMESTAMP(MAX(`kic`.`until`)) > 0,
								UNIX_TIMESTAMP(MAX(`kic`.`until`)),
								0
							)
						) as `departure_day`, 
	";
}

$sSql .=				' ' . $sSelectAddon . ' ';

if(
	$this->_oGui->checkColumn('payment_reminder')
){
	$sSql .= "
					(
						SELECT
							CONCAT(COUNT(`inquiry_id`), ',', MAX(`date`))
						FROM
							`kolumbus_inquiries_payments_reminders`
						WHERE
							`inquiry_id` = `ki`.`id`
						GROUP BY
							`inquiry_id`
					) `payment_reminder`,
	";
}

if($this->_oGui->getOption('show_only_visa') == 1) {
	
	$sJoinPart .= " LEFT JOIN
		`kolumbus_visum_status` `kvs`
			ON `kvs`.`id` = `ts_j_t_v_d`.`status` AND
			`kvs`.`active` = 1	
	";
	
	$sWherePart .= "
		AND (
			`ts_j_t_v_d`.`required` = 1 OR
			`kvs`.`id` IS NOT NULL
		)
	";
}

$aFilterElements = $this->_oGui->getAllFilterElements();
foreach($aFilterElements as $oFilterElement) {
	if(
		$oFilterElement->id == 'course_category_info_select'
	){
		$sJoinPart .= ' LEFT JOIN
			`kolumbus_tuition_courses` `ktc` ON
				`ktc`.`id` = `kic`.`course_id` AND
				`ktc`.`active` = 1
		';
	}
}

$sSql .= "
					#query_id_alias.#query_id_column `id`,
					`ki`.`has_invoice`,
					`ki`.`has_proforma`
					".$sSelectPart."
			FROM
				`ts_inquiries_journeys` `ts_i_j` INNER JOIN
				`ts_inquiries` `ki` ON
					`ki`.`id` = `ts_i_j`.`inquiry_id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
					`ts_i_to_c`.`type` = 'traveller' INNER JOIN
				`tc_contacts` `cdb1` ON
					`cdb1`.`id` = `ts_i_to_c`.`contact_id` AND
					`cdb1`.`active` = 1 LEFT JOIN
				`data_languages` `d_l` ON
					`d_l`.`iso_639_1` = `cdb1`.`language` LEFT JOIN
				`data_languages` `d_l2` ON
					`d_l2`.`iso_639_1` = `cdb1`.`corresponding_language` LEFT JOIN
				`ts_inquiries_journeys_accommodations` `kia` ON
					`kia`.`journey_id` = `ts_i_j`.`id` AND
					`kia`.`for_matching` = 1
					".$sJoinFilterInquiryAccommodation." LEFT JOIN
				`ts_inquiries_journeys_courses` `kic` ON
					`kic`.`journey_id` = `ts_i_j`.`id` AND
					`kic`.`visible` = 1 AND
					`kic`.`active` = 1  AND
					`kic`.`for_tuition` = 1  LEFT JOIN
				`kolumbus_groups` `kg` ON
					`kg`.`id` = `ki`.`group_id` LEFT JOIN
				`ts_journeys_travellers_visa_data` `ts_j_t_v_d` ON
					`ts_j_t_v_d`.`journey_id` = `ts_i_j`.`id` AND
					`ts_j_t_v_d`.`traveller_id` = `cdb1`.`id` LEFT JOIN
				`wdbasic_attributes` `wa_journey` ON
					`wa_journey`.`entity` = 'ts_inquiries_journeys' AND
					`wa_journey`.`entity_id` = `ts_i_j`.`id` AND
					`wa_journey`.`key` = 'transfer_comment' LEFT JOIN
				`ts_companies` `ka` ON
					`ka`.`id` = `ki`.`agency_id` LEFT JOIN
				`ts_companies_numbers` `ka_n` ON
					`ka_n`.`company_id` = `ka`.`id` LEFT JOIN
				`ts_inquiries_matching_data` `ts_i_m_d` ON
					`ts_i_m_d`.`inquiry_id` = `ki`.`id` LEFT JOIN
				`tc_contacts_numbers` `tc_c_n` ON
					`tc_c_n`.`contact_id` = `cdb1`.`id` LEFT JOIN
				`tc_contacts_to_emailaddresses` `tc_c_to_e` ON
					`tc_c_to_e`.`contact_id` = `cdb1`.`id` LEFT JOIN
				`tc_emailaddresses` `tc_e` ON
					`tc_e`.`id` = `tc_c_to_e`.`emailaddress_id` AND
					`tc_e`.`active` = 1 AND
					`tc_e`.`master` = 1 LEFT JOIN
				`data_countries` AS `d_c` ON
					`d_c`.`cn_iso_2` = `cdb1`.`nationality` /* LEFT JOIN
				`tc_contacts_details` `tc_c_d_comment` ON
					`tc_c_d_comment`.`contact_id` = `cdb1`.`id` AND
					`tc_c_d_comment`.`active` = 1 AND
					`tc_c_d_comment`.`type` = 'comment' LEFT JOIN
				`tc_contacts_details` `tc_c_d_phone` ON
					`tc_c_d_phone`.`contact_id` = `cdb1`.`id` AND
					`tc_c_d_phone`.`active` = 1 AND
					`tc_c_d_phone`.`type` = 'phone_private' LEFT JOIN
				`tc_contacts_details` `tc_c_d_mobile` ON
					`tc_c_d_mobile`.`contact_id` = `cdb1`.`id` AND
					`tc_c_d_mobile`.`active` = 1 AND
					`tc_c_d_mobile`.`type` = 'phone_mobile' */ LEFT JOIN
				`tc_contacts_to_addresses` `tc_c_to_a` ON
					`tc_c_to_a`.`contact_id` = `cdb1`.`id` LEFT JOIN
				`tc_addresses` `tc_a` ON
					`tc_a`.`id` = `tc_c_to_a`.`address_id` AND
					`tc_a`.`active` = 1 AND
					`tc_a`.`label_id` = :address_label_id LEFT JOIN
				`data_countries` AS `d_c2` ON
					`d_c2`.`cn_iso_2` = `tc_a`.`country_iso` LEFT JOIN
				`ts_journeys_travellers_detail` `ts_j_t_d` ON
					`ts_j_t_d`.`journey_id` = `ts_i_j`.`id` AND
					`ts_j_t_d`.`traveller_id` = `cdb1`.`id` AND
					`ts_j_t_d`.`type` = 'guide' LEFT JOIN
				`kolumbus_inquiries_documents` `kid_filter` ON
					`kid_filter`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`kid_filter`.`entity_id` = `ki`.`id` AND
					`kid_filter`.`active` = 1 AND
					`kid_filter`.`document_number` != '' LEFT JOIN
				`kolumbus_inquiries_documents_versions_items_changes` `kidvic` ON
					`kidvic`.`inquiry_id` = `ki`.`id` AND
					`kidvic`.`active` = 1 AND
					`kidvic`.`visible` = 1 LEFT JOIN
				`ts_inquiries_journeys_transfers` `kit` ON
					`kit`.`journey_id` = `ts_i_j`.`id` AND
					`kit`.`active` = 1";

if($this->_oGui->sView != 'transfer') { 
	$sSql .= "		AND `kit`.`transfer_type` IN (1,2) ";
}

$sSql .= $sJoinPart;

if(Ext_Thebing_System::isAllSchools()) {

	$aAccessSchools		= Ext_Thebing_System::getClient()->getSchoolListByAccess(true);
	$aAccessSchoolIds	= array_keys($aAccessSchools);

	$sWherePart .= " AND
					`ts_i_j`.`school_id` IN(:access_school_ids)
			";
	
	$aSql['access_school_ids'] = $aAccessSchoolIds;

} else {

	$sWherePart .= " AND
					`ts_i_j`.`school_id` = :school_id
			";
	$aSql['school_id'] = Ext_Thebing_School::getSchoolIdFromSession();

}

// Group By
$sGroupPart = ' GROUP BY 
						`ki`.`id` ';

// INBOX
if(
	$this->_oGui->sView == 'departure_list' ||
	$this->_oGui->sView == 'arrival_list' ||
	$this->_oGui->sView == 'student_cards' ||
	$this->_oGui->sView == 'visum_list' ||
	$this->_oGui->sView == 'simple_view'
) {
	
	// Es dürfen Nur Schüler angezeigt werden die keine Rechnung haben, wenn Client erlaubt
	$sWherePart .= $sWhereShowWithoutInvoice;

	$sWherePart .= ' AND `ki`.`canceled` <= 0 ';

} else if(
	$this->_oGui->sView == 'inbox'
) {

	$sHavingPart = '';

} else if(
	$sOrderByOption == ''
) {

	if($this->_oGui->sView !== 'proforma') {
		$sWherePart .= $sWhereShowWithoutInvoice;
	}

	$sHavingPart = '';

} else if($sOrderByOption == 'client_payments') {

	$sWherePart .= ' AND `ki`.`canceled` <= 0 ';

} else if($sOrderByOption == 'agency_payment') {

	$sWherePart .= ' AND `ki`.`canceled` <= 0
					 AND `ki`.`amount` >= `ki`.`amount_payed`'; // da auch beides 0 sein kann

	$sHavingPart = " HAVING
						(
						`kidv_net` != '' OR
						(							## Auch Brutto Agenturkunden die vorort Zahlen
							`kidv_gross` != '' AND
							`agency_id` > 0 AND
							(
								`payment_method` = 1 OR
								`payment_method` = 3		## Zeile wurde ergänzt T3273
							)
						)
						)
					";

}

// In der Proformaliste dürfen NUR Einträge mit Proforma sein
if($this->_oGui->sView == 'proforma') {
	$sHavingPart = ' HAVING `only_proforma` = 1 ';
	
	$sWherePart .= ' AND `ki`.`group_id` <= 0 ';
}

if($this->_oGui->sView == 'transfer') {

	// Es dürfen Nur Schüler angezeigt werden die keine Rechnung haben, wenn Client erlaubt
	#if($oClient->show_customer_without_invoice != 1){
		#$sWherePart .= ' AND `ki`.`confirmed` > 0 ';
	#}
	$sWherePart .= $sWhereShowWithoutInvoice;

	$sWherePart .= " AND
						(
							UNIX_TIMESTAMP(`ki`.`canceled`) <= 0 OR
							(
								`kit`.`provider_id` > 0		## Transfere anzeigen auch wenn gecanceled aber Provider zugewiesen
							)
						)
					AND
					`kit`.`id` IS NOT NULL AND (
						`kit`.`transfer_type` = ".Ext_TS_Inquiry_Journey_Transfer::TYPE_ADDITIONAL." OR
						(
							`ts_i_j`.`transfer_mode` & ".Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL." AND
							`kit`.`transfer_type` = ".Ext_TS_Inquiry_Journey_Transfer::TYPE_ARRIVAL."
						) OR (
							`ts_i_j`.`transfer_mode` & ".Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE." AND
							`kit`.`transfer_type` = ".Ext_TS_Inquiry_Journey_Transfer::TYPE_DEPARTURE."
						)
					)
					";

	$sGroupPart = ' GROUP BY `kit`.`id`, `ki`.`id` ';

	$aSql['empty_timestamp'] = '0000-00-00 00:00:00';
	
}

if($this->_oGui->getOption('query_accommodation_communication')) {

	$iConfig	= Ext_Thebing_System::getConfig('show_customer_without_invoice');

	if($iConfig == 0)
	{
		$sWherePart .= " AND (`ki`.`has_invoice` = 1 OR `ki`.`has_proforma` = 1)";
	}
	elseif($iConfig == 2)
	{
		$sWherePart .= " AND `ki`.`has_invoice` = 1";
	}

}

$sWherePart .= " AND `ki`.`confirmed` > 0 ";
$sWherePart .= " AND `ki`.`type` & ".\Ext_TS_Inquiry::TYPE_BOOKING;
$sWherePart .= " AND `ts_i_j`.`type` & ".\Ext_TS_Inquiry_Journey::TYPE_BOOKING;

if($this->_oGui->getOption('groupby') == 'accommodations') {
	$sGroupPart = ' GROUP BY `kia`.`id`, `kaal`.`id` ';
} else if($this->_oGui->getOption('groupby') == 'overview') {
	$sGroupPart = ' GROUP BY `kid`.`id` ';
}

$aInboxData = $this->getInboxData();
if($aInboxData['short'] != ""){
	$sWherePart .= " AND (`ki`.`inbox` = :inbox OR `ki`.`inbox` = '') ";
	$aSql['inbox'] = $aInboxData['short'];
}

$aSql['query_id_alias'] = $this->_oGui->query_id_alias;
$aSql['query_id_column'] = $this->_oGui->query_id_column;

// Filter in den Where Part einbauen
$aQueryParts = array('where'=>$sWherePart, 'having' => $sHavingPart);

$this->setQueryFilterDataByRef($aFilter, $aQueryParts, $aSql);

// IDs mit filtern falls übergeben
$this->setQueryIdDataByRef($aSelectedIds, $aQueryParts, $aSql);

$this->setParentGuiWherePartByRef($aQueryParts, $aSql);

// WHERE und GROUP BY an den SELECT anhängen
$sSql .= $aQueryParts['where'];
$sSql .= $sGroupPart;
$sSql .= $aQueryParts['having'];

if(!$bCheckWDSearch){
	
	$sOrderPart = " ORDER BY ".$this->_buildOrderByPart($this->_oGui->_aTableData['orderby'], $aSql);
	
	// Query um den ORDER BY Teil erweitern und den Spalten die sortierung zuweisen
	$this->setQueryOrderByDataByRef($sSql, $aOrderBy, $aColumnList, $sOrderPart);
	
}

if(!$bSkipLimit) {
	// LIMIT anhängen!
	$this->setQueryLimitDataByRef($iLimit, $iEnd, $sSql);
}

// DB Verbindung setzen
$this->_oDb = DB::getDefaultConnection();

#$sSQL = "SET SESSION query_cache_type = OFF;";
#DB::executeQuery($sSQL);

$aResult = $this->_getTableQueryData($sSql, $aSql, $iEnd, $iLimit);

// Daten aufbereiten, wenn sie nicht aus der Collection kommen
if(!($aResult['data'] instanceof Collection)){
	
	foreach((array)$aResult['data'] as $iKey => $aData){

		// Ext_TS_Inquiry::updateMatchingCache()
//		$aAlloccationData = explode('{=}', $aData['kaal_data']);
//		$aResult['data'][$iKey]['accommodation_fullnamelist']			= $aAlloccationData[0];
//		$aResult['data'][$iKey]['accommodation_room_fullnamelist']		= $aAlloccationData[1];
//		$aResult['data'][$iKey]['accommodation_fulllstreetlist']		= $aAlloccationData[2];
//		$aResult['data'][$iKey]['accommodation_fulladdressaddonlist'] = $aAlloccationData[3];
//		$aResult['data'][$iKey]['accommodation_fulllziplist']			= $aAlloccationData[4];
//		$aResult['data'][$iKey]['accommodation_fulllcitytlist']			= $aAlloccationData[5];
//		$aResult['data'][$iKey]['accommodation_fulllphonelist']			= $aAlloccationData[6];
//		$aResult['data'][$iKey]['accommodation_fulllphone2list']		= $aAlloccationData[7];
//		$aResult['data'][$iKey]['accommodation_fulllmobilelist']		= $aAlloccationData[8];
//		$aResult['data'][$iKey]['accommodation_fulllmaillist']			= $aAlloccationData[9];
//		$aResult['data'][$iKey]['accommodation_fulllcontactlist']		= $aAlloccationData[10];

		$aTransferData = explode('{||}', $aData['transfer_data']);
		foreach((array)$aTransferData as $aSingleTransferData){
			$aTransferTemp = explode('{|}', $aSingleTransferData);

			$sTransferType = '';
			if($aTransferTemp[0] == 1){
				$sTransferType = 'arrival';
			}elseif($aTransferTemp[0] == 2){
				$sTransferType = 'departure';
			}

			if(!empty($sTransferType)){
				$aResult['data'][$iKey][$sTransferType.'_inquiry_transfer_id']		= $aTransferTemp[1];
				$aResult['data'][$iKey][$sTransferType.'_date']						= $aTransferTemp[2];
				$aResult['data'][$iKey][$sTransferType.'_time']						= $aTransferTemp[3];
				$aResult['data'][$iKey][$sTransferType.'_airline']					= $aTransferTemp[4];
				$aResult['data'][$iKey][$sTransferType.'_flightnumber']				= $aTransferTemp[5];
				$aResult['data'][$iKey][$sTransferType.'_comment']					= $aTransferTemp[6];

				$aResult['data'][$iKey][$sTransferType.'_accommodation_confirmed']	= $aTransferTemp[7];
				$aResult['data'][$iKey][$sTransferType.'_agency_confirmed']			= $aTransferTemp[8];
				$aResult['data'][$iKey][$sTransferType.'_updated']					= $aTransferTemp[9];
				
				$aResult['data'][$iKey][$sTransferType.'_start']					= $aTransferTemp[10];
				$aResult['data'][$iKey][$sTransferType.'_end']						= $aTransferTemp[11];
				$aResult['data'][$iKey][$sTransferType.'_start_type']				= $aTransferTemp[12];
				$aResult['data'][$iKey][$sTransferType.'_end_type']					= $aTransferTemp[13];
			}
		}

		
		$aAccommodationData = explode(',', $aData['acc_times_fulllist']);
		
		$aAccommodationStarts = array();
		$aAccommodationEnds   = array();
		
		foreach($aAccommodationData as $sAccommodationData)
		{
			$aAccommodationDates = explode('{=}', $sAccommodationData);
			$aAccommodationStarts[] = $aAccommodationDates[0];
			$aAccommodationEnds[] = $aAccommodationDates[1];
		}
		
		#$aAccTimeData = explode('{=}', $aData['acc_times_fulllist']);
		$aResult['data'][$iKey]['acc_time_from_fulllist']				= implode(',', $aAccommodationStarts);
		$aResult['data'][$iKey]['acc_time_to_fulllist']					= implode(',', $aAccommodationEnds);



		$aCrsTimeData = explode('{=}', $aData['crs_times_fulllist']);
		$aResult['data'][$iKey]['crs_time_from_fulllist']				= $aCrsTimeData[0];
		$aResult['data'][$iKey]['crs_time_to_fulllist']					= $aCrsTimeData[1];

	}

}
