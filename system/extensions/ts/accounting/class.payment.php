<?php

/**
 * @TODO Es darf keine zwei Klassen derselben Entität geben!
 * @see Ext_Thebing_Inquiry_Payment
 */
class Ext_TS_Accounting_Payment extends Ext_Thebing_Basic {
		
	protected $_sTable = 'kolumbus_inquiries_payments';
	
	protected $_sTableAlias = 'kip';

	/**
	 * @TODO Das muss alles in die getListQueryData() rein, wenn die Ext_Thebing_Inquiry_Payment mal umgestellt wird!
	 *
	 * @param array $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		
		$sLanguage = Ext_TC_System::getInterfaceLanguage();
		$sInvoiceTypes = Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('invoice_with_creditnotes_and_without_proforma');

		// 1024 Zeichen sind zu wenig für service_amount_data
		\Ext_TC_Util::setMySqlGroupConcatMaxLength();

		$aSqlParts['select'] .= ",
			`kpm`.`name` `method_name`,
			`tc_c`.`lastname`,
			`tc_c`.`firstname`,
			`tc_c`.`nationality`,
			`tc_c_n`.`number` `customer_number`,
			GROUP_CONCAT(DISTINCT `kid`.`document_number` SEPARATOR ', ') `document_numbers`,
			`ts_ij`.`school_id`,
			`ts_i`.`currency_id` `currency_id`,
			`ts_i`.`status_id`,
			`ts_g`.`short` `group_short`,
			`ts_g`.`name` `group`,
			`ka`.`ext_1` `agency`,
			`ka`.`ext_2` `agency_short`,
			`ka_c`.`name` `agency_category`,
			`ts_an`.`number` `agency_number`,
			`ts_ij`.`school_id` `school_id`,
			`kipo`.`amount_inquiry` `overpayment_amount`,
			`kid3`.`document_number` `receipt_number`,
			`ts_i`.`payment_method` `payment_method`,
			`ts_i`.`inbox`,
			`ts_i`.`sales_person_id`,
			`ts_ipg`.`number` `payment_grouping`,
			`ts_iti`.`total_course_weeks`,
			course_agg.course_names,
			course_agg.course_names_short,
			course_agg.course_dates_from,
			course_agg.course_dates_until,
			accom_agg.accommodation_names,
			accom_agg.accommodation_names_short,
			accom_agg.journey_accommodations,
			accom_agg.accommodation_dates_from,
			accom_agg.accommodation_dates_until,
			GROUP_CONCAT(DISTINCT CONCAT(`kidvi`.`id`, '{|}', `kidvi`.`type`, '{|}', `kipi`.`amount_inquiry`, '{|}', `kidvi`.`type_id`, '{|}', 0, '{|}', 0, '{|}', `kidvi`.`parent_id`) SEPARATOR '{||}') `service_amount_data`,
			IF(
				ts_i_payment.group_id != 0 AND ts_i_payment.id = ts_i.id,
				COALESCE(group_amounts.group_member_amount, 0),
				1
			) AS group_member_amount,
			`kipi_all`.`id` `any_kipi_id` /* Irgendeine ID für HAVING */
		";

