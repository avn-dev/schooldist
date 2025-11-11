<?php

class Ext_TS_System_Checks_Document_Version_Commissionable extends GlobalChecks {

	public function getTitle() {
		return 'Updates invoice status';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2G');
		
		$documentTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_brutto_without_proforma');
		
		$documentTypes[] = 'manual_creditnote';
        $documentTypes[] = 'creditnote';
		
		$sqlQuery = "
			SELECT
			   `kidv`.`id`
			FROM
				`kolumbus_inquiries_documents_versions` `kidv` JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kidv`.`document_id` = `kid`.`id`
			WHERE
				`kidv`.`active` = 1 AND
				`kid`.`type` IN (:types)
			ORDER BY
				`kidv`.`id` DESC
		";

		$results = \DB::getDefaultConnection()->getCollection($sqlQuery, ['types'=>$documentTypes]);
		
		foreach($results as $result) {
			$this->addProcess(['version_id'=>(int)$result['id']]);
		}
		
		return true;
	}

	public function executeProcess(array $aData) {
		
		if(isset($aData['version_id'])) {
			
			$version = \Ext_Thebing_Inquiry_Document_Version::getInstance($aData['version_id']);
			$version->updateHasCommissionableItems();

			$sqlData = [
				'has_commissionable_items'=>$version->has_commissionable_items, 
				'id'=>$version->id
			];
			
			\DB::executePreparedQuery("UPDATE `kolumbus_inquiries_documents_versions` SET `changed` = `changed`, `has_commissionable_items` = :has_commissionable_items WHERE `id` = :id", $sqlData);
			
		}
		
	}
	
}
