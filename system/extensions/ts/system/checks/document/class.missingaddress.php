<?php

class Ext_TS_System_Checks_Document_MissingAddress extends GlobalChecks {

	public function getTitle() {
		return 'Missing addresses';
	}

	public function getDescription() {
		return 'Check invoices for missing addresses.';
	}

	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2G');
			
		\Util::backupTable('ts_inquiries_documents_versions_addresses');

		DB::begin(__CLASS__);
		
		$documentSearch = new Ext_Thebing_Inquiry_Document_Type_Search();
		$invoiceTypes = $documentSearch->getSectionTypes('invoice_creditnote_manual_creditnote_offer');
		
		$sqlQuery = "
			SELECT 
				`kidv`.`id` `version_id`, 
				`kid`.`entity_id` `inquiry_id`,
				`kid`.`type` `type`
			FROM 
				`kolumbus_inquiries_documents` `kid` JOIN 
				`kolumbus_inquiries_documents_versions` `kidv` ON 
					`kid`.`latest_version` = `kidv`.`id` AND 
					`kidv`.`active` = 1 LEFT JOIN 
				`ts_inquiries_documents_versions_addresses` `ts_idva` ON 
					`kidv`.`id` = `ts_idva`.`version_id`  
			WHERE 
				`kid`.`active` = 1 AND
				`kid`.`type` IN (:types) AND
				`kid`.`entity` = 'Ext_TS_Inquiry' AND
				`ts_idva`.`version_id` IS NULL
		";

		$sqlParams = [
			'types' => $invoiceTypes
		];
		
		$documents = \DB::getDefaultConnection()->getCollection($sqlQuery, $sqlParams);

		foreach($documents as $document) {

			$inquiry = Ext_TS_Inquiry::getInstance($document['inquiry_id']);
			$version = Ext_Thebing_Inquiry_Document_Version::getInstance($document['version_id']);
			
			$view = 'gross';
			if(
				strpos($document['type'], 'netto') !== false ||
				$document['type'] == 'creditnote'
			) {
				$view = 'net';
			}
			
			$documentAddress = new Ext_Thebing_Document_Address($inquiry);
			$defaultValue = $documentAddress->getSelectedAdressSelect($version, $view);
			
			list($type, $typeId) = explode('_', $defaultValue);
			
			$insertAddressLink = [
				'version_id' => $document['version_id'],
				'type' => $type,
				'type_id' => $typeId
			];

			\DB::insertData('ts_inquiries_documents_versions_addresses', $insertAddressLink);
			
		}
		
		DB::commit(__CLASS__);
		
		return true;
	}
		
}