		$aSqlParts['from'] .= " INNER JOIN
			/* Erst einmal auf Inquiry joinen, um an die mögliche Gruppe zu kommen */
			`ts_inquiries` `ts_i_payment` ON
				`ts_i_payment`.`id` = `kip`.`inquiry_id` LEFT JOIN
			/* Aufteilung auf alle möglichen Gruppenmitglieder */
			`ts_inquiries` `ts_i_groupmembers` ON
				`ts_i_payment`.`group_id` > 0 AND
				`ts_i_groupmembers`.`group_id` = `ts_i_payment`.`group_id` INNER JOIN
			/* Endgültiger Self-Join für Inquiry, damit IF-Bedingung nicht überall wiederholt werden muss (OR im Join verhindern) */
			`ts_inquiries` `ts_i` ON
				`ts_i`.`id` = IF(
		 			`ts_i_payment`.`group_id` = 0,
		 			`ts_i_payment`.`id`,
		 			`ts_i_groupmembers`.`id`
				) LEFT JOIN 
			(
				SELECT
					kip.id AS payment_id,
					kid_sub.entity_id AS inquiry_id,
					ABS(SUM(kipi_sub.amount_inquiry)) AS group_member_amount
				FROM 
					kolumbus_inquiries_payments kip JOIN 
					kolumbus_inquiries_payments_items kipi_sub ON 
						kipi_sub.payment_id = kip.id AND kipi_sub.active = 1 JOIN 
					kolumbus_inquiries_documents_versions_items kidvi_sub ON 
						kidvi_sub.id = kipi_sub.item_id JOIN 
					kolumbus_inquiries_documents kid_sub ON 
						kid_sub.latest_version = kidvi_sub.version_id
				WHERE 
					kid_sub.entity = 'Ext_TS_Inquiry'
				GROUP BY 
					kip.id, 
					kid_sub.entity_id
			) AS group_amounts ON 
				group_amounts.payment_id = kip.id AND 
				group_amounts.inquiry_id = ts_i.id INNER JOIN
		 	`kolumbus_inquiries_documents` `kid` ON
				`kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
				`kid`.`entity_id` = `ts_i`.`id` AND
				/*`kid`.`type` != 'creditnote' AND*/
				`kid`.`active` = 1 INNER JOIN
			`kolumbus_inquiries_documents_versions_items` `kidvi` ON
				`kidvi`.`version_id` = `kid`.`latest_version` AND
				`kidvi`.`active` = 1 AND
				`kidvi`.`onPdf` = 1 LEFT JOIN
			/* kipi-Join berücksichtigt nur Items der Inquiry, der das Payment gehört */
			`kolumbus_inquiries_payments_items` `kipi` ON
				`kipi`.`item_id` = `kidvi`.`id` AND
				`kipi`.`payment_id` = `kip`.`id` AND
				`kipi`.`active` = 1 LEFT JOIN
			/* kipi_all-Join hat alle Items des Payments, um zu prüfen, ob es überhaupt Items bei dieser Zahlung gibt */
			`kolumbus_inquiries_payments_items` `kipi_all` ON
				`kipi_all`.`payment_id` = `kip`.`id` AND
				`kipi_all`.`active` = 1 LEFT JOIN
			`kolumbus_groups` `ts_g` ON
				`ts_g`.`id` = `ts_i`.`group_id` AND
				`ts_g`.`active` = 1 LEFT JOIN
			`ts_inquiries_journeys` `ts_ij` ON
				`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
				`ts_ij`.`type` & '".Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
				`ts_ij`.`active` = 1 LEFT JOIN (
					SELECT ts_ij.inquiry_id,
						GROUP_CONCAT(DISTINCT ktc.name_".$sLanguage." SEPARATOR '{||}') AS course_names,
						GROUP_CONCAT(DISTINCT ktc.name_short SEPARATOR '{||}') AS course_names_short,
						GROUP_CONCAT(DISTINCT ts_i_j_c.`from` SEPARATOR '{||}') AS course_dates_from,
						GROUP_CONCAT(DISTINCT ts_i_j_c.`until` SEPARATOR '{||}') AS course_dates_until
					FROM ts_inquiries_journeys_courses ts_i_j_c
					JOIN ts_inquiries_journeys ts_ij ON ts_i_j_c.journey_id = ts_ij.id
					JOIN kolumbus_tuition_courses ktc ON ktc.id = ts_i_j_c.course_id
					WHERE ts_i_j_c.active = 1
					GROUP BY ts_ij.inquiry_id
			  ) AS course_agg ON course_agg.inquiry_id = ts_i.id 

