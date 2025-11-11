<?php

class Ext_TS_System_Tools extends Ext_TC_System_Tools {

	/**
	 * @inheritdoc
	 */
	public function getIndexes() {
		return [
			'ts_inquiry' => 'Ext_TS_System_Checks_Index_Reset_Inquiry',
			'ts_document' => 'Ext_TS_System_Checks_Index_Reset_Document',
			'ts_inquiry_group' => 'Ext_TS_System_Checks_Index_Reset_Inquiry_Group'
		];
	}

	/**
	 * @inheritdoc
	 */
	public function getIdActions() {

		$actions = [
			'Buchung: Informationen' => [
				'inquiry_id_customer_data' => 'Inquiry-ID → Daten über Kunden',
				'customer_number_to_inquiry_id' => 'Kundennummer/Buchungsnummer → Inquiry-ID',
				'document_number_to_document_id' => 'Dokument-Nr. → Dokument-ID / Inquiry-ID',
				'document_version_id_to_document_data' => 'Dokument-Versions-ID → Daten über Dokument',
			],
			'Buchung: Aktualisieren' => [
				'tuition_index' => 'Inquiry-ID → Tuition-Index aktualisieren',
				'inquiry_id_refresh_index_data' => 'Inquiry ID → Alle Dokumente (plus Gruppe) aktualisieren (Index)',
			],
			'Buchung: Destruktive Aktionen' => [
				'inquiry_remove_documents' => 'Inquiry-ID → Alle Rechnungen/Dokumente + Storno löschen',
				'inquiry_remove_accommodation_allocations' => 'Inquiry-ID → Alle UK-Zuweisungen löschen',
				'remove_partial_invoice_flag' => 'Inquiry-ID → Teilrechnung-Flag entfernen',
				'document_remove_release' => 'Dokument-ID → Freigabe löschen (ohne Buchungsstapel)',
				'payment_remove_release' => 'Payment-ID → Freigabe löschen (ohne Buchungsstapel)',
			],
			'Index' => [
				'check_missing_bookings' => 'Index: Buchungen auf Vollständigkeit prüfen',
				'check_missing_documents' => 'Index: Dokumente auf Vollständigkeit prüfen'
			],
			'System' => [
				'agency_add_numbers' => 'Agenturnummern ergänzen',
				'update_language_fields' => 'Sprachfelder aktualisieren',
			]
		];

		return array_merge_recursive($actions, parent::getIdActions());

	}

