<?php

class Ext_Thebing_System_Checks_Numberranges extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Number range updates';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Builds new number range structure and imports old settings.';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '512M');

		$oDb = DB::getDefaultConnection();
		
		$bField = $oDb->checkField('kolumbus_inquiries_documents', 'numberrange_id');
		
		// Nur ausführen, wenn das Feld noch nicht da ist
		if(!$bField) {

			// Schulen
			$sSql = "
				SELECT 
					`cdb2`.`id`, 
					`cdb2`.`ext_1`
				FROM 
					`customer_db_2` `cdb2` JOIN
					`kolumbus_clients` `kc` ON
						`cdb2`.`idClient` = `kc`.`id` AND
						`kc`.`active` = 1
				WHERE 
					`cdb2`.`active` = 1";
			$aSchools = DB::getQueryPairs($sSql);
			
			// Zuordnung alter Type > neue Application
			$aTypeToAllocation = array(
				'invoice' => 'invoice',
				'proforma' => 'proforma',
				'receipt_invoice' => 'invoice_payments',
				'receipt_payment' => 'payment_receipt',
				'receipt_total' => 'inquiry_payments'
			);
			
			$aTypeToCategory = array(
				'invoice' => 'document',
				'proforma' => 'document',
				'receipt_invoice' => 'receipt',
				'receipt_payment' => 'receipt',
				'receipt_total' => 'receipt'
			);

			$aTypeToDocumentType = array(
				'invoice' => Ext_Thebing_Inquiry_Document_Search::getTypeData(array('invoice_without_proforma', 'creditnote')),
				'proforma' => Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_proforma'),
				'receipt_invoice' => array('document_payment_customer', 'document_payment_agency'),
				'receipt_payment' => array('receipt_customer', 'receipt_agency'),
				'receipt_total' => array('document_payment_overview_customer', 'document_payment_overview_agency')
			);

			Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents');

			DB::addField('kolumbus_inquiries_documents', 'numberrange_id', 'INT NOT NULL', 'document_number');

			// Tabellen leeren
			$aTables = array(
				'tc_number_ranges',
				'tc_number_ranges_allocations',
				'tc_number_ranges_allocations_objects',
				'tc_number_ranges_allocations_receipts',
				'tc_number_ranges_allocations_sets',
				'tc_number_ranges_allocations_sets_applications'
			);

			foreach($aTables as $sTable) {
				$aSql = array(
					'table' => $sTable
				);
				$sSql = "TRUNCATE #table";
				DB::executePreparedQuery($sSql, $aSql);
			}
			
			$aTypes = array_keys($aTypeToAllocation);
			
			// Nummernkreise anlegen
			$sSql = "
				SELECT 
					*
				FROM
					`kolumbus_number_ranges`
				WHERE
					`type` IN (:types)
				";
			$aSql = array('types' => (array)$aTypes);
			$aNumberRanges = DB::getQueryRows($sSql, $aSql);

			foreach($aNumberRanges as $aNumberRange) {

				if(!isset($aSchools[$aNumberRange['school_id']])) {
					continue;
				}
				
				if(empty($aNumberRange['format'])) {
					$aNumberRange['format'] = '%count';
				}

				$sTypeLabel = str_replace('_', ' ', $aNumberRange['type']);
				$sTypeLabel = ucwords($sTypeLabel);
				
				$sLabel = $sTypeLabel." - ".$aSchools[$aNumberRange['school_id']];
				
				// Nummernkreis
				$aData = array();
				$aData['created'] = date('Y-m-d H:i:s');
				$aData['active'] = 1;
				$aData['category'] = $aTypeToCategory[$aNumberRange['type']];
				$aData['name'] = $sLabel;
				$aData['offset_abs'] = $aNumberRange['offset_abs'];
				$aData['offset_rel'] = $aNumberRange['offset_rel'];
				$aData['digits'] = $aNumberRange['digits'];
				$aData['format'] = $aNumberRange['format'];

				$iNumberRangeId = DB::insertData('tc_number_ranges', $aData);

				// Zuordnung
				$aData = array();
				$aData['created'] = date('Y-m-d H:i:s');
				$aData['active'] = 1;
				$aData['category'] = $aTypeToCategory[$aNumberRange['type']];
				$aData['name'] = $sLabel;
				
				$iAllocationId = DB::insertData('tc_number_ranges_allocations', $aData); 

				// Verknüpfung zur Schule
				$aData = array();
				$aData['allocation_id'] = (int)$iAllocationId;
				$aData['object_id'] = (int)$aNumberRange['school_id'];

				DB::insertData('tc_number_ranges_allocations_objects', $aData); 
				
				// Set
				$aData = array();
				$aData['created'] = date('Y-m-d H:i:s');
				$aData['active'] = 1;
				$aData['allocation_id'] = (int)$iAllocationId;
				$aData['numberrange_id'] = (int)$iNumberRangeId;

				$iSetId = DB::insertData('tc_number_ranges_allocations_sets', $aData); 
				
				// Anwendung
				$aData = array();
				$aData['set_id'] = (int)$iSetId;
				$aData['application'] = $aTypeToAllocation[$aNumberRange['type']];
				
				DB::insertData('tc_number_ranges_allocations_sets_applications', $aData); 				

				// Bei Rechnung auch Gutschrift und Storno zuordnen
				if($aTypeToAllocation[$aNumberRange['type']] == 'invoice') {
					$aData = array();
					$aData['set_id'] = (int)$iSetId;
					$aData['application'] = 'creditnote';
					DB::insertData('tc_number_ranges_allocations_sets_applications', $aData);
					
					$aData = array();
					$aData['set_id'] = (int)$iSetId;
					$aData['application'] = 'cancellation';
					DB::insertData('tc_number_ranges_allocations_sets_applications', $aData);
				}

				$aDocumentTypes = $aTypeToDocumentType[$aNumberRange['type']];

				// Nummerkreis ID in Document Tabelle schreiben für Schule und Typ
				$sSql = "
					UPDATE
						`kolumbus_inquiries_documents` `kid` JOIN
						`ts_inquiries` `ki` ON
							`kid`.`inquiry_id` = `ki`.`id` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`inquiry_id` = `ki`.`id` AND
							`ts_i_j`.`active` = 1
					SET
						`kid`.`changed` = `kid`.`changed`,
						`kid`.`numberrange_id` = :numberrange_id
					WHERE
						`ts_i_j`.`school_id` = :school_id AND
						`kid`.`type` IN (:types) AND
						`kid`.`document_number` != ''
					";
				$aSql = array(
					'school_id' => (int)$aNumberRange['school_id'],
					'types' => (array)$aDocumentTypes,
					'numberrange_id' => (int)$iNumberRangeId
				);
				DB::executePreparedQuery($sSql, $aSql);

			}
			
		}

		return true;

	}

}
