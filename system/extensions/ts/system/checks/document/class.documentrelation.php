<?php

/**
 * Ticket #16428 – GLS - Falsche Hauptrechnungsnummer
 *
 * Bei jedem erneuten Speichern (ab Version 2) einer Gutschrift oder Diff wurde diese unwiderbringlich mit sich selbst verknüpft.
 */
class Ext_TS_System_Checks_Document_DocumentRelation extends GlobalChecks {

	public function getTitle() {
		return 'Fix Document Relations';
	}

	public function getDescription() {
		return 'Fix document relations where invoices relate to themselves after editing.';
	}

	public function executeCheck() {

		Util::backupTable('ts_documents_to_documents');

		DB::begin(__CLASS__);

		$rows = (array)DB::getQueryRows("
			SELECT
				ts_dtd.*,
			    kid.type document_type,
			    kid.entity_id
			FROM
				ts_documents_to_documents ts_dtd INNER JOIN
				kolumbus_inquiries_documents kid ON
					kid.id = ts_dtd.child_document_id
			WHERE
				ts_dtd.parent_document_id = ts_dtd.child_document_id AND
			    ts_dtd.type IN ('credit', 'diff') AND
			    kid.entity = 'Ext_TS_Inquiry'
			ORDER BY
				ts_dtd.child_document_id
		");

		$this->logInfo('Affected: '.count($rows));

		foreach ($rows as $row) {

			$row['types'] = ['brutto', 'brutto_diff', 'netto', 'netto_diff'];
			if ($row['type'] === 'credit') {
				$row['types'] = [$row['document_type']];
			}

			// Letzte Rechnung vor dieser Rechnung suchen
			// Glücklicherweise kann man bisher nur immer auf der letzten Rechnung arbeiten, womit das eigentlich immer korrekt sein sollte
			$invoices = (array)DB::getQueryCol("
				SELECT
					id
				FROM
					kolumbus_inquiries_documents
				WHERE
				    entity = 'Ext_TS_Inquiry' AND
					entity_id = :entity_id AND
					id < :child_document_id AND
				    type IN (:types)
				ORDER BY
					`created` DESC,
					`id` DESC
			", $row);

			if (!empty($invoices)) {

				$parentId = reset($invoices);

				try {

					DB::updateData('ts_documents_to_documents', [
						'parent_document_id' => $parentId
					], [
						'parent_document_id' => $row['parent_document_id'],
						'child_document_id' => $row['child_document_id'],
						'type' => $row['type'],
					]);

					$this->logInfo(sprintf('Document %d: Set parent_document_id to %d (type: %s/%s, invoices: %s)', $row['child_document_id'], $parentId, $row['type'], $row['document_type'], join(',', $invoices)));

				} catch (DB_QueryFailedException $e) {

					// Bei Piccola waren alte Dokumente bereits korrekt verknüpft, aber auch nochmal mit sich selbst
					$this->logError(sprintf('Document %d: Error while setting parent_document_id to %d', $row['child_document_id'], $parentId), [$e->getMessage()]);

				}

			} else {

				DB::executePreparedQuery("DELETE FROM ts_documents_to_documents WHERE parent_document_id = :parent_document_id AND child_document_id = :child_document_id AND type = :type", $row);

				$this->logError(sprintf('Document %d: Could not find any previous invoices, deleting relation', $row['child_document_id']), $row);

			}

			Ext_Gui2_Index_Stack::add('ts_document', $row['child_document_id'], 10);

		}

		Ext_Gui2_Index_Stack::save();

		DB::commit(__CLASS__);

		return true;

	}

}
