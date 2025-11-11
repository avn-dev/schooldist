<?php

class Ext_TS_System_Checks_UpdateStoragePath extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Update file path in database';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function executeCheck() {
		
		$aTables = [
			'kolumbus_contracts_versions' => 'file',
			'kolumbus_upload' => 'filename',
			'ts_accommodations_payments_groupings' => 'file',
			'ts_teachers_payments_groupings' => 'file',
			'ts_transfers_payments_groupings' => 'file'
		];

		foreach($aTables as $sTable=>$sField) {
			
			Util::backupTable($sTable);
			
			$aSql = [
				'table' => $sTable,
				'field' => $sField
			];
			
			$sSql = "
				UPDATE
					#table
				SET 
					`changed` = `changed`,
					#field = REPLACE(#field, '/media/secure/', '/')
					";

			DB::executePreparedQuery($sSql, $aSql);
			
			$sSql = "
				UPDATE
					#table
				SET 
					`changed` = `changed`,
					#field = REPLACE(#field, 'media/secure/', '/')
					";

			DB::executePreparedQuery($sSql, $aSql);
			
			$sSql = "
				UPDATE
					#table
				SET 
					`changed` = `changed`,
					#field = REPLACE(#field, 'storage/', '/')
					";

			DB::executePreparedQuery($sSql, $aSql);
			
		}

		return true;
	}
		
}
