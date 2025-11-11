<?php

use \ElasticaAdapter\Facade\Elastica;
use \Elastica\Query;

/**
 * @TODO Redundanz mit Ext_Thebing_Examination_Autocomplete und Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry
 *
 * @deprecated
 */
class Ext_Thebing_Customer_Search {
	
	private $foundRows;
	
	public function getFoundRows() {
		return $this->foundRows;
	}
	
	public function search($aData, $iSchool = null, $iCustomerId = 0) {

		if(empty($iSchool)) {
			$iSchool = \Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$sSql = "
			SELECT
				`tc_c`.*,
				`tc_cn`.`number` `customerNumber`,
				GROUP_CONCAT(
					CONCAT(
						`tc_cd`.`type`, '{|}',
						`tc_cd`.`value`
					)
					SEPARATOR '{||}'
				) `customer_details`
			FROM
				`tc_contacts` `tc_c` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`tc_contacts_details` `tc_cd` ON
					`tc_cd`.`contact_id` = `tc_c`.`id` AND
					`tc_cd`.`active` = 1 LEFT JOIN
					(
						`ts_inquiries_to_contacts` `ts_itc` INNER JOIN
						`ts_inquiries` `ts_i` INNER JOIN
						`ts_inquiries_journeys` `ts_ij`
					) ON
						`ts_itc`.`contact_id` = `tc_c`.`id` AND
						`ts_itc`.`type` = 'traveller' AND
						`ts_i`.`id` = `ts_itc`.`inquiry_id` AND
						`ts_ij`.`inquiry_id` = `ts_i`.`id` AND
						`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_ij`.`active` = 1 /*LEFT JOIN
					`ts_enquiries_to_contacts` `ts_etc` ON
						`ts_etc`.`contact_id` = `tc_c`.`id`*/
			WHERE
				(
					`ts_itc`.`inquiry_id` IS NOT NULL /*OR
					`ts_etc`.`enquiry_id` IS NOT NULL*/
				)
		";
		$aSql = array();

		foreach((array)$aData as $sField => $mValue){

			$sSql .= " AND `tc_c`.#".$sField.' LIKE :_'.$sField." ";
			$aSql[$sField] = $sField;
			$aSql['_'.$sField] = $mValue.'%';

		}

		// wenn vorhanden dann aktuellen Kunden nicht auch finden
		if($iCustomerId > 0){
			$sSql .= " AND `tc_c`.`id` != :customerId ";
			$aSql['customerId'] = (int) $iCustomerId;
		}

		$sSql .= " GROUP BY `tc_c`.`id` ";

		$aSql['school_id'] = (int)$iSchool;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		foreach($aResult as $iKey => &$aUserData) {
			self::addCustomerData($aUserData['id'], $aUserData);
		}

		return $aResult;
	}

	protected static function addCustomerData($iUserId, array &$aUserData) {

		$oCustomer = Ext_TS_Inquiry_Contact_Traveller::getInstance($iUserId);
		
		$aUserData += $oCustomer->getShortArray();
		$aUserData['customerNumber'] = $oCustomer->getCustomerNumber();

		$aUserData['birthday'] = Ext_Thebing_Format::LocalDate($oCustomer->birthday, $iSchool);

		// E-Mails
		$aUserData['email'] = '';
		$aUserData['emails'] = [];
		$aEmails = $oCustomer->getEmailAddresses(true);
		if(!empty($aEmails)) {
			$aUserData['email'] = reset($aEmails)->email; // Anzeige
			$aUserData['emails'] = array_map(function(Ext_TC_Email_Address $oEmail) {
				return ['id' => $oEmail->id, 'email' => $oEmail->email];
			}, array_values($aEmails));
		}

		// Adresse
		$oAddress = $oCustomer->getAddress(null, false);
		if($oAddress) {
			$aUserData['c_address'] = self::_getAddressAsArray($oAddress);
		}

		// Bucher / Rechnungskontakt
		$inquiries = $oCustomer->getInquiries(true, true);

		if(!empty($inquiries)) {
			$booker = reset($inquiries)->getBooker();

			if($booker) {
				$aUserData['booker'] = $booker->getShortArray();
				$bookerAddress = $booker->getAddress(null, false);
				if($bookerAddress) {
					$aUserData['c_billing'] = self::_getAddressAsArray($bookerAddress);
				}
			}
		}
		
		// Details
		foreach($oCustomer->getDetails() as $oDetail) {
			$aUserData[$oDetail->type] = $oDetail->value;
		}

		// Visa
		$inquiry = $oCustomer->getLatestInquiry();
		$visa = $inquiry->getVisaData();
		if(
			$visa instanceof Ext_TS_Inquiry_Journey_Visa &&
			$visa->exist()
		) {
			
			$dateFields = [
				'passport_date_of_issue',
				'passport_due_date',
				'date_from',
				'date_until'
			];
			
			$visaData = [
				'servis_id' => $visa->servis_id,
				'tracking_number' => $visa->tracking_number,
				'status' => $visa->status,
				'required' => $visa->required,
				'passport_number' => $visa->passport_number,
			];
			
			foreach($dateFields as $dateField) {
				$visaData[$dateField] = Ext_Thebing_Format::LocalDate($visa->$dateField);
			}
			
			$aUserData['visa_data'] = $visaData;
			
		}
		unset($aUserData['customer_details']);
	}

	protected static function _getAddressAsArray($oAddress) {
		$aReturn = array(
			'country_iso'			=> $oAddress->country_iso,
			'state'					=> $oAddress->state,
			'company'				=> $oAddress->company,
			'address'				=> $oAddress->address,
			'address_addon'			=> $oAddress->address_addon,
			'address_additional'	=> $oAddress->address_additional,
			'zip'					=> $oAddress->zip,
			'city'					=> $oAddress->city			
		);
		
		return $aReturn;
	}


	public static function getBirthdays($iFrom = 0, $iTo = 0, $bOnlyWithCourse = false) {
	
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
	
		if($iFrom == 0){
			$iFrom = time();
			//$iFrom = strtotime('- 1 Week',$iFrom);
			$iFrom = mktime(0,0,0,date('m',$iFrom),date('d',$iFrom),date('Y',$iFrom));
		}

		if($iTo == 0){
			$iTo = time();
			$iTo = strtotime('+ 2 Week',$iFrom);
			$iTo = mktime(23,59,59,date('m',$iTo),date('d',$iTo),date('Y',$iTo));
		}

		$sWhereAddon = '';
		$aSql = array();

		$birthdayRequestedYear = "DATE_ADD(`tc_c`.`birthday`, INTERVAL (YEAR(:from) - YEAR(`tc_c`.`birthday`)) YEAR)";

		if($iSessionSchoolId > 0){
			$sWhereAddon .= " `ts_i_j`.`school_id` = :school_id AND ";
			$aSql['school_id'] = $iSessionSchoolId;
		}
		if($bOnlyWithCourse == true){
			$sWhereAddon .= "  ( 
									".$birthdayRequestedYear." BETWEEN
									`ts_i`.`service_from` AND `ts_i`.`service_until`
							) AND ";
		}

		$sSql = "	
			SELECT
				`tc_c`.`id`,
				`tc_c`.`lastname`,
				`tc_c`.`firstname`,
				
				getAge(
					`tc_c`.`birthday`
				) `age`,
				`tc_c`.`birthday`,
				".$birthdayRequestedYear." `birthday_requested_year`
			FROM
				`tc_contacts` `tc_c` JOIN
				`ts_inquiries_to_contacts` `tc_i_to_c` ON
					`tc_i_to_c`.`contact_id` = `tc_c`.`id` JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `tc_i_to_c`.`inquiry_id` AND
					`ts_i`.`active` = 1 JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
			WHERE
				`ts_i`.`canceled` = 0 AND 

				(
					(
						/* Das DAYOFYEAR muss auf den Geburtstag im angefragten Jahr ausgeführt werden, sonst gibt
						 es Probleme in Schaltjahren */
						DAYOFYEAR(".$birthdayRequestedYear.")+IF(DAYOFYEAR(:from)>DAYOFYEAR(".$birthdayRequestedYear."),1000,0)
					) BETWEEN 
						DAYOFYEAR(:from) AND 
						(
							DAYOFYEAR(:to)+IF(DAYOFYEAR(:from)>DAYOFYEAR(:to),1000,0)
						)
				) AND
				".$sWhereAddon."
				`tc_c`.`active` = 1
			GROUP BY
				`tc_c`.`id`
			ORDER BY
				`birthday`
		";

		$aSql['from'] = date('Y-m-d', $iFrom);
		$aSql['to'] = date('Y-m-d', $iTo);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		return $aResult;
	}

