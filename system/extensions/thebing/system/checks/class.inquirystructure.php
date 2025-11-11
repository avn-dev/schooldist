<?php


class Ext_Thebing_System_Checks_InquiryStructure extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Change Inquiry Structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Convert inquiry to new contacts structure';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600 * 4);
		ini_set("memory_limit", '1024M');

		$aError = array();
		$aInfo = array();
		
		// Tabelle MUSS vorhanden sein!!!!!
		$bExistsOldTable = Ext_Thebing_Util::checkTableExists('__old_customer_table');
		
		if(!$bExistsOldTable){
			$aError['no_table_found'] = 'Keine Tabelle gefunden (Inquiry Check)';
			self::report($aError, $aInfo);
			return true;
		}
		
		$sInquiryBackupTable = '__old_kolumbus_inquiries';
		
		$bExistsOldBackup = Ext_Thebing_Util::checkTableExists($sInquiryBackupTable);
		
		//wenn weder backup noch die eigentliche Tabelle vorhanden, dann nichts ausführen
		$sInquiryTable = 'kolumbus_inquiries';
		
		if($bExistsOldBackup){
			$sInquiryTable = $sInquiryBackupTable;
			
			// Tabellen droppen
			$this->dropTables(); 
		}
		
		
		$iClientId = (int)Ext_Thebing_System::getClientId();

		$this->_createTables();
		
		// Alle Inquiries holen zum Client die schon einen Kunden haben.
		
		$sSql = "SELECT
						`ki`.*,
						IFNULL(`ts_e_to_c`.`enquiry_id`, 0) `enquiry_id`,
						`ts_s_to_p`.`productline_id` `productline_id`,	
						`cdb1`.`ext_43`,
						`cdb1`.`free_course`,
						`cdb1`.`free_accommodation`,
						`cdb1`.`free_course_fee`,
						`cdb1`.`free_accommodation_fee`,
						`cdb1`.`free_transfer`,
						`cdb1`.`free_all`,
						
						`cdb1`.`reg_form`,
						`cdb1`.`ip`
					FROM
						#table `ki` INNER JOIN
						`tc_contacts` `tc_c` ON
							`tc_c`.`id` = `ki`.`idUser` INNER JOIN
						`__old_customer_table` `cdb1` ON
							`cdb1`.`id` = `ki`.`idUser` INNER JOIN
						`ts_productlines_schools` `ts_s_to_p` ON
							`ts_s_to_p`.`school_id` =  `ki`.`crs_partnerschool` LEFT JOIN
						`ts_enquiries_to_contacts` `ts_e_to_c` ON
							`ts_e_to_c`.`contact_id` = `tc_c`.`id` AND
							`ts_e_to_c`.`type` = 'booker'
					WHERE
						`ki`.`office` = :client_id AND
						`ki`.`crs_partnerschool` > 0 AND
						(
							`cdb1`.`ext_39` = 0 OR
							(
								`cdb1`.`ext_39` = 1 AND
								`ki`.`active` = 1
							)
						)
				";
		
		$aSql = array(
				'table' => $sInquiryTable,
				'client_id' => $iClientId
		);
		
		$oDB = DB::getDefaultConnection();
		
		$aResult = $oDB->getCollection($sSql, $aSql);
		
		#$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
		foreach($aResult as $aRowData){

			foreach($aRowData as $sKey => $sValue){ 
				if(is_null($sValue)){
					$aRowData[$sKey] = '';
				}
			} 
			
			$aInsertDataInquiry = array(
				'id'								=> (int)$aRowData['id'],
				'changed'							=> $aRowData['changed'],
				'created'							=> $aRowData['created'],
				'active'							=> $aRowData['active'],
				'creator_id'						=> (int)$aRowData['creator_id'],
				'editor_id'							=> (int)$aRowData['user_id'],
				'inbox'								=> $aRowData['ac_office'],
				'group_id'							=> (int)$aRowData['group_id'],
				'agency_id'							=> (int)$aRowData['idAgency'],
				'currency_id'						=> $aRowData['idCurrency'],
				'confirmed'							=> $aRowData['confirmed'],
				
				'amount'							=> $aRowData['amount'],
				'amount_payed'						=> $aRowData['amount_payed'],
				'amount_initial'					=> $aRowData['amount_inital'],
				'amount_credit'						=> $aRowData['amount_credit'],
				'amount_payed_prior_to_arrival'		=> $aRowData['amount_payed_prior_to_arrival'],
				'amount_payed_at_school'			=> $aRowData['amount_payed_at_school'],
				'amount_payed_refund'				=> $aRowData['amount_payed_refund'],
				
				'changed_amount'					=> $aRowData['changed_amount'],
				'changed_initial_amount'			=> $aRowData['changed_inital_amount'],
				
				'canceled'							=> $aRowData['canceled'],
				'canceled_amount'					=> $aRowData['canceled_amount'],
				
				'tsp_transfer'						=> $aRowData['tsp_transfer'],
				'tsp_comment'						=> $aRowData['tsp_comment'],
				'transfer_data_requested'			=> $aRowData['tspInfoRequestSent'],
				
				'referer_id'						=> $aRowData['referer_select'],
				'status_id'							=> (int)$aRowData['status_id'],
				'promotion'							=> $aRowData['promotion'],
				'firstpay'							=> $aRowData['firstpay'],
				'finalpay'							=> $aRowData['finalpay'],
				'voucher_id'						=> (int)$aRowData['voucher_id'],
				'social_security_number'			=> $aRowData['sozialversicherungsnummer'],
				'payment_method'					=> $aRowData['payment_methode'],
				'payment_method_comment'			=> $aRowData['payment_method_comment'],
				'profession'						=> $aRowData['profession'],
				'agency_contact_id'					=> (int)$aRowData['agency_contact_id'],
				'has_invoice'						=> $aRowData['has_invoice'],
				'has_proforma'						=> $aRowData['has_proforma'],
				'has_invoice'						=> $aRowData['has_invoice'],
				'has_proforma'						=> $aRowData['has_proforma'],
				
				'reg_form'							=> $aRowData['reg_form'],
				'ip'								=> $aRowData['ip'],

			);
			
			$rRes = DB::insertData('ts_inquiries', $aInsertDataInquiry);
			
			// AC Netto füllen
			if(
				$aRowData['bank'] > 0 ||	
				$aRowData['payment'] > 0 ||
				$aRowData['netto_amount'] != 0 ||
				$aRowData['netto_pay_until'] != '0000-00-00' ||
				$aRowData['netto_payed'] != '0' ||
				$aRowData['netto_payed_date'] != '0000-00-00'
			){
				$aInsertDataAc = array(
					'inquiry_id'						=> (int)$aRowData['id'],
					'netto_amount'						=> $aRowData['netto_amount'],
					'netto_pay_until'					=> $aRowData['netto_pay_until'],
					'netto_payed'						=> $aRowData['netto_payed'],
					'netto_payed_date'					=> $aRowData['netto_payed_date'],
					'bank'								=> $aRowData['bank'],
					'payment'							=> $aRowData['payment'],
				);
				$rRes = DB::insertData('ts_inquiries_ac_data', $aInsertDataAc);
			}


			// Matching Data füllen
			if(
				$aRowData['matching_cats'] != 0 ||
				$aRowData['matching_dogs'] != 0 ||
				$aRowData['matching_pets'] != 0 ||
				$aRowData['matching_smoker'] != 0 ||
				$aRowData['matching_distance_to_school'] != 0 ||
				$aRowData['matching_air_conditioner'] != 0 ||
				$aRowData['matching_bath'] != 0 ||
				$aRowData['matching_familie_age'] != 0 ||
				strlen($aRowData['matching_residential_area']) > 0 ||
				$aRowData['matching_familie_kids'] != 0 ||
				$aRowData['matching_internet'] != 0 ||
				$aRowData['acc_vegetarian'] != 0 ||
				$aRowData['acc_muslim_diat'] != 0 ||
				$aRowData['acc_smoker'] != 0 ||
				strlen($aRowData['acc_allergies']) > 0 ||
				strlen($aRowData['acc_comment']) > 0 ||
				strlen($aRowData['acc_comment2']) > 0 
			){
				$aInsertDataMatching = array(
					'inquiry_id'						=> (int)$aRowData['id'],
					'cats'								=> (int)$aRowData['matching_cats'],
					'dogs'								=> (int)$aRowData['matching_dogs'],
					'pets'								=> (int)$aRowData['matching_pets'],
					'smoker'							=> (int)$aRowData['matching_smoker'],
					'distance_to_school'				=> (int)$aRowData['matching_distance_to_school'],
					'air_conditioner'					=> (int)$aRowData['matching_air_conditioner'],
					'bath'								=> (int)$aRowData['matching_bath'],
					'family_age'						=> (int)$aRowData['matching_familie_age'],
					'residential_area'					=> $aRowData['matching_residential_area'],
					'family_kids'						=> (int)$aRowData['matching_familie_kids'],
					'internet'							=> (int)$aRowData['matching_internet'],

					'acc_vegetarian'					=> (int)$aRowData['acc_vegetarian'],
					'acc_muslim_diat'					=> (int)$aRowData['acc_muslim_diat'],
					'acc_smoker'						=> (int)$aRowData['acc_smoker'],

					'acc_allergies'						=> $aRowData['acc_allergies'],	
					'acc_comment'						=> $aRowData['acc_comment'],
					'acc_comment2'						=> $aRowData['acc_comment2']
				);
				$rRes = DB::insertData('ts_inquiries_matching_data', $aInsertDataMatching);
			}
			
			

			
			// Zwischentabelle Contact <-> Inquiry
			$aInsertDataInquiryContact = array(
				'inquiry_id'						=> $aRowData['id'],
				'contact_id'						=> $aRowData['idUser'],
				'type'								=> 'booker',
			);
			$rRes = DB::insertData('ts_inquiries_to_contacts', $aInsertDataInquiryContact);
			
			// Contact <-> Inquiry
			$aInsertDataInquiryContact = array(
				'inquiry_id'						=> $aRowData['id'],
				'contact_id'						=> $aRowData['idUser'],
				'type'								=> 'traveller',
			);
			$rRes = DB::insertData('ts_inquiries_to_contacts', $aInsertDataInquiryContact);

			// Zwischentabelle Inquiry <-> Enquiry
			if(
				$aRowData['enquiry_id'] > 0 &&
				$aRowData['active'] == 1 //wenn active=0, dann wurde die anfrage noch nicht umgewandelt
			){
				$aInsertDataInquiryEnquiry = array(
					'inquiry_id'					=> $aRowData['id'],
					'enquiry_id'					=> $aRowData['enquiry_id'],
				);
				$rRes = DB::insertData('ts_enquiries_to_inquiries', $aInsertDataInquiryEnquiry);
			}
			
			// Inquiry Journeys füllen
			$aInsertDataInquiryJourney = array(
				'changed'							=> $aRowData['changed'],
				'created'							=> $aRowData['created'],
				'active'							=> $aRowData['active'],
				'creator_id'						=> $aRowData['creator_id'],
				'editor_id'							=> $aRowData['user_id'],
				'inquiry_id'						=> $aRowData['id'],
				'productline_id'					=> $aRowData['productline_id'],
				'school_Id'							=> $aRowData['crs_partnerschool'],
			);
			$iJourneyId = DB::insertData('ts_inquiries_journeys', $aInsertDataInquiryJourney);

						
					
			// Gruppen Details speichern
			self::saveJourneysTravellerDetail($iJourneyId, $aRowData['idUser'], 'guide', $aRowData['ext_43']);
			self::saveJourneysTravellerDetail($iJourneyId, $aRowData['idUser'], 'free_course', $aRowData['free_course']);
			self::saveJourneysTravellerDetail($iJourneyId, $aRowData['idUser'], 'free_accommodation', $aRowData['free_accommodation']);
			self::saveJourneysTravellerDetail($iJourneyId, $aRowData['idUser'], 'free_course_fee', $aRowData['free_course_fee']);
			self::saveJourneysTravellerDetail($iJourneyId, $aRowData['idUser'], 'free_accommodation_fee', $aRowData['free_accommodation_fee']);
			self::saveJourneysTravellerDetail($iJourneyId, $aRowData['idUser'], 'free_transfer', $aRowData['free_transfer']);
			self::saveJourneysTravellerDetail($iJourneyId, $aRowData['idUser'], 'free_all', $aRowData['free_all']);
	
			
			// Visum Daten füllen
			$bSaveVisa = false;
			if(
				strlen($aRowData['visum_servis_id']) > 0 ||
				strlen($aRowData['visum_tracking_number']) > 0 ||
				$aRowData['visum_status'] > 0 ||
				strlen($aRowData['visum_passport_number']) > 0 ||
				$aRowData['visum_passport_date_of_issue'] != '0000-00-00' ||
				$aRowData['visum_passport_due_date'] != '0000-00-00' ||
				$aRowData['visum_date_from'] != '0000-00-00' ||
				$aRowData['visum_date_until'] != '0000-00-00'
			){
				$bSaveVisa = true;
			}
			
			if($aRowData['visum_required'] == 1){
				$bSaveVisa = true;
			}
			
			if($bSaveVisa){
				
				$aInsertDataVisa = array(
					'journey_id'						=> $iJourneyId,
					'traveller_id'						=> $aRowData['idUser'],
					'servis_id'							=> $aRowData['visum_servis_id'],
					'tracking_number'					=> $aRowData['visum_tracking_number'],
					'status'							=> $aRowData['visum_status'],
					'required'							=> $aRowData['visum_required'],
					'passport_number'					=> $aRowData['visum_passport_number'],
					'passport_date_of_issue'			=> $aRowData['visum_passport_date_of_issue'],
					'passport_due_date'					=> $aRowData['visum_passport_due_date'],
					'date_from'							=> $aRowData['visum_date_from'],
					'date_until'						=> $aRowData['visum_date_until']
				);

				$rRes = DB::insertData('ts_journeys_travellers_visa_data', $aInsertDataVisa);
			}
			
			

			
			
			// Emergency Contact
			if(
				!empty($aRowData['emergency_name']) ||
				!empty($aRowData['emergency_phone']) ||
				!empty($aRowData['emergency_email'])
			){
				$aInsertDataEmergency = array(
					#'id'						=> $aRowData['id'],
					'changed'					=> $aRowData['changed'],
					'created'					=> $aRowData['created'],
					'active'					=> $aRowData['active'],
					'creator_id'				=> $aRowData['creator_id'],
					'editor_id'					=> $aRowData['user_id'],
					'lastname'					=> $aRowData['emergency_name'],
				);

				$iEmergencyContactId = (int)DB::insertData('tc_contacts', $aInsertDataEmergency);

				if($iEmergencyContactId > 0){
					if(!empty($aRowData['emergency_phone'])){
						self::saveContactDetail($iEmergencyContactId, 'phone_private'	, $aRowData['emergency_phone']);
					}
					
					if(!empty($aRowData['emergency_email'])){
						// Contact Mail Adresse
						$aContactMail = array(
							'changed'			=> $aRowData['changed'],
							'created'			=> $aRowData['created'],
							'active'			=> $aRowData['active'],
							'creator_id'		=> $aRowData['creator_id'],
							'editor_id'			=> $aRowData['user_id'],
							'email'				=> $aRowData['emergency_email'],
							'master'			=> 1,
						);
						
						$iContactMailId = (int)DB::insertData('tc_emailaddresses', $aContactMail);
						
						$aContactToMail = array(
							'contact_id'		=> (int)$iEmergencyContactId,
							'emailaddress_id'	=> (int)$iContactMailId
						);		
						DB::insertData('tc_contacts_to_emailaddresses', $aContactToMail);
						
					}
					
					// Contacts <-> Inquiry
					$aInsertDataInquiryEmergencyContact = array(
						'inquiry_id'						=> $aRowData['id'],
						'contact_id'						=> $iEmergencyContactId,
						'type'								=> 'emergency',
					);
					$rRes = DB::insertData('ts_inquiries_to_contacts', $aInsertDataInquiryEmergencyContact);
					
					

				}
			}
			
			
		}

		$bExistsInquiryTable = Ext_Thebing_Util::checkTableExists('kolumbus_inquiries');
		
		if($bExistsInquiryTable){
			$sSql = "
					RENAME TABLE 
							`kolumbus_inquiries` 
						TO 
							`" . $sInquiryBackupTable . "`
			";

			DB::executeQuery($sSql);
		}
		
		
		
		
		return true;
	}

	
	public static function report($aError, $aInfo){
		
		$oMail = new WDMail();
		$oMail->subject = 'Inquiry Structure';
		
		$sText = '';
		$sText = $_SERVER['HTTP_HOST']."\n\n";
		$sText .= date('Y-m-d H:i:s')."\n\n";
		$sText .= print_r($aInfo, 1)."\n\n";
		
		if(!empty($aError)){
			$sText .= '------------ERROR------------';
			$sText .= "\n\n";
			$sText .= print_r($aError, 1);
		}
		
		$oMail->text = $sText;

		$oMail->send(array('m.durmaz@thebing.com'));
				
	}
	
	protected function _createTables()
	{
		

		
		//Inquiry Tabelle
		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_inquiries` (
			  `id` mediumint(9) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(1) NOT NULL default '1',
			  `creator_id` mediumint(9) NOT NULL,
			  `editor_id` mediumint(9) NOT NULL,
			  `inbox` varchar(50) NOT NULL,
			  `group_id` mediumint(9) NOT NULL,
			  `agency_id` mediumint(9) NOT NULL,
			  `currency_id` mediumint(9) NOT NULL,
			  `confirmed` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `amount` decimal(15,5) NOT NULL,
			  `amount_payed` decimal(15,5) NOT NULL,
			  `amount_initial` decimal(15,5) NOT NULL,
			  `amount_credit` decimal(15,5) NOT NULL,
			  `amount_payed_prior_to_arrival` decimal(15,5) NOT NULL,
			  `amount_payed_at_school` decimal(15,5) NOT NULL,
			  `amount_payed_refund` decimal(15,5) NOT NULL,
			  `changed_amount` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `changed_initial_amount` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `canceled` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `canceled_amount` decimal(15,5) NOT NULL,
			  `tsp_transfer` varchar(50) NOT NULL,
			  `tsp_comment` text NOT NULL,
			  `transfer_data_requested` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `referer_id` mediumint(9) NOT NULL,
			  `status_id` mediumint(9) NOT NULL,
			  `promotion` varchar(16) NOT NULL,
			  `firstpay` date NOT NULL,
			  `finalpay` date NOT NULL,
			  `voucher_id` varchar(255) NOT NULL,
			  `social_security_number` varchar(255) NOT NULL,
			  `payment_method` tinyint(1) NOT NULL,
			  `payment_method_comment` text NOT NULL,
			  `profession` text NOT NULL,
			  `agency_contact_id` mediumint(9) NOT NULL,
			  `has_invoice` tinyint(1) NOT NULL,
			  `has_proforma` tinyint(1) NOT NULL,
			  `reg_form` tinyint(1) NOT NULL default '0',
			  `ip` varchar(15) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `active` (`active`),
			  KEY `inbox` (`inbox`),
			  KEY `agency_contact_id` (`agency_contact_id`),
			  KEY `creator_id` (`creator_id`),
			  KEY `editor_id` (`editor_id`),
			  KEY `agency_id` (`agency_id`),
			  KEY `finalpay` (`finalpay`),
			  KEY `created` (`created`),
			  KEY `canceled` (`canceled`),
			  KEY `referer_id` (`referer_id`),
			  KEY `status_id` (`status_id`),
			  KEY `ki_1` (`active`,`created`),
			  KEY `currency_id` (`currency_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";

		DB::executeQuery($sSql);
		
		// AC Tabelle
		$sSql = "CREATE TABLE IF NOT EXISTS `ts_inquiries_ac_data` (
			  `id` mediumint(9) NOT NULL auto_increment,
			  `inquiry_id` mediumint(9) NOT NULL,
			  `netto_amount` decimal(15,5) NOT NULL,
			  `netto_pay_until` date NOT NULL,
			  `netto_payed` tinyint(1) NOT NULL,
			  `netto_payed_date` date NOT NULL,
			  `bank` mediumint(9) NOT NULL,
			  `payment` mediumint(9) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `inquiry_id` (`inquiry_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		
		DB::executeQuery($sSql);

		// Matching Tabelle
		$sSql = "CREATE TABLE IF NOT EXISTS `ts_inquiries_matching_data` (
			  `id` mediumint(9) NOT NULL auto_increment,
			  `inquiry_id` mediumint(9) NOT NULL,
			  `cats` tinyint(1) NOT NULL,
			  `dogs` tinyint(1) NOT NULL,
			  `pets` tinyint(1) NOT NULL,
			  `smoker` tinyint(1) NOT NULL,
			  `distance_to_school` tinyint(1) NOT NULL,
			  `air_conditioner` tinyint(1) NOT NULL,
			  `bath` tinyint(1) NOT NULL,
			  `family_age` tinyint(1) NOT NULL,
			  `residential_area` varchar(255) NOT NULL,
			  `family_kids` tinyint(1) NOT NULL,
			  `internet` tinyint(1) NOT NULL,
			  `acc_vegetarian` tinyint(1) NOT NULL,
			  `acc_muslim_diat` tinyint(1) NOT NULL,			 
			  `acc_smoker` tinyint(1) NOT NULL,
			  `acc_allergies` text NOT NULL,
			  `acc_comment` text NOT NULL,
			  `acc_comment2` text NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `inquiry_id` (`inquiry_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		
		DB::executeQuery($sSql);
		
		// Visa Table
		$sSql = "CREATE TABLE IF NOT EXISTS `ts_journeys_travellers_visa_data` (
			  `id` mediumint(9) NOT NULL auto_increment,
			  `journey_id` mediumint(9) NOT NULL,
			  `traveller_id` mediumint(9) NOT NULL,
			  `servis_id` varchar(255) NOT NULL,
			  `tracking_number` varchar(255) NOT NULL,
			  `status` mediumint(9) NOT NULL,
			  `required` tinyint(1) NOT NULL,
			  `passport_number` varchar(255) NOT NULL,
			  `passport_date_of_issue` date NOT NULL,
			  `passport_due_date` date NOT NULL,
			  `date_from` date NOT NULL,
			  `date_until` date NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `journey_id` (`journey_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		
		DB::executeQuery($sSql);
		
		//Contact <-> Inquiry
		$sSql = "CREATE TABLE IF NOT EXISTS `ts_inquiries_to_contacts` (
			  `inquiry_id` mediumint(9) NOT NULL,
			  `contact_id` mediumint(9) NOT NULL,
			  `type` varchar(50) NOT NULL default 'traveller',
			  PRIMARY KEY  (`inquiry_id`,`contact_id`,`type`),
			  KEY `type` (`type`),
			  KEY `inquiry_id` (`inquiry_id`,`type`),
			  KEY `contact_id` (`contact_id`,`type`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		
		DB::executeQuery($sSql);

		//Inquiry <-> Enquiry
		
		$sSql = "CREATE TABLE IF NOT EXISTS `ts_enquiries_to_inquiries` (
			  `enquiry_id` mediumint(9) NOT NULL,
			  `inquiry_id` mediumint(9) NOT NULL,
			  PRIMARY KEY  (`enquiry_id`,`inquiry_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		
		DB::executeQuery($sSql);
		
		// Journeys
		$sSql = "CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys` (
			  `id` mediumint(9) NOT NULL auto_increment,
			  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
			  `active` tinyint(1) NOT NULL default '1',
			  `creator_id` mediumint(9) NOT NULL,
			  `editor_id` mediumint(9) NOT NULL,
			  `inquiry_id` mediumint(9) NOT NULL,
			  `productline_id` mediumint(9) NOT NULL,
			  `school_id` mediumint(9) NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `active` (`active`),
			  KEY `creator_id` (`creator_id`),
			  KEY `editor_id` (`editor_id`),
			  KEY `inquiry_id` (`inquiry_id`),
			  KEY `productline_id` (`productline_id`),
			  KEY `school_id` (`school_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		
		DB::executeQuery($sSql);
	
		// Jouney Traveller Details
		$sSql = "CREATE TABLE IF NOT EXISTS `ts_journeys_travellers_detail` (
			  `id` mediumint(9) NOT NULL auto_increment,
			  `journey_id` mediumint(9) NOT NULL,
			  `traveller_id` mediumint(9) NOT NULL,
			  `type` varchar(50) NOT NULL,
			  `value` text NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `journey_id` (`journey_id`),
			  KEY `traveller_id` (`traveller_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
		";
		
		DB::executeQuery($sSql);
		
	
		
		// Emergency Contacts löschen
		$iClientId = (int)Ext_Thebing_System::getClientId();
		
		$sSql = "SELECT
						`ts_i_to_c`.`contact_id`,
						IFNULL(`tc_c_to_e`.`emailaddress_id`, 0) `emailaddress_id`
					FROM
						`ts_inquiries_to_contacts` `ts_i_to_c` LEFT JOIN
						`tc_contacts_to_emailaddresses` `tc_c_to_e` ON
							`tc_c_to_e`.`contact_id` = `ts_i_to_c`.`contact_id`
					WHERE
						`ts_i_to_c`.`type` = 'emergency'
				";
		
		$aSql = array(
				'client_id' => $iClientId
		);
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$sSql = "
			DELETE FROM
				#table
			WHERE
				#column IN(:ids)
		";
		
	
	
		foreach((array)$aResult as $aData){
			// Emergency Contact löschen
			$aSql['table'] = 'tc_contacts';
			$aSql['column'] = 'id';
			$aSql['ids'] = array($aData['contact_id']);
			DB::executePreparedQuery($sSql, $aSql);
			
			if($aData['emailaddress_id'] > 0){
				// zwischhentabelle löschen
				$aSql['table'] = 'tc_contacts_to_emailaddresses';
				$aSql['column'] = 'contact_id';
				$aSql['ids'] = array($aData['contact_id']);
				DB::executePreparedQuery($sSql, $aSql);

				// Emails löschen
				$aSql['table'] = 'tc_emailaddresses';
				$aSql['column'] = 'id';
				$aSql['ids'] = array($aData['emailaddress_id']);
				DB::executePreparedQuery($sSql, $aSql);
				
				// Inquiry <-> Contacts wird eh schon gelöscht
			}

			
		}

		// Tabellen leeren
		$sSql = "TRUNCATE TABLE `ts_inquiries`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_inquiries_ac_data`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_inquiries_matching_data`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_journeys_travellers_visa_data`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_inquiries_to_contacts`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_enquiries_to_inquiries`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_inquiries_journeys`";
		DB::executeQuery($sSql);
		$sSql = "TRUNCATE TABLE `ts_journeys_travellers_detail`";
		DB::executeQuery($sSql);

	}
	
	
	public static function saveContactDetail($iContactId, $sType, $sValue){
		
		if(
			empty($sValue) ||
			empty($sType)
		){
			return;
		}
		
		$aData = array(
					'type' => $sType,
					'value' => $sValue,
					'active' => 1,
					'contact_id' => (int)$iContactId
		);
		
		return DB::insertData('tc_contacts_details', $aData);
	}
	
	public static function saveJourneysTravellerDetail($iJourneyId, $iTravellerId, $sType, $sValue){
		
		if(
			empty($sValue) ||
			empty($sType)
		){
			return;
		}
		
		$aData = array(
					'type' => $sType,
					'value' => $sValue,
					'journey_id' => (int)$iJourneyId,
					'traveller_id' => (int)$iTravellerId
		);
		
		return DB::insertData('ts_journeys_travellers_detail', $aData);
	}
	
	public function dropTables(){
		// Alles löschen
 
		$bCheck = Ext_Thebing_Util::checkTableExists('ts_inquiries');		
		if($bCheck){
			$sSql = "DROP TABLE `ts_inquiries`;";
			DB::executeQuery($sSql);
		}
		
		$bCheck = Ext_Thebing_Util::checkTableExists('ts_inquiries_ac_data');		
		if($bCheck){
			$sSql = "DROP TABLE `ts_inquiries_ac_data`;";
			DB::executeQuery($sSql);
		}
		
		$bCheck = Ext_Thebing_Util::checkTableExists('ts_inquiries_journeys');		
		if($bCheck){
			$sSql = "DROP TABLE `ts_inquiries_journeys`;";
			DB::executeQuery($sSql);
		}
		
		$bCheck = Ext_Thebing_Util::checkTableExists('ts_inquiries_matching_data');		
		if($bCheck){
			$sSql = "DROP TABLE `ts_inquiries_matching_data`;";
			DB::executeQuery($sSql);
		}
		
		$bCheck = Ext_Thebing_Util::checkTableExists('ts_inquiries_to_contacts');		
		if($bCheck){
			$sSql = "DROP TABLE `ts_inquiries_to_contacts`;";
			DB::executeQuery($sSql);
		}
		
		$bCheck = Ext_Thebing_Util::checkTableExists('ts_enquiries_to_inquiries');		
		if($bCheck){
			$sSql = "DROP TABLE `ts_enquiries_to_inquiries`;";
			DB::executeQuery($sSql);
		}
		
		$bCheck = Ext_Thebing_Util::checkTableExists('ts_journeys_travellers_detail');		
		if($bCheck){
			$sSql = "DROP TABLE `ts_journeys_travellers_detail`;";
			DB::executeQuery($sSql);
		}

	}
	
	
}