			  LEFT JOIN (
					SELECT
						ts_ij.inquiry_id,
						GROUP_CONCAT(DISTINCT CONCAT(kar.name_".$sLanguage.", ' / ', kam.name_".$sLanguage.", ' / ', kac.name_".$sLanguage.") SEPARATOR '{||}') AS accommodation_names,
						GROUP_CONCAT(DISTINCT CONCAT(kar.short_".$sLanguage.", ' / ', kam.short_".$sLanguage.", ' / ', kac.short_".$sLanguage.") SEPARATOR '{||}') AS accommodation_names_short,
						GROUP_CONCAT(DISTINCT ts_i_j_a.id SEPARATOR '{||}') AS journey_accommodations,
						GROUP_CONCAT(DISTINCT ts_i_j_a.`from` SEPARATOR '{||}') AS accommodation_dates_from,
						GROUP_CONCAT(DISTINCT ts_i_j_a.`until` SEPARATOR '{||}') AS accommodation_dates_until
					FROM ts_inquiries_journeys_accommodations ts_i_j_a
					JOIN ts_inquiries_journeys ts_ij ON ts_ij.id = ts_i_j_a.journey_id
					LEFT JOIN kolumbus_accommodations_roomtypes kar ON kar.id = ts_i_j_a.roomtype_id AND kar.active = 1
					LEFT JOIN kolumbus_accommodations_meals kam ON kam.id = ts_i_j_a.meal_id AND kam.active = 1
					LEFT JOIN kolumbus_accommodations_categories kac ON kac.id = ts_i_j_a.accommodation_id AND kac.active = 1
					WHERE ts_i_j_a.active = 1
					GROUP BY ts_ij.inquiry_id
			) AS accom_agg ON accom_agg.inquiry_id = ts_i.id LEFT JOIN
			`ts_inquiries_to_contacts` `ts_i_to_c` ON
				`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_to_c`.`type` = 'traveller' LEFT JOIN
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
				`tc_c`.`active` = 1	LEFT JOIN
			`tc_contacts_numbers` `tc_c_n` ON
				`tc_c_n`.`contact_id` = `tc_c`.`id` LEFT JOIN
			`ts_companies` `ka` ON
				`ts_i`.`agency_id` = `ka`.`id` AND
				`ka`.`active` = 1 LEFT JOIN
			`kolumbus_agency_categories` `ka_c` ON
				`ka_c`.`id` = `ka`.`ext_39` AND
				`ka_c`.`active` = 1 LEFT JOIN
			`ts_companies_numbers` `ts_an` ON
				`ts_an`.`company_id` = `ka`.`id` LEFT JOIN
			`kolumbus_inquiries_payments_documents` `kipd` ON
				`kipd`.`payment_id` = `kip`.`id` LEFT JOIN
			`kolumbus_inquiries_documents` `kid3` ON
				`kid3`.`id` = `kipd`.`document_id` AND
				`kid3`.`active` = 1 AND
				`kid3`.`type` = 'receipt_customer' LEFT JOIN
			`kolumbus_inquiries_payments_overpayment` `kipo` ON
				`kipo`.`payment_id` = `kip`.`id` AND
				`kipo`.`active` = 1 LEFT JOIN
			/* Join für Filter, damit man immer alle Bezahlungen findet */
			`kolumbus_inquiries_documents` `kid_kipo` ON
				`kid_kipo`.`id` = `kipo`.`inquiry_document_id` AND
				`kid_kipo`.`active` = 1 LEFT JOIN
			`ts_inquiries_payments_groupings` `ts_ipg` ON
				`ts_ipg`.`id` = `kip`.`grouping_id` AND
				`ts_ipg`.`active` = 1 LEFT JOIN
			`kolumbus_payment_method` AS `kpm` ON
				`kip`.`method_id` = `kpm`.`id` LEFT JOIN
			`ts_inquiries_tuition_index` `ts_iti` ON
				`ts_iti`.`inquiry_id` = `ts_i`.`id` AND
				/* Erste Woche nehmen, da in allen Rows dasselbe in den Total-Spalten drin steht */
				`ts_iti`.`current_week` = 1
		";

		$aSqlParts['where'] .= " AND (
				/* Wenn die Payment keine Items (Inquiry-bezogen!) hat (Refunds, Overpayments), muss für diese Zahlung eine Zuweisung hergezaubert werden */
				`kipi`.`id` IS NOT NULL OR
				`ts_i_payment`.`id` = `ts_i`.`id`
			) AND
			/* Im System schwirren Zahlungen mit 0er-Beträgen rum, die auch keinerlei Zuweisung haben (@TODO: Sollte man mal korrigieren und Einträge löschen) */
			ABS(`kip`.`amount_inquiry`) > 0 AND
			`kid`.`type` IN ( ".$sInvoiceTypes." )
		";

		$aSqlParts['groupby'] = "
			`kip`.`id`,
			`ts_i`.`id` /* Nach Inquiry-ID gruppieren, damit nach Gruppenmitgliedern aufgeteilt wird */
		";

		$aSqlParts['having'] .= "
			/*
			 * Entweder hat das Payment gar keine Items (Refund, Overpayment) oder die Inquiry der Gruppe muss von der Payment betroffen gewesen sein.
			 * Dieser Fall ist dann relevant, wenn eine Inquiry zum Bezahlen selektiert wurde, auf die bei dieser Bezahlung kein Betrag einem Item zugewiesen wurde
			 * und der Inquiry demnach kein Payment-Item gehört (oder direkt bei Refund und Overpayment, da gar keine Payment-Items vorhanden). #.7735-2
			 */
			`any_kipi_id` IS NULL OR
			`group_member_amount` > 0
		";
		
	}
	
	public static function getFilterDates(){
		
		$oWdDate = new WDDate();
		$oWdDate->sub(7, WDDate::DAY);
		$sFilterStart = $oWdDate->get(WDDate::DB_DATE);
		$oWdDate->set(time(), WDDate::TIMESTAMP);
		$sFilterEnd = $oWdDate->get(WDDate::DB_DATE);

		$aBack = array('from' => $sFilterStart, 'until' => $sFilterEnd);
		return $aBack;
	}
	
}