	public function fulltextSearch($input, $schoolId, $contactId = null, int $maxResults=4) {
		
		if(empty($schoolId)) {
			$schoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$mariaDbSearchMode = '';#IN BOOLEAN MODE';
		
		$sqlQuery = "
			SELECT
				AUTO_SQL_CALC_FOUND_ROWS 
				`tc_c`.*,
				`tc_cn`.`number` `customerNumber`,
				GROUP_CONCAT(
					CONCAT(
						`tc_cd`.`type`, '{|}',
						`tc_cd`.`value`
					)
					SEPARATOR '{||}'
				) `customer_details`,
				`tc_c`.`birthday`,
				`tc_e`.`email`,
				`tc_a`.`company`,
				GREATEST(
					MATCH(`tc_c`.`firstname`, `tc_c`.`lastname`) AGAINST(:search ".$mariaDbSearchMode."),
					MATCH(`tc_e`.`email`) AGAINST(:search ".$mariaDbSearchMode."),
					MATCH(`tc_a`.`company`) AGAINST(:search ".$mariaDbSearchMode."),
					MATCH(`tc_cn`.`number`) AGAINST(:search ".$mariaDbSearchMode.")
				) `score`
			FROM
				`tc_contacts` `tc_c` LEFT JOIN
				`tc_contacts_numbers` `tc_cn` ON
					`tc_cn`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`tc_contacts_details` `tc_cd` ON
					`tc_cd`.`contact_id` = `tc_c`.`id` AND
					`tc_cd`.`active` = 1 LEFT JOIN
				`tc_contacts_to_emailaddresses` `tc_cte` ON
					`tc_cte`.`contact_id` = `tc_c`.`id` LEFT JOIN
				`tc_emailaddresses` `tc_e` ON
					`tc_cte`.`emailaddress_id` = `tc_e`.`id` AND
					`tc_e`.`active` = 1 LEFT JOIN 
				`tc_contacts_to_addresses` `tc_cta` ON
					`tc_c`.`id` = `tc_cta`.`contact_id` LEFT JOIN 
				`tc_addresses` `tc_a` ON
					`tc_cta`.`address_id` = `tc_a`.`id` AND
					`tc_a`.`active` = 1
			WHERE
				`tc_c`.`active` = 1 AND
				`tc_c`.`lastname` != 'Anonym'
		";
		
		$sqlParams = [
			'search' => $input,
			'max_results' => $maxResults
		];

		$date = Ext_Thebing_Format::ConvertDate($input, $schoolId, true);

		$having = '';
		$orderBy = '';

		if(
			!empty($date) &&
			$input != $date
		) {
			
			$sqlQuery .= " AND `tc_c`.`birthday` = :date ";
			$sqlParams['date'] = $date;
			
		} else {

			$sqlQuery .= " AND (
					MATCH(`tc_c`.`firstname`, `tc_c`.`lastname`) AGAINST(:search ".$mariaDbSearchMode.") OR
					MATCH(`tc_e`.`email`) AGAINST(:search ".$mariaDbSearchMode.") OR
					MATCH(`tc_a`.`company`) AGAINST(:search ".$mariaDbSearchMode.") OR
					MATCH(`tc_cn`.`number`) AGAINST(:search ".$mariaDbSearchMode.")
					)";

			$having = 'HAVING 
				`score` > 1';
			$orderBy = 'ORDER BY 
				`score` DESC';
			
		}

		// wenn vorhanden dann aktuellen Kunden nicht auch finden
		if($contactId > 0){
			$sqlQuery .= " AND `tc_c`.`id` != :customerId ";
			$sqlParams['customerId'] = (int)$contactId;
		}

		$sqlQuery .= " 
			GROUP BY 
				`tc_c`.`id`
			".$having."
			".$orderBy."
			LIMIT :max_results
				";

		$sqlParams['school_id'] = (int)$schoolId;

		$result = DB::getPreparedQueryData($sqlQuery, $sqlParams);
//__out(\DB::getDefaultConnection()->getLastQuery());
//__out($result);
		$this->foundRows = \DB::fetchFoundRows();
		
		foreach($result as &$userData) {
			self::addCustomerData($userData['id'], $userData);
		}

		return $result;
	}
	
