<?php

/**
 * Kein echter Check, sondern nur für Usage über Update-Server/Hotfix
 */
class Ext_TS_System_Checks_Enquiry_CheckCombinedOffers extends GlobalChecks {

	public function getTitle() {
		return '';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		$sql = "
			SELECT
				ts_e.id enquiry_id,
				ts_e.school_id,
				cdb2.ext_1 school_name,
				ts_e.created enquiry_created,
				ts_eo.id offer_id,
				ts_eo.created offer_created,
				tc_cn.number contact_number,
				GROUP_CONCAT(DISTINCT ts_eti.inquiry_id) inquiry_ids,
				COUNT(DISTINCT ts_ecc.combination_id) count_combinations_courses,
				COUNT(DISTINCT ts_eca.combination_id) count_combinations_accommodations,
				COUNT(DISTINCT ts_ect.combination_id) count_combinations_transfers,
				COUNT(DISTINCT ts_eci.combination_id) count_combinations_insurances
			FROM
				ts_enquiries_offers ts_eo INNER JOIN
				ts_enquiries ts_e ON
					ts_e.id = ts_eo.enquiry_id INNER JOIN
				customer_db_2 cdb2 ON
					cdb2.id = ts_e.school_id AND
					cdb2.active = 1 LEFT JOIN
				ts_enquiries_to_inquiries ts_eti ON
					ts_eti.enquiry_id = ts_e.id LEFT JOIN
				ts_enquiries_to_contacts ts_etc ON
					ts_etc.enquiry_id = ts_e.id AND
					ts_etc.type = 'booker' LEFT JOIN
				tc_contacts_numbers tc_cn ON
					tc_cn.contact_id = ts_etc.contact_id LEFT JOIN
				(
					ts_enquiries_offers_to_combinations_courses ts_eotcc INNER JOIN
					ts_enquiries_combinations_courses ts_ecc
				) ON
					ts_eotcc.offer_id = ts_eo.id AND
					ts_ecc.id = ts_eotcc.combination_course_id AND
					ts_ecc.active = 1 LEFT JOIN
				(
					ts_enquiries_offers_to_combinations_accommodations ts_eotca INNER JOIN
					ts_enquiries_combinations_accommodations ts_eca
				) ON
					ts_eotca.offer_id = ts_eo.id AND
					ts_eca.id = ts_eotca.combination_accommodation_id AND
					ts_eca.active = 1 LEFT JOIN
				(
					ts_enquiries_offers_to_combinations_transfers ts_eotct INNER JOIN
					ts_enquiries_combinations_transfers ts_ect
				) ON
					ts_eotct.offer_id = ts_eo.id AND
					ts_ect.id = ts_eotct.combination_transfer_id AND
					ts_ect.active = 1 LEFT JOIN
				(
					ts_enquiries_offers_to_combinations_insurances ts_eotci INNER JOIN
					ts_enquiries_combinations_insurances ts_eci
				) ON
					ts_eotci.offer_id = ts_eo.id AND
					ts_eci.id = ts_eotci.combination_insurance_id AND
					ts_eci.active = 1
			WHERE
				ts_eo.active = 1 AND
				ts_e.active = 1 AND
				YEAR(ts_eo.created) >= 2019
			GROUP BY
				ts_eo.id
			HAVING
				count_combinations_courses > 1 OR
				count_combinations_accommodations > 1 OR
				count_combinations_transfers > 1 OR
				count_combinations_insurances > 1
			ORDER BY
				ts_eo.created DESC
		";

		$rows = DB::getQueryRows($sql);

		if (empty($rows)) {
			$rows = 'No combined offers found since 2019!';
		}

		$curl = curl_init('https://update.fidelo.com/info.php?topic='.urlencode('Combined Offer Usage - '.\Util::getHost()));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Fidelo Update Service');

		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($rows));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

		curl_exec($curl);
		curl_close($curl);

		return true;
	}

}