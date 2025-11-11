<?php

/**
 * ts_documents_to_documents befüllen bei Anfragen, die vor der Anfragenumstellung (#16002) existiert haben.
 *
 * Für einen(!) Filter muss bekannt sein, welches Angebot umgewandelt wurde. Dafür wird bei allen neu umgewandelten Umfragen das Offer
 * mit der generierten Rechnung/Proforma verknüpft (warum auch immer das vorher nicht gemacht wurde). Dieser Check sucht sich dafür die
 * erstbeste Rechnung der umgewandelten Buchung aus, da es sonst keine andere Möglichkeit gibt.
 *
 * Gleichzeitig sorgt das auch für eine performantere Anzeige umgewandleter Angebote in der unteren Enquiry GUI.
 * @see Ext_TS_Enquiry_Combination_Gui2_Style_Row
 */
class Ext_TS_System_Checks_Enquiry_ConvertedOffersRelation extends GlobalChecks {

	public function getTitle() {
		return 'Offer/Invoice Relation';
	}

	public function getDescription() {
		return 'Allocate old converted offers to the first possible invoice. Needed for the status whether an offer was converted.';
	}

	public function executeCheck() {

		// Nicht nochmal ausführen, da ansonsten fehlerhafte Gruppen hier wieder reinlaufen
		if (System::d('ts_check_enquiry_converted_offers')) {
			return true;
		}

		Util::backupTable('ts_documents_to_documents');

		DB::begin(__CLASS__);

		$inquiries = DB::getQueryRows("
			SELECT
				ts_i.id,
				ts_i.group_id,
			    GROUP_CONCAT(DISTINCT kid.id) `document_ids`
			FROM
				ts_inquiries ts_i LEFT JOIN
				/* Gruppe muss konvertiert worden sein, sonst gibt es hier keine Einträge */
				ts_inquiries_to_inquiries ts_iti ON
				    ts_i.group_id != 0 AND
					ts_iti.parent_id = ts_i.id INNER JOIN
				ts_inquiries_journeys ts_ij ON
					ts_ij.inquiry_id = ts_i.id AND
					ts_ij.type & IF(ts_i.group_id = 0, 2, 1) AND
					ts_ij.active = 1 INNER JOIN
				kolumbus_inquiries_documents kid ON
					kid.entity = 'Ext_TS_Inquiry_Journey' AND
					kid.entity_id = ts_ij.id AND
					kid.active = 1 LEFT JOIN
				/* Angebote, die seit der Umstellung umgewandelt wurden, haben hier Einträge */
				ts_documents_to_documents ts_dtd ON
					ts_dtd.parent_document_id = kid.id AND
					ts_dtd.type = 'offer'
			WHERE
				ts_i.type & 1 AND (
					/* Einzelne Anfragen werden auf Typ 1|2 gesetzt */
				    ts_i.type & 2 OR
				    /* Gruppen-Anfragen bleiben auf Typ 1 und Buchungen werden mit Typ 2 kopiert */
				    ts_iti.child_id IS NOT NULL
				) AND
				ts_dtd.child_document_id IS NULL
			GROUP BY
				ts_i.id
			ORDER BY
				kid.created
		");

		foreach ($inquiries as $inquiry) {

			$documentIds = explode(',', $inquiry['document_ids']);

			if (empty($documentIds)) {
				throw new RuntimeException(sprintf('No document_ids for inquiry %d', $inquiry['id']));
			}

			// Da bei Gruppen nicht klar ist, welches Offer umgewandelt wurde (da Journey auf Typ request bleibt), gibt es hier möglicherweise mehrere
			// Normale Buchungen haben aber immer nur eins, da der Journey auf Typ request+booking geändert wurde
			if (empty($inquiry['group_id']) && count($documentIds) > 1) {
				throw new RuntimeException(sprintf('More than one converted offer for inquiry %d', $inquiry['id']));
			}

			if (empty($inquiry['group_id'])) {

				$this->allocateInvoice($inquiry, reset($documentIds));

			} else {

				$inquiry['document_ids'] = $documentIds;

				// Erstbestes Dokument suchen, welches laut der alten Struktur umgewandelt wurde
				// Hier kann eigentlich nur ein Dokument drin stehen, da die vorherigen Daten bei JEDER Umwandelung gelöscht wurden
				$data = DB::getQueryRow("
					SELECT
						ts_eotd.document_id,
						GROUP_CONCAT(DISTINCT ts_i.id) inquiry_ids
					FROM
						ts_enquiries_offers_to_documents ts_eotd INNER JOIN
						ts_enquiries_offers_to_inquiries ts_eoti ON
							ts_eoti.enquiry_offer_id = ts_eotd.enquiry_offer_id LEFT JOIN
						/* Gelöschte Buchungen haben keine Relevanz hierfür */
						ts_inquiries ts_i ON
							ts_i.id = ts_eoti.inquiry_id AND
							ts_i.active = 1
					WHERE
						ts_eotd.document_id IN (:document_ids)
					GROUP BY
						ts_eotd.document_id
					LIMIT
					    1
				", $inquiry);

				if (empty($data)) {
					// Vielleicht wird das auch durch das frühere Autocomplete-Feld ausgelöst?
					$this->logError('Could not find any document data for converted group inquiry', $inquiry);
//					throw new RuntimeException('Could not find any document data for converted group inquiry '.$inquiry['id']);
				}

				// Gruppe wurde z.B. komplett gelöscht oder alle Mitglieder, die umgewandelt wurden
				if (empty($data['inquiry_ids'])) {
					$this->logError('Group is converted but has no active inquiries anymore?', $inquiry);
					continue;
				}

				$inquiryIds = explode(',', $data['inquiry_ids']);

				foreach ($inquiryIds as $inquiryId) {
					$inquiry['id'] = $inquiryId;
					$this->allocateInvoice($inquiry, $data['document_id']);
				}

			}

		}

		System::s('ts_check_enquiry_converted_offers', 1);

		DB::commit(__CLASS__);

		return true;

	}

	private function allocateInvoice(array $inquiry, $documentId) {

		$inquiry['types'] = \Ext_Thebing_Inquiry_Document_Search::getTypeData(['invoice_netto', 'invoice_brutto']);

		// Erstbeste Proforma/Rechnung
		$invoiceId = DB::getQueryOne("
			SELECT
				id
			FROM
				kolumbus_inquiries_documents
			WHERE
				entity = 'Ext_TS_Inquiry' AND
				entity_id = :id AND
				active = 1 AND
				type IN (:types)
			ORDER BY
				created
		", $inquiry);

		if (empty($invoiceId)) {
			// Nach der Umwandlung könnte die Proforma z.B. gelöscht worden sein, ist daher keine Exception
			$this->logError('Could not find any invoice for converted offer '.$documentId, $inquiry);
			return;
		}

		DB::insertData('ts_documents_to_documents', [
			'parent_document_id' => $documentId,
			'child_document_id' => $invoiceId,
			'type' => 'offer'
		]);

		$this->logInfo(sprintf('Allocated converted offer %s to proforma/invoice %s', $documentId, $invoiceId));

	}

}