	/**
	 * @TODO Redundanz mit Ext_Thebing_Examination_Autocomplete
	 */
	static public function __fulltextSearch($sInput, $iSchoolId, $iContactId = null) {
		
		$oSearch = new Elastica(Elastica::buildIndexName('ts_inquiry'));
		$sSearch = $oSearch->escapeTerm($sInput);

		// Auf Datum testen
		$sDate = Ext_Thebing_Format::ConvertDate($sInput, $iSchoolId, true);

		if(!empty($sDate)) {

			$oQuery = new \Elastica\Query\Range('customer_birthday_original', [
				'gte' => $sDate
			]);
			$oSearch->addMustQuery($oQuery);
			$oQuery = new \Elastica\Query\Range('customer_birthday_original', [
				'lte' => $sDate
			]);
			$oSearch->addMustQuery($oQuery);
		
		} else {

			if(mb_substr($sSearch, -1) !== '*') {
				$sSearch .= '*';
			}
	
			$oQuery = new Query\Term();
			$oQuery->setTerm('school_id', $iSchoolId);
			$oSearch->addQuery($oQuery);

			$oBool = new Query\BoolQuery();
			$oBool->setMinimumShouldMatch(1);

			$oQuery = new Query\QueryString();
			$oQuery->setQuery($sSearch);
			$oQuery->setDefaultField('customer_number');
			$oQuery->setDefaultOperator('AND');
			$oBool->addShould($oQuery);

			$oQuery = new Query\QueryString();
			$oQuery->setQuery($sSearch);
			$oQuery->setDefaultField('customer_name');
			$oQuery->setDefaultOperator('AND');
			$oBool->addShould($oQuery);

			$oQuery = new Query\QueryString();
			$oQuery->setQuery($sSearch);
			$oQuery->setDefaultField('email_original');
			$oQuery->setDefaultOperator('AND');
			$oBool->addShould($oQuery);
			
		}

		if ($iContactId) {
			$oQuery = new Query\Term();
			$oQuery->setTerm('contact_id', $iContactId);
			$oSearch->addMustNotQuery($oQuery);
		}
		
		$oSearch->setFields(['_id', 'contact_id', 'customer_number', 'customer_name', 'created_original', 'email_original', 'customer_birthday']);
		$oSearch->addMustQuery($oBool);
		$oSearch->setSort('created_original');
		$oSearch->setLimit(100);
		$aResult = $oSearch->search();

		$aCustomers = [];
		foreach($aResult['hits'] as $aHit) {
			foreach($aHit['fields'] as &$mValue) {
				if(is_array($mValue)) {
					$mValue = reset($mValue);
				}
			}

			$aCustomerData = [];
			
			self::addCustomerData($aHit['fields']['contact_id'], $aCustomerData);
			
			$aCustomers[$aHit['fields']['contact_id']] = $aCustomerData;
			
		}

		return $aCustomers;
	}
	
}

