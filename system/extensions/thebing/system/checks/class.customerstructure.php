<?php


class Ext_Thebing_System_Checks_CustomerStructure extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Change Customer Structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Convert customers to new contacts structure';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		$aError = array();
		$aInfo = array();
		
		$aSql = array();
		
		try
		{
			$iConverted = 0;

			$sTableBackupOld = '__customer_structure';

			// Anlegen von Adress Typen	
			$sSql = "TRUNCATE TABLE `tc_addresslabels_i18n`";
			DB::executePreparedQuery($sSql, $aSql);
			$sSql = "TRUNCATE TABLE `tc_addresslabels`";
			DB::executePreparedQuery($sSql, $aSql);

			$aLableContact = array();	
			$aInsertDataLable = array(
					'id'		=> 1,
					'type'		=> 'contact_address'
			);
			$aLableContact['contact'] = (int)DB::insertData('tc_addresslabels', $aInsertDataLable);

			$aInsertDataLable = array(
					'id'		=> 2,
					'type'		=> 'billing_address'
			);
			$aLableContact['billing'] = (int)DB::insertData('tc_addresslabels', $aInsertDataLable);

			// L18n Lables 
			foreach($aLableContact as $sKey => $iId){

				if($sKey == 'contact'){
					$sNameKey = 'Contact';
				}else{
					$sNameKey = 'Billing';
				}

				$aInsertDataLable = array(
					'label_id'			=> $iId,
					'language_iso'		=> 'en',
					'name'				=> $sNameKey.' Address'
				);

				DB::insertData('tc_addresslabels_i18n', $aInsertDataLable);
			}

			$bExistsOldTable = Ext_Thebing_Util::checkTableExists('customer_db_1');



			$bExistsOldBackup = Ext_Thebing_Util::checkTableExists($sTableBackupOld);

			//wenn weder backup noch die eigentliche Tabelle vorhanden, dann nichts ausführen
			if(
				!$bExistsOldBackup &&
				!$bExistsOldTable
			){
				$aError['no_table_found'] = 'Keine Tabelle gefunden';
				self::report($aError, $aInfo);
				return true;
			}

			//wenn zum 2.mal der check ausgeführt wird, hole die daten aus der backuptabelle
			if($bExistsOldTable){
				$sTable = 'customer_db_1';
			}else{
				$sTable	= $sTableBackupOld;
			}

			//Backup erstellen und neuen Namen festhalten, falls check zum ersten mal ausgeführt wird
			$bSuccessBackup = false;
			if($bExistsOldTable){
				$bSuccessBackup = Ext_Thebing_Util::backupTable('customer_db_1', false, $sTableBackupOld);
			}else{
				// Alle Daten löschen
				$this->deleteData($sTableBackupOld);
			}

			//Logintabelle erstellen
			$sSql = "
				CREATE TABLE IF NOT EXISTS `ts_inquiries_contacts_logins` (
				  `id` mediumint(9) NOT NULL auto_increment,
				  `contact_id` mediumint(8) unsigned NOT NULL,
				  `active` tinyint(1) NOT NULL default '1',
				  `nickname` varchar(255) collate utf8_unicode_ci NOT NULL,
				  `password` char(32) collate utf8_unicode_ci NOT NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `nickname` (`nickname`),
				  KEY `active` (`active`),
				  KEY `contact_id` (`contact_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			";

			DB::executeQuery($sSql);

			// Anfragetable erstellen
			$sSql = "
				CREATE TABLE IF NOT EXISTS `ts_enquiries` (
				  `id` mediumint(9) NOT NULL auto_increment,
				  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				  `created` timestamp NOT NULL default '0000-00-00 00:00:00',
				  `active` tinyint(1) NOT NULL default '1',
				  `editor_id` mediumint(9) NOT NULL,
				  `creator_id` mediumint(9) NOT NULL,
				  `school_id` mediumint(9) NOT NULL,
				  `agency_id` mediumint(9) NOT NULL,
				  `check` date NOT NULL,
				  `referer_id` mediumint(9) NOT NULL,
				  `comment` mediumtext NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `active` (`active`),
				  KEY `editor_id` (`editor_id`),
				  KEY `creator_id` (`creator_id`),
				  KEY `school_id` (`school_id`),
				  KEY `agency_id` (`agency_id`),
				  KEY `referer_id` (`referer_id`),
				  KEY `created` (`created`),
				  KEY `ts_enquiries_1` (`school_id`,`active`,`created`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
			";

			DB::executeQuery($sSql);

			// Anfrage Verknüpfungen
			$sSql = "
				CREATE TABLE IF NOT EXISTS `ts_enquiries_to_contacts` (
				  `enquiry_id` mediumint(9) NOT NULL,
				  `contact_id` mediumint(9) NOT NULL,
				  `type` varchar(50) NOT NULL default 'traveller',
				  PRIMARY KEY  (`enquiry_id`,`contact_id`,`type`),
				  KEY `type` (`type`),
				  KEY `enquiry_id` (`enquiry_id`,`type`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			";
			DB::executeQuery($sSql);

			//Nur Daten vom Client holen, es könnten noch Datensätze aus anderen Installationen vorhanden sein
			$iClientId = (int)Ext_Thebing_System::getClientId();

			$sSql = "
				SELECT
					`cdb1`.*,
					IFNULL(`dl`.`iso_639_1`, '') `language`,
					IFNULL(`dlc`.`iso_639_1`, '') `c_language`,
					IFNULL(`dc`.`cn_iso_2`, '') `nationality`
				FROM
					#table `cdb1` LEFT JOIN
					`data_languages` `dl` ON
						`dl`.`iso_639_1` = `cdb1`.`ext_32` LEFT JOIN
					`data_languages` `dlc` ON
						`dlc`.`iso_639_1` = `cdb1`.`ext_27` LEFT JOIN
					`data_countries` `dc` ON
						`dc`.`id` = `cdb1`.`ext_46`
				WHERE
					`cdb1`.`office` = :client_id 
			";

			$aSql = array(
				'table'		=> $sTable,
				'client_id' => $iClientId
			);

			$oDB = DB::getDefaultConnection();
			
			$aResult = $oDB->getCollection($sSql, $aSql);
			
			#$aResult = DB::getPreparedQueryData($sSql, $aSql);

			if(count($aResult) > 0){
				$aInfo['customer_found'] = 'Kunden gefunden: ' . count($aResult);
			}
		}
		catch(DB_QueryFailedException $e)
		{
			$aError['exception'] = $e->getMessage();
			$aResult = array();
		}
		catch(Exception $e)
		{
			$aError['exception'] = $e->getMessage();
			$aResult = array();
		}
		
		foreach($aResult as $aRowData)
		{
			try
			{
			
				//Datumsformat bereinigen
				$sDay	= $aRowData['ext_3'];
				if(strlen($sDay) < 1){
					$sDay = '00';
				}elseif(strlen($sDay) < 2){
					$sDay = '0'.$sDay;
				}
				$sMonth = $aRowData['ext_4'];
				if(strlen($sMonth) < 1){
					$sMonth = '00';
				}elseif(strlen($sMonth) < 2){
					$sMonth = '0'.$sMonth;
				}
				$sYear	= $aRowData['ext_5'];

				$sDate	= $sYear.'-'.$sMonth.'-'.$sDay;

				if(
					!WDDate::isDate($sDate, WDDate::DB_DATE)
				){
					$sDate = '0000-00-00';
				}

				// Haupt Kontakt
				$aInsertDataCustomer = array(
					'id'						=> $aRowData['id'],
					'changed'					=> $aRowData['last_changed'],
					'created'					=> $aRowData['created'],
					'active'					=> $aRowData['active'],
					'creator_id'				=> $aRowData['creator_id'],
					'editor_id'					=> $aRowData['user_id'],
					'firstname'					=> $aRowData['ext_2'],
					'lastname'					=> $aRowData['ext_1'],
					'language'					=> $aRowData['language'],
					'corresponding_language'	=> $aRowData['c_language'],
					'gender'					=> $aRowData['ext_6'],
					'nationality'				=> $aRowData['nationality'],
					'birthday'					=> $sDate
				);

				$iContactId = (int)DB::insertData('tc_contacts', $aInsertDataCustomer);


				if($iContactId > 0){

					// Details speichern
					self::saveContactDetail($iContactId, 'phone_private'	, $aRowData['ext_16']);
					self::saveContactDetail($iContactId, 'phone_office'		, $aRowData['ext_17']);
					self::saveContactDetail($iContactId, 'phone_mobile'		, $aRowData['ext_18']);
					self::saveContactDetail($iContactId, 'fax'				, $aRowData['ext_19']);
					self::saveContactDetail($iContactId, 'newsletter'		, $aRowData['ext_41']);
					self::saveContactDetail($iContactId, 'comment'			, $aRowData['ext_23']);

					// Kundennummer speichern
					if(!empty($aRowData['customerNumber'])){
						$aInsertDataCustomerNumber = array(
							'contact_id'			=> (int)$iContactId,
							'number'				=> $aRowData['customerNumber'],
							'numberrange_id'		=> (int)$aRowData['numberrange_id'],
						);

						$mReturn = DB::insertData('tc_contacts_numbers', $aInsertDataCustomerNumber);

						if($mReturn === false){
							$aError['customer_number'][] = $aInsertDataCustomerNumber;
						}
					}

					// Logintabelle füllen
					$aInsertDataLogin = array(
						'contact_id'			=> $aRowData['id'],
						'active'				=> 1,
						'nickname'				=> $aRowData['nickname'],
						'password'				=> $aRowData['password']
					);

					$mReturn = DB::insertData('ts_inquiries_contacts_logins', $aInsertDataLogin);

					if($mReturn === false){
						$aError['login_table'][] = $aInsertDataLogin;
					}

					// Kunden Adresse füllen
					$mReturnContactAddress = $this->_saveContactAddress('address', $aLableContact['contact'], $aRowData);

					if($mReturnContactAddress === false){
						$aError['contact_address'][] = $aInsertDataAddress;
					}

					// Rechnungs Adresse füllen
					$mReturnBillingAddress = $this->_saveContactAddress('billing_address', $aLableContact['billing'], $aRowData);

					if($mReturnBillingAddress === false){
						$aError['billing_address'][] = $aInsertBillingAddress;
					}

					if(
						$mReturnContactAddress !== false &&
						$mReturnBillingAddress !== false
					){
						// Contact Mail Adresse
						$aContactMail = array(
							'changed'			=> $aRowData['last_changed'],
							'created'			=> $aRowData['created'],
							'active'			=> 1,
							'creator_id'		=> $aRowData['creator_id'],
							'editor_id'			=> $aRowData['user_id'],
							'email'				=> $aRowData['email'],
							'master'			=> 1,
						);

						$iContactMailId = (int)DB::insertData('tc_emailaddresses', $aContactMail);

						if($iContactMailId > 0){
							$aContactToMail = array(
								'contact_id'		=> (int)$iContactId,
								'emailaddress_id'	=> (int)$iContactMailId
							);		
							DB::insertData('tc_contacts_to_emailaddresses', $aContactToMail);

							if(
								$aRowData['ext_39'] > 0
							){
								// Kontaktanfrage anlegen
								$aEnquiryData = array(
									'changed'			=> $aRowData['last_changed'],
									'created'			=> $aRowData['created'], 
									'active'			=> $aRowData['active'],
									'creator_id'		=> $aRowData['creator_id'],
									'editor_id'			=> $aRowData['user_id'],
									'school_id'			=> $aRowData['ext_31'],
									'agency_id'			=> $aRowData['ext_45'],
									'check'				=> $aRowData['ext_42'],
									'referer_id'		=> $aRowData['ext_44'],
									'comment'			=> $aRowData['ext_40'],
								);
								$iEnquiryId = (int)DB::insertData('ts_enquiries', $aEnquiryData);

								if($iEnquiryId > 0){
									// Zwischentabelle speichern
									$aContactToEnquiry = array(
										'contact_id'	=> (int)$iContactId,
										'enquiry_id'	=> (int)$iEnquiryId,
										'type'			=> 'booker'
									);		
									DB::insertData('ts_enquiries_to_contacts', $aContactToEnquiry);

									$aContactToEnquiry = array(
										'contact_id'	=> (int)$iContactId,
										'enquiry_id'	=> (int)$iEnquiryId,
										'type'			=> 'traveller'
									);		
									DB::insertData('ts_enquiries_to_contacts', $aContactToEnquiry);
								}else{
									$aError['enquiry'][] = $aEnquiryData;
								}

							}

							// Vollständig erfolgreich importierte Kunden
							$iConverted++;
						}

					}
				}else{
					$aError['contact_data'][] = $aInsertDataCustomer;

				}
			}
			catch(DB_QueryFailedException $e)
			{
				$aError['exceptions'][$aRowData['id']] = $e->getMessage();
			}
			catch(Exception $e)
			{
				$aError['exceptions'][$aRowData['id']] = $e->getMessage();
			}

		}
		
		$aInfo['customer_converted'] = 'Kunden komplett umgewandelt: ' . $iConverted;
		
		self::report($aError, $aInfo);

		/*
		 * TABELLE MUSS IMMER `__old_customer_table` umbenannt werden, sonst kann der Inquiry_check nicht durchlafen
		 */
		if(
			$bSuccessBackup
		){
			$sSql = "
				RENAME TABLE 
							`customer_db_1` 
						TO 
							`__old_customer_table`
			";

			DB::executeQuery($sSql);
		}

		return true;
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
	
	public static function report($aError, $aInfo){
		
		$oMail = new WDMail();
		$oMail->subject = 'Customer Structure';
		
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
	
	
	public function deleteData($sTableBackupOld){
		// Contact-Datensätze holen die gelöscht werden können
		$sSql = "SELECT
						`tcc`.`id`
					FROM
						`tc_contacts` `tcc` INNER JOIN
						#table `backup_table` ON
							`backup_table`.`id` = `tcc`.`id`
				";

		$aSql = array(
			'table' => $sTableBackupOld
		);

		$aDeleteContactIds = (array)DB::getQueryCol($sSql, $aSql);

		$aSql['contact_ids'] = $aDeleteContactIds;

		// Adress-Datensätze holen die gelöscht werden können
		$sSql = "
			SELECT
				`address_id`
			FROM
				`tc_contacts_to_addresses`
			WHERE
				`contact_id` IN(:contact_ids)
		";

		$aDeleteAddressIds = (array)DB::getQueryCol($sSql, $aSql);


		// Email-Datensätze holen die gelöscht werden können
		$sSql = "
			SELECT
				`emailaddress_id`
			FROM
				`tc_contacts_to_emailaddresses`
			WHERE
				`contact_id` IN(:contact_ids)
		";

		$aDeleteEmailIds = (array)DB::getQueryCol($sSql, $aSql);

		//Anfragen löschen
		$sSql = "
			SELECT
				`enquiry_id`
			FROM
				`ts_enquiries_to_contacts`
			WHERE
				`contact_id` IN(:contact_ids)
		";

		$aDeleteEnquiryIds = (array)DB::getQueryCol($sSql, $aSql);

		$sSql = "
			DELETE FROM
				#table
			WHERE
				#column IN(:ids)
		";

		/**
		 * Contactsdaten löschen
		 */

		//Contact ids
		$aSql['ids'] = $aDeleteContactIds;

		//Column
		$aSql['column'] = 'id';

		//Contacts löschen
		$aSql['table'] = 'tc_contacts';
		DB::executePreparedQuery($sSql, $aSql);

		//Column
		$aSql['column'] = 'contact_id';

		//Details löschen
		$aSql['table'] = 'tc_contacts_details';
		DB::executePreparedQuery($sSql, $aSql);

		// Nummern löschen
		$aSql['table'] = 'tc_contacts_numbers';
		DB::executePreparedQuery($sSql, $aSql);

		// Logins löschen
		$aSql['table'] = 'ts_inquiries_contacts_logins';
		DB::executePreparedQuery($sSql, $aSql);

		// Contact/Address Verknüpfung löschen
		$aSql['table'] = 'tc_contacts_to_addresses';
		DB::executePreparedQuery($sSql, $aSql);

		// Contact/Email-Address Verknüpfung löschen
		$aSql['table'] = 'tc_contacts_to_emailaddresses';
		DB::executePreparedQuery($sSql, $aSql);

		// Contact/Enquiry Verknüpfung löschen
		$aSql['table'] = 'ts_enquiries_to_contacts';
		DB::executePreparedQuery($sSql, $aSql);

		/**
		 * Addressdaten löschen
		 */

		//Column
		$aSql['column'] = 'id';

		//Table
		$aSql['table'] = 'tc_addresses';

		//Address ids
		$aSql['ids'] = $aDeleteAddressIds;

		DB::executePreparedQuery($sSql, $aSql);


		/**
		 * Emaildaten löschen
		 */

		//Column
		$aSql['column'] = 'id';

		//Table
		$aSql['table'] = 'tc_emailaddresses';

		//Address ids
		$aSql['ids'] = $aDeleteEmailIds;

		DB::executePreparedQuery($sSql, $aSql);

		/**
		 * Anfragedaten löschen
		 */

		//Column
		$aSql['column'] = 'id';

		//Table
		$aSql['table'] = 'ts_enquiries';

		//Address ids
		$aSql['ids'] = $aDeleteEnquiryIds;

		DB::executePreparedQuery($sSql, $aSql);
	}

	protected function _saveContactAddress($sType, $iLabelId, $aRowData)
	{
		$aInsertData = array(
			'changed'				=> $aRowData['last_changed'],
			'created'				=> $aRowData['created'],
			'creator_id'			=> $aRowData['creator_id'],
			'active'				=> 1,
			'editor_id'				=> $aRowData['user_id'],
			'label_id'				=> $iLabelId,
		);

		$bSave = true;
		
		if(
			$sType == 'address'
		)
		{
			$aInsertData['country_iso']		= $aRowData['ext_11'];
			$aInsertData['state']			= $aRowData['ext_33'];
			$aInsertData['address']			= $aRowData['ext_8'];
			$aInsertData['address_addon']	= $aRowData['ext_38'];
			$aInsertData['zip']				= $aRowData['ext_9'];
			$aInsertData['city']			= $aRowData['ext_10'];

			if(
				empty($aInsertData['country_iso']) &&
				empty($aInsertData['state']) &&
				empty($aInsertData['address']) &&
				empty($aInsertData['address_addon']) &&
				empty($aInsertData['zip']) &&
				empty($aInsertData['city'])
			)
			{
				$bSave = false;
			}
		}
		else
		{
			$aInsertData['country_iso']		= $aRowData['ext_15'];
			$aInsertData['address']			= $aRowData['ext_12'];
			$aInsertData['zip']				= $aRowData['ext_13'];
			$aInsertData['city']			= $aRowData['ext_14'];
			$aInsertData['company']			= $aRowData['ext_24'];

			if(
				empty($aInsertData['country_iso']) &&
				empty($aInsertData['address']) &&
				empty($aInsertData['zip']) &&
				empty($aInsertData['city']) &&
				empty($aInsertData['company'])
			)
			{
				$bSave = false;
			}
		}

		if(
			$bSave
		)
		{
			$iAddressId = (int)DB::insertData('tc_addresses', $aInsertData);

			if(
				$iAddressId > 0
			)
			{
				$aContactToAddress = array(
					'contact_id'		=> $aRowData['id'],
					'address_id'		=> $iAddressId
				);

				$rRes = DB::insertData('tc_contacts_to_addresses', $aContactToAddress);

				return $rRes;
			}
			else
			{
				return false;
			}
		}

		return true;
	}

}