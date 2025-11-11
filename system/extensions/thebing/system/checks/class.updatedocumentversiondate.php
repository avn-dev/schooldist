<?php


class Ext_Thebing_System_Checks_UpdateDocumentVersionDate extends GlobalChecks {

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions');

		$sSql = "
			UPDATE
				`kolumbus_inquiries_documents_versions` `kidv` JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kidv`.`document_id` = `kid`.`id`
			SET
				`kidv`.`changed` = `kidv`.`changed`,
				`kid`.`changed` = `kid`.`changed`,
				`kidv`.`date` = DATE(`kid`.`created`)
			WHERE
				`kidv`.`active` = 1 AND
				`kidv`.`date` = '0000-00-00'
				";
		DB::executeQuery($sSql);

		return true;

	}

	public function getTitle() {
		$sTitle = 'Update document date field';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Generate default document date if text field is empty.';
		return $sDescription;
	}

}