<?php

/**
 * In der saveParentRelation() gab es einen Bug, dass CNs auf gutgeschriebene Rechnungen (is_credit = 1)
 * immer nochmal mit dem Typ credit verknüpft wurden, was falsch ist. Durch updateJoinData() wurde dann
 * die korrekte Verknüpfung (creditnote) nochmal gesetzt.
 *
 * @see \Ext_Thebing_Inquiry_Document::saveParentRelation()
 */
class Ext_Thebing_System_Checks_Documents_Relation2 extends GlobalChecks {

	public function getTitle() {
		return 'Clean document relations';
	}

	public function getDescription() {
		return '';
	}

	public function executeCheck() {

		Util::backupTable('ts_documents_to_documents');

		$sSql = "
			SELECT
				`ts_dtd`.`parent_document_id`,
				`ts_dtd`.`child_document_id`,
				`kid`.`type` `parent_type`,
				`kid2`.`type` `child_type`,
				`kid2`.`created`
			FROM
				`ts_documents_to_documents` `ts_dtd` INNER JOIN
				`kolumbus_inquiries_documents` `kid` ON
					`kid`.`id` = `ts_dtd`.`parent_document_id` AND
					`kid`.`is_credit` = 1 INNER JOIN
				`kolumbus_inquiries_documents` `kid2` ON
					`kid2`.`id` = `ts_dtd`.`child_document_id`
			WHERE
				`ts_dtd`.`type` = 'credit'
		";

		$aResult = (array)DB::getQueryRows($sSql);

		foreach($aResult as $aRow) {

			if(strpos($aRow['parent_type'], 'brutto') !== false) {
				$this->logError('Strange parent relation', $aRow);
			}

			if(strpos($aRow['parent_type'], 'creditnote') !== false) {
				$this->logError('Strange child relation', $aRow);
			}

			$sSql = "
				DELETE FROM
					`ts_documents_to_documents`
				WHERE
					`parent_document_id` = :parent_document_id AND
					`child_document_id` = :child_document_id
			";

			DB::executePreparedQuery($sSql, $aRow);

			$this->logInfo('Deleted relation', $aRow);

		}

		return true;

	}

}