<?php

namespace Office\Migrations;

class AmountReports extends \GlobalChecks {

	public function getTitle() {
		return 'Update documents';
	}
	
	public function getDescription() {
		return 'Update document amount for reporting.';
	}
	
	/**
	 * @return boolean
	 */
	public function executeCheck() {
		
		$bBackup = \Util::backupTable('office_documents');
		
		if($bBackup === false) {
			throw new \RuntimeException('Backup failed!');
		}
		
		$sSql = "
			SELECT
				*
			FROM
				`office_documents`
			WHERE
				`price` != 0 AND
				`price_reports` = 0
				";
		$aDocumentIds = \DB::getQueryCol($sSql);
		
		foreach($aDocumentIds as $iDocumentId) {
			$oDocument = new \Ext_Office_Document($iDocumentId);
			$oDocument->save();
		}

		return true;
	}

}