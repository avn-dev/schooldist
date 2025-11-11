<?php

class Ext_TS_System_Checks_Inquiry_BillingAddress extends GlobalChecks {
	
	public function getTitle() {
		return 'Billing address';
	}
	
	public function getDescription() {
		return 'Invoice addresses of bookings are newly linked.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '2G');

		// Rausfinden, ob der Check schon gelaufen ist!
		if(\System::d('check_'.__CLASS__, false) === true) {
			return;
		}
		
		// Backups
		$backupTables = [
			'tc_addresses',
			'tc_contacts_to_addresses',
			'ts_inquiries_to_contacts',
			'tc_emailaddresses',
			'tc_contacts_to_emailaddresses',
			'ts_journeys_travellers_visa_data',
			'tc_contacts',
			'tc_contacts_numbers',
			'tc_contacts_details'
		];
		
		foreach($backupTables as $backupTable) {
			$backupSuccess = Util::backupTable($backupTable);
			if(!$backupSuccess) {
				return false;
			}
		}
		
		// Leere Adressen inklusive Verknüpfung löschen
		\DB::executeQuery("
			DELETE 
				`tc_a`, 
				`tc_cta` 
			FROM 
				`tc_addresses` `tc_a` LEFT JOIN 
				`tc_contacts_to_addresses` `tc_cta` ON
					`tc_a`.`id` = `tc_cta`.`address_id`
			WHERE 
				`tc_a`.`country_iso` = '' AND 
				`tc_a`.`state` = '' AND 
				`tc_a`.`state_id` = 0 AND 
				`tc_a`.`company` = '' AND 
				`tc_a`.`address` = '' AND 
				`tc_a`.`address_addon` = '' AND 
				`tc_a`.`address_additional` = '' AND 
				`tc_a`.`zip` = '' AND 
				`tc_a`.`city` = ''
		");

		// Leere E-Mail-Adressen inklusive Verknüpfung löschen
		\DB::executeQuery("
			DELETE 
				`tc_e`, 
				`tc_cte` 
			FROM 
				`tc_emailaddresses` `tc_e` LEFT JOIN 
				`tc_contacts_to_emailaddresses` `tc_cte` ON
					`tc_e`.`id` = `tc_cte`.`emailaddress_id`
			WHERE 
				`tc_e`.`email` = ''
		");
		
		// Booker-Einträge löschen (alte Struktur die nie wirklich verwendet wurde)
		\DB::executeQuery("DELETE FROM `ts_inquiries_to_contacts` WHERE `type` = 'booker'");
		
		// Leere VISA-Daten
		\DB::executeQuery("
			DELETE FROM 
				`ts_journeys_travellers_visa_data`
			WHERE 
				`servis_id` = '' AND
				`tracking_number` = '' AND
				`status` = 0 AND
				`required` = 0 AND
				`passport_number` = '' AND
				`passport_date_of_issue` = '0000-00-00' AND
				`passport_due_date` = '0000-00-00' AND
				`date_from` = '0000-00-00' AND
				`date_until` = '0000-00-00'
		");		
		
		// Leere Kontakt-Details
		\DB::executeQuery("
			DELETE FROM 
				`tc_contacts_details`
			WHERE 
				`value` = ''
		");		
		
		// FULLTEXT-Index für Kundensuche
		\DB::executeQuery("ALTER TABLE `tc_contacts` ADD FULLTEXT(`firstname`, `lastname`)");
		\DB::executeQuery("ALTER TABLE `tc_emailaddresses` ADD FULLTEXT(`email`)");
		\DB::executeQuery("ALTER TABLE `tc_addresses` ADD FULLTEXT(`company`)");
		\DB::executeQuery("ALTER TABLE `tc_contacts_numbers` ADD FULLTEXT(`number`)");
		
		\DB::begin(__CLASS__);
		
		// Rechnungsadresse neu zuordnen
		$sqlQuery = "
			SELECT 
				`ts_i`.`id` `inquiry_id`,
				`ts_itc`.`contact_id` `contact_id`,
				`tc_a`.`id` `address_id`				
			FROM 
				`ts_inquiries` `ts_i` JOIN 
				`ts_inquiries_to_contacts` `ts_itc` ON 
					`ts_i`.`id` = `ts_itc`.`inquiry_id` JOIN 
				`tc_contacts_to_addresses` `tc_cta` ON 
					`ts_itc`.`contact_id` = `tc_cta`.`contact_id` JOIN 
				`tc_addresses` `tc_a` ON 
					`tc_a`.`id` = `tc_cta`.`address_id` 
			WHERE 
				`ts_i`.`active` = 1 AND 
				`tc_a`.`active` = 1 AND 
				`tc_a`.`label_id` = 2 AND 
				(
					`tc_a`.`company` != '' OR
					`tc_a`.`address` != '' OR
					`tc_a`.`zip` != '' OR
					`tc_a`.`city` != ''
				)
				


		";

		$billingAddresses = \DB::getQueryRows($sqlQuery);

		foreach($billingAddresses as $billingAddress) {

			// Neuer Kontakt
			$booker = Ext_TS_Inquiry_Contact_Booker::getInstance();
			$booker->inquiries = [$billingAddress['inquiry_id']];
			$booker->save();

			// Verknüpfung zur Adresse aktualisieren
			$address = \Ext_TC_Address::getInstance($billingAddress['address_id']);
			$address->contacts = [$booker->id];
			$address->save();

		}

		\System::s('check_'.__CLASS__, true);
		
		\DB::commit(__CLASS__);
		
		return true;
	}

}