	/**
	 * @inheritdoc
	 */
	public function executeIdAction($sAction, array $aData) {

		switch($sAction) {
			case 'inquiry_id_customer_data':

				$oInquiry = Ext_TS_Inquiry::getInstance(trim($aData['value']));
				$oCustomer = $oInquiry->getCustomer();
				$oGetIds = function($aObjects) {
					return join(', ', array_map(function($oObject) {
						return $oObject->id;
					}, $aObjects));
				};

				return [
					'inquiry_id' => $oInquiry->id,
					'inquiry_number' => $oInquiry->number,
					'contact_id' => $oCustomer->id,
					'contact_name' => $oCustomer->getName(),
					'contact_number' => $oCustomer->getCustomerNumber(),
					'school' => $oInquiry->getSchool()->getName(),
					'document_number_last' => $oInquiry->getLastDocumentNumber(),
					'document_numbers' => join(', ', array_filter(array_map(function($aDocument) {
						return $aDocument['document_number'];
					}, $oInquiry->getDocuments('all')))),
					'journey_id' => $oInquiry->getJourney()->id,
					'journey_course_ids' => $oGetIds($oInquiry->getCourses(true, true, true, false)),
					'journey_accommodation_ids' => $oGetIds($oInquiry->getAccommodations(true, true)),
					'journey_transfer_ids' => $oGetIds($oInquiry->getTransfers('', true)),
					'journey_insurance_ids' => $oGetIds($oInquiry->getInsurances()),
					'journey_activity_ids' => $oGetIds($oInquiry->getActivities()),
				];

			case 'customer_number_to_inquiry_id':

				$sSql = "
					( 
						SELECT
							`ts_itc`.`inquiry_id`
						FROM
							`tc_contacts_numbers` `tc_cn` INNER JOIN
							`ts_inquiries_to_contacts` `ts_itc` ON
								`ts_itc`.`contact_id` = `tc_cn`.`contact_id` AND
								`ts_itc`.`type` = 'traveller'
						WHERE
							`tc_cn`.`number` = :number
						GROUP BY
							`ts_itc`.`inquiry_id`
					) UNION (
						SELECT
							id inquiry_id
						FROM
							ts_inquiries
						WHERE
							number = :number
					)
				";

				$aResult = DB::getQueryCol($sSql, [
					'number' => trim($aData['value'])
				]);

				return $aResult;

			case 'document_number_to_document_id':

				$sNumber = trim($aData['value']);
				if(empty($sNumber)) {
					return null;
				}

				$sSql = "
					SELECT
						`id` `document_id`,
						`entity`,
						`entity_id`,
						`document_number`
					FROM
						`kolumbus_inquiries_documents`
					WHERE
						`document_number` = :number
				";

				return DB::getQueryRow($sSql, [
					'number' => $sNumber
				]);

			case 'document_version_id_to_document_data':

				$version = Ext_Thebing_Inquiry_Document_Version::getInstance(trim($aData['value']));

				return $version->getDocument()->getData();

			case 'document_remove_release':

				$oDocument = Ext_Thebing_Inquiry_Document::getInstance(trim($aData['value']));

				if(!$oDocument->exist()) {
					throw new RuntimeException('Document doesn\'t exist!');
				}

				if(!$oDocument->isReleased()) {
					throw new RuntimeException('Document isn\'t released!');
				}

				$oDocument->removeDocumentRelease();

				return true;

			case 'payment_remove_release':

				$oPayment = Ext_Thebing_Inquiry_Payment::getInstance(trim($aData['value']));

				if(!$oPayment->exist()) {
					throw new RuntimeException('Payment doesn\'t exist!');
				}

				if(!$oPayment->isReleased()) {
					throw new RuntimeException('Payment isn\'t released!');
				}

				$oPayment->removeRelease();

				return true;

			case 'inquiry_id_refresh_index_data':

				$aInquiries = []; /** @var Ext_TS_Inquiry[] $aInquiries */
				$oInquiry = Ext_TS_Inquiry::getInstance(trim($aData['value']));
				$oGroup = $oInquiry->getGroup();
				if($oGroup) {
					foreach($oGroup->getInquiries() as $oInquiry) {
						$aInquiries[] = $oInquiry;
					}
				} else {
					$aInquiries[] = $oInquiry;
				}

				foreach($aInquiries as $oInquiry) {
					Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);
					$aDocuments = $oInquiry->getDocuments('all', true, true);
					foreach($aDocuments as $oDocument) {
						Ext_Gui2_Index_Stack::add('ts_document', $oDocument->id, 0);
					}
				}

				if($oInquiry->hasGroup()) {
					Ext_Gui2_Index_Stack::add('ts_inquiry_group', $oGroup->id, 0);
				}

				Ext_Gui2_Index_Stack::executeCache();

				return true;

			case 'tuition_index':
				
				$oInquiry = Ext_TS_Inquiry::getInstance(trim($aData['value']));
				if (!$oInquiry->exist()) {
					throw new \RuntimeException('Inquiry does not exist.');
				}
				
				$oTuitionIndex = new \Ext_TS_Inquiry_TuitionIndex($oInquiry);
				$oTuitionIndex->update();
				
				return true;

			case 'inquiry_remove_documents':

				$oInquiry = Ext_TS_Inquiry::getInstance(trim($aData['value']));
				$aDocuments = $oInquiry->getDocuments('all', true, true);
				foreach($aDocuments as $oDocument) {
					$oDocument->delete();
				}

				$oInquiry->has_proforma = 0;
				$oInquiry->has_invoice = 0;
				$oInquiry->canceled_amount = 0;
				$oInquiry->canceled = 0;
				$oInquiry->save();

				Ext_Gui2_Index_Stack::executeCache();

				break;

			// Ersetzt durch direkten Button
//			case 'inquiry_recalculate_amounts':
//
//				$inquiry = Ext_TS_Inquiry::getInstance(trim($aData['value']));
//
//				if (!$inquiry->exist()) {
//					throw new \RuntimeException('Inquiry does not exist.');
//				}
//
//				if ($inquiry->hasGroup()) {
//					$inquiries = $inquiry->getGroup()->getInquiries();
//				} else {
//					$inquiries = [$inquiry];
//				}
//
//				foreach ($inquiries as $inquiry) {
//					$inquiry->getAmount(false, true);
//					$inquiry->getAmount(true, true);
//					$inquiry->calculatePayedAmount();
//
//					Ext_Gui2_Index_Stack::add('ts_inquiry', $inquiry->id, 0);
//				}
//
//				Ext_Gui2_Index_Stack::executeCache();
//
//				break;

			case 'inquiry_remove_accommodation_allocations':

				$inquiry = Ext_TS_Inquiry::getInstance(trim($aData['value']));

				if (!$inquiry->exist()) {
					throw new \RuntimeException('Inquiry does not exist.');
				}

				$accommodations = $inquiry->getAccommodations(true, true);
				foreach ($accommodations as $accommodation) {
					/** @var Ext_Thebing_Accommodation_Allocation[] $allocations */
					$allocations = Ext_Thebing_Accommodation_Allocation::query()
						->where('inquiry_accommodation_id', $accommodation->id)
						->get();

					foreach ($allocations as $allocation) {
						// Über Query löschen, da delete/save etc. maximal vergewaltigt wurden
						DB::updateData('kolumbus_accommodations_allocations', ['active' => 0], ['id' => $allocation->id]);
						$allocation->log(Ext_TC_Log::DELETED);
					}
				}

				Ext_Gui2_Index_Stack::add('ts_inquiry', $inquiry->id, 0);
				Ext_Gui2_Index_Stack::save(true);

				break;

			case 'agency_add_numbers':

				$bSuccess = \Util::backupTable('ts_companies_numbers');

				if($bSuccess === false) {
					return false;
				}

				$aReturn = [];

				$sSql = "
					SELECT
						`ka`.`id`
					FROM
						`kolumbus_agencies` `ka` LEFT JOIN
						`ts_companies_numbers` `ts_an` ON
							`ts_an`.`company_id` = `ka`.id
					WHERE
					  	`ka`.`active` = 1 AND
						`ts_an`.`agency_id` IS NULL
					ORDER BY
						`ka`.`created`
				";

				$aAgencies = (array)DB::getQueryCol($sSql);

				foreach($aAgencies as $iAgencyId) {
					$oAgency = Ext_Thebing_Agency::getInstance($iAgencyId);
					$oAgency->disableUpdateOfCurrentTimestamp();
					$oAgency->disableUpdateOfEditor();

					try {
						$oAgency->save();
						$aReturn['updated_agencies']++;
					} catch(\Exception $e) {
						$aReturn['failures'][$iAgencyId] = $e->getMessage();
					}

					unset($oAgency);

				}

				$oLog->addInfo('Return', $aReturn);

				return $aReturn;

			case 'update_language_fields':
				
				Ext_Thebing_Util::updateLanguageFields();
				
				return true;
			case 'check_missing_bookings':
				$oCheck = new Ext_TS_System_Checks_Index_CheckMissingInquiries();
				$oCheck->executeCheck();
				return true;
			case 'check_missing_documents':
				$oCheck = new Ext_TS_System_Checks_Index_CheckMissingDocuments();
				$oCheck->executeCheck();
				return true;
			case 'remove_partial_invoice_flag':
				$inquiry = Ext_TS_Inquiry::getInstance(trim($aData['value']));
				if (!$inquiry->exist()) {
					throw new \RuntimeException('Inquiry does not exist.');
				}

				$documents = $inquiry->getDocuments('invoice', true, true);
				foreach ($documents as $document) {
					$document->partial_invoice = 0;
					$document->save();
				}

				$inquiry->partial_invoices_terms = 0;
				$inquiry->save();

				Ts\Entity\Inquiry\PartialInvoice::query()
					->where('inquiry_id', $inquiry->id)
					->each(fn(Ts\Entity\Inquiry\PartialInvoice $partialInvoice) => $partialInvoice->delete());

				Ext_Gui2_Index_Stack::save(true);

				return true;
			default:
				return parent::executeIdAction($sAction, $aData);
		}

	}

}
