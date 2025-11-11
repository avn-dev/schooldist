<?php

class Ext_Thebing_Gui2_Icon_Document_List extends Ext_Gui2_View_Icon_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param $oElement
	 * @return int
	 * @throws Exception
	 */
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {
		global $_VARS;

		if (str_contains($oElement->action, 'additional_document')) {
			return $this->getAdditionalDocumentStatus($oElement, $aSelectedIds);
		}

        if($oElement->action == 'executeIndexStack') {
            return 1;
        }

		// Hinweis-Row immer anzeigen
		if(
			$oElement->additional == 'GUIHint' ||
			$oElement->additional == 'GUIInfo' ||
			$oElement->additional == 'GUIError'
		) {
			return 1;
		}

		// CSV-Export immer aktiv (#9411)
		if(
			$oElement->action === '' &&
			$oElement->task === 'export_csv'
		) {
			return 1;
		}

		$aSelectedIds = (array)$aSelectedIds;
		$_VARS['parent_gui_id'] = (array)$_VARS['parent_gui_id'];

		$iSelectedId = (int)reset($aSelectedIds);
		$iInquiryId = (int)reset($_VARS['parent_gui_id']);

		if($this->_oGui->hasParent()) {
			$oParentGui = $this->_oGui->getParent();
			$sEncodeOption = $oParentGui->getOption('decode_inquiry_id_additional_documents');
			if($sEncodeOption) {
				$iInquiryId = (int)$oParentGui->decodeId($iInquiryId, $sEncodeOption);
			}
		}

		$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
		// Entwürfe müssen auch beachtet werden. (draft = null)
		$oLastInvoice = $oInquiry->getDocuments('invoice_without_proforma', false, true, null);
		$oLastProforma = $oInquiry->getDocuments('proforma', false, true);
		$oDocument = Ext_Thebing_Inquiry_Document::getInstance((int)$iSelectedId);

		$isLatestProformaOrInvoice = false;
		if(
			$oLastInvoice->id == $oDocument->id ||
			$oLastProforma->id == $oDocument->id
		) {
			$isLatestProformaOrInvoice = true;
		}
		
		// Bei CN-Icons die CN vom ausgewählten Dokument laden
		$oCreditnote = null;
		$oCreditnoteSubAgency = null;
		if (strpos($oElement->action, 'finalize_creditnote') !== false) {
			$oCreditnote = $oDocument->getCreditNote();
			$oCreditnoteSubAgency = $oDocument->getCreditNoteSubAgency();
		} elseif (strpos($oElement->action, 'creditnote_subagency') !== false) {
			$oCreditnoteSubAgency = $oDocument->getCreditNoteSubAgency();
		} elseif (strpos($oElement->action, 'creditnote') !== false) {
			$oCreditnote = $oDocument->getCreditNote();
		}

        $bNewInvoice = true;
        $bNewProforma = true;

		// Wenn es sich um ein Gruppenmitglied handelt, müssen auch die Rechnungen der
		// Gruppenmitglieder geprüft werden. Der aListCache ist hier NICHT ausreichend
		if($oInquiry->hasGroup()){

			$oGroup		= $oInquiry->getGroup();
			$aInquiries = $oGroup->getInquiries();

			foreach($aInquiries as $oGroupInquiry){

				$oSearch = new Ext_Thebing_Inquiry_Document_Search($oGroupInquiry->id);
				$oSearch->setType(array('netto', 'brutto', 'proforma_netto', 'proforma_brutto'));
				$oDocumentTemp = $oSearch->searchDocument(true, false);

				// Prüfen ob es danach noch eine Gutschrift gab, dann dürfte wieder eine neue Rechnung möglich sein
				$oSearch->setCredit(1);
				$iDocumentCreditId = (int)$oSearch->searchDocument(false, false);

				if(
                     $oDocumentTemp &&
					(
                        (
                            // Es gibt bereits eine Rechnung für eines der Mitglieder aber KEINE Gutschschrift -> Neue Rechnung nicht möglich
                            $oDocumentTemp->id > 0 &&
                            $iDocumentCreditId == 0
                        ) || (
                            // Es existiert nach einer Gutschrift schon wieder eine NEUE Rechnung -> Neue Rechnung nicht möglich
                            $oDocumentTemp->id > $iDocumentCreditId
                        )
                    )
				){
					// nur wenn es keine Proforma ist muss der neu button für rechnungen
                    // deaktivciert werden ansonsten nur proforma deaktivieren
                    if(
						strpos($oDocumentTemp->type, 'proforma') === false
					){
                        $bNewInvoice = false;
                    }
					$bNewProforma = false;
                    break;
				}
			}
		}

		// Ich habe nichts zusammengefasst damit man später
		// problemlos jedes Icon mit verschienden Rechten versehen kann!
		if(
			// NEUE Proforma
			$oElement->task == 'openDialog' &&
			$oElement->action == 'new_proforma' &&
			!$oInquiry->has_invoice &&
			!$oInquiry->has_proforma &&
			$oInquiry->canceled <= 0 && //sollen doch neue Prof
			$bNewProforma &&
			!$oInquiry->hasDraft()
		) {
			return 1;
		} else if(
			// NEUE Rechnung
			$oElement->task == 'openDialog' &&
			$oElement->action == 'new_invoice' &&
			(
				!$oInquiry->has_invoice ||
               (
                   $oLastInvoice->is_credit == 1 &&
                   strpos($oLastInvoice->type, 'diff') === false // gutschrift prüfung gilt nur für nicht diff gutschriften!
               )
            ) &&
			$oInquiry->canceled <= 0 &&
			$oInquiry->isConfirmed() &&
            $bNewInvoice && // gruppen prüfung
			!$oInquiry->hasDraft()
		){
			return 1;
		} else if(
			// Dokument erstellen
			$oElement->task == 'openDialog' &&
			$oElement->action == 'new_document'
		){
			return 1;
		} else if($iSelectedId <= 0){
			// Wenn KEINE ID
			return 0;
		} else if(
			// Als storniert makieren
			$oElement->task == 'request' &&
			$oElement->action == 'mark_as_canceled' &&
			$isLatestProformaOrInvoice &&
			strpos($oDocument->type, 'proforma') !== false &&
			$oInquiry->canceled <= 0
		){
			return 1;
		} else if(
			// Proforma umwandeln
			$oElement->task == 'request' &&
			$oElement->action == 'convertProformaDocument' &&
			$isLatestProformaOrInvoice &&
			strpos($oDocument->type, 'proforma') !== false &&
			$oInquiry->canceled <= 0 &&
			$oInquiry->isConfirmed() &&
			!$oInquiry->hasDraft()
		){
			return 1;
		}  else if(
			// Rechnung Editieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'edit_invoice' &&
			strpos($oDocument->type, 'proforma') === false && (
				!$oDocument->isReleased() ||
				$oDocument->getCompany()->invoice_item_description_changeable
			)
		) {
			if(
				$oInquiry->hasGroup() &&
				$oLastInvoice->id != $oDocument->id
			){
				// Es darf nur die  letzte Gruppenrechnung editiert werden können
				// Unser System kommt mit dem bearbeiten alter Rechnungen nicht klar
				return 0;
			}
			return 1;
		}  else if(
			// Dokument Editieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'edit_document'
		){
			if(
				is_array($aRowData) &&
				count($aRowData) == 1
			) {
				$aData		= reset($aRowData);
				$oTemplate	= Ext_Thebing_Pdf_Template::getInstance($aData['template_id']);
				$sRequestData = $oElement->request_data;
				parse_str($sRequestData, $aRequestParams);
				if(isset($aRequestParams['template_type']))
				{
					$sTemplateType		= $oTemplate->type;
					$aTemplateTypes		= (array)$aRequestParams['template_type'];

					if(!in_array($sTemplateType,$aTemplateTypes)) {
						return 0;
					} else {
						return 1;
					}
				}
			}

			return 1;
		} else if(
			// Proforma Editieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'edit_proforma' &&
			//$oLastDocument->id == $oDocument->id && proforma soll editierbar bleiben
			strpos($oDocument->type, 'proforma') !== false
		){
			return 1;
		} else if(
			// Proforma Löschen
			$oElement->task == 'confirm' &&
			$oElement->action == 'delete_proforma' &&
			$isLatestProformaOrInvoice &&
			strpos($oDocument->type, 'proforma') !== false &&
			$oInquiry->canceled <= 0
		){
			return 1;
		} else if(
			// Rechnung Löschen
			$oElement->task == 'confirm' &&
			$oElement->action == 'delete_invoice' &&
			(
				$isLatestProformaOrInvoice ||
				$oDocument->isDraft()
			) &&
			strpos($oDocument->type, 'proforma') === false &&
			$oInquiry->canceled <= 0 &&
			$oDocument->isReleased() === false &&
			$oDocument->getPayedAmount() == 0 &&
			$oDocument->isMutable()
		){
			$oOverpayment = \Ext_Thebing_Inquiry_Payment_Overpayment::query()
				->where('inquiry_document_id', $oDocument->id)
				->first();
			if ($oDocument->isDraft()){
				return 1;
			}
			if ($oOverpayment === null) {

				$latestDocumentWithSameNumberrange = \Ext_Thebing_Inquiry_Document::query()
					->where('numberrange_id', $oDocument->numberrange_id)
					->orderBy('created', 'DESC')
					->first();

				if ($latestDocumentWithSameNumberrange->id === $oDocument->id) {
					// Löschen nur erlauben wenn es das letzte Dokument eines Nummernkreises ist
					return 1;
				}

			}
		} else if(
			// Proforma Aktuallisieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'refresh_proforma'  &&
			$isLatestProformaOrInvoice &&
			strpos($oDocument->type, 'proforma') !== false &&
			$oInquiry->canceled <= 0 /*&&
			!$oInquiry->hasGroup()*/
		){
			return 1;
		} else if(
			// Rechnung Aktuallisieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'refresh_invoice' &&
			$isLatestProformaOrInvoice &&
			strpos($oDocument->type, 'proforma') === false &&
			$oDocument->type !== 'credit_brutto' &&
			$oDocument->type !== 'credit_netto' &&
			$oDocument->is_credit != 1 &&
			!$oDocument->hasInstalments() &&
			!$oDocument->isReleased() &&
			$oDocument->isMutable()
		){
			return 1;
		} else if(
			// Stornieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'storno' &&
			$isLatestProformaOrInvoice &&
			$oDocument->type != 'storno' &&
			strpos($oDocument->type, 'proforma') === false &&
			!$oDocument->isDraft()
		){
			
			// Gibt es Rechnungen an verschiedene Firmen?
			$invoices = $oInquiry->getDocuments('invoice_without_proforma', true, true);
			$companyIds = [];
			foreach($invoices as $invoice) {
				$version = $invoice->getLastVersion();
				$companyIds[$version->company_id??0] = 1;
			}

			// Stornierungen dürfen nicht über diesen Dialog laufen, wenn Rechnungen an mehr als eine Firma gestellt wurden
			if(count($companyIds) > 1) {
				return 0;
			}
			
			return 1;
		} else if(
			// Differenz Kunde
			$oElement->task == 'openDialog' &&
			(
				$oElement->action == 'diff_customer' ||
				$oElement->action == 'diff_customer_plus_credit' ||
				$oElement->action === 'diff_customer_partial'
			) &&
			$isLatestProformaOrInvoice &&
			!$oDocument->isDraft() &&
			$oDocument->type != 'storno' &&
			#strpos($oDocument->type, 'proforma') === false &&
			//habe ich hinzugefügt wegen T-2748
			(
				$oDocument->is_credit != 1 ||
				(
					$oDocument->type != 'brutto' &&
					$oDocument->type != 'netto'
				)
			) &&
//			!$oInquiry->hasGroup() && #2464
			(
				(
					$oElement->action !== 'diff_customer_partial' &&
					!$oDocument->hasInstalments()
				) || (
					$oElement->action === 'diff_customer_partial' &&
					$oDocument->hasInstalments()
				)
			)
		) {

			if($oElement->action === 'diff_customer_partial') {
				$nextPartialInvoice = Ts\Entity\Inquiry\PartialInvoice::getRepository()->getNext($oInquiry);
				if(empty($nextPartialInvoice)) {
					return 0;
				}
			}
			// Diff Rechnungen von Proforma nicht erlaubt, solange Entwürfe existieren.
			if (
				strpos($oDocument->type, 'proforma') !== false &&
				$oInquiry->hasDraft()
			) {
				return 0;
			}

			return 1;
		} else if(
			// Differenz Agentur
			$oElement->task == 'openDialog' &&
			$oElement->action == 'diff_agency' &&
			$oInquiry->hasAgency() && // hier stand früher $oInquiry->hasNettoPaymentMethod(), wurde so gewünscht mit #2377, falls Probleme verursacht mit Anne reden :)
			$isLatestProformaOrInvoice &&
			$oDocument->type != 'storno' &&
			!$oDocument->isDraft() &&
			#strpos($oDocument->type, 'proforma') === false &&
			//habe ich hinzugefügt wegen T-2748
			(
				$oDocument->is_credit != 1 ||
				(
					$oDocument->type != 'brutto' &&
					$oDocument->type != 'netto'
				)
			) &&
//			!$oInquiry->hasGroup() && #2464
			!$oDocument->hasInstalments()
		){
			return 1;
		} else if(
			// Gutschrift Kunde
			$oElement->task == 'openDialog' &&
			$oElement->action == 'credit_customer'	&&
			(
				$oLastInvoice->type == 'brutto' ||
				$oLastInvoice->type == 'netto'
			) &&
			$isLatestProformaOrInvoice &&
			$oDocument->type != 'storno' &&
			strpos($oDocument->type, 'proforma') === false &&
			!$oDocument->hasInstalments()
		){
			return 1;
		} else if(
			// Gutschrift Agentur
			$oElement->task == 'openDialog' &&
			$oElement->action == 'credit_agency' &&
			(
				$oLastInvoice->type == 'brutto' ||
				$oLastInvoice->type == 'netto'
			) &&
			$isLatestProformaOrInvoice &&
			$oDocument->type != 'storno' &&
			strpos($oDocument->type, 'proforma') === false &&
			$oDocument->is_credit != 1
		){
			return 1;
		} else if(
			// Creditnote anlegen
			$oElement->task == 'openDialog' &&
			$oElement->action == 'creditnote_new' &&
			(
				strpos($oDocument->type, 'brutto') !== false ||
				$oDocument->type == 'storno'					// Man darf auch CNs für Storno anlegen T3771
			) &&
			//strpos($oDocument->type, 'proforma') === false &&
			$oCreditnote === null &&
			$oInquiry->agency_id > 0
		){
			// Wenn ausgewähltes Dokument eine Gutschrift ist: Prüfen, ob dieses bereits eine CN hat #9618
			if($oDocument->is_credit) {
				$oParentDocument = $oDocument->getParentDocument();
				if($oParentDocument instanceof Ext_Thebing_Inquiry_Document) {
					$oParentCreditnote = $oParentDocument->getCreditNote();
					return $oParentCreditnote instanceof Ext_Thebing_Inquiry_Document ? 1 : 0;
				}
			}

			return 1;
		} else if(
			// Creditnote anlegen
			$oElement->task == 'openDialog' &&
			$oElement->action == 'creditnote_subagency_new' &&
			(
				strpos($oDocument->type, 'brutto') !== false ||
				strpos($oDocument->type, 'netto') !== false ||
				$oDocument->type == 'storno'					// Man darf auch CNs für Storno anlegen T3771
			) &&
			$oCreditnoteSubAgency === null &&
			$oInquiry->agency_id > 0
		){
			// Wenn ausgewähltes Dokument eine Gutschrift ist: Prüfen, ob dieses bereits eine CN hat #9618
			if($oDocument->is_credit) {
				$oParentDocument = $oDocument->getParentDocument();
				if($oParentDocument instanceof Ext_Thebing_Inquiry_Document) {
					$oParentCreditnote = $oParentDocument->getCreditNoteSubAgency();
					return $oParentCreditnote instanceof Ext_Thebing_Inquiry_Document ? 1 : 0;
				}
			}

			return 1;
		} else if(
			// Creditnote editieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'creditnote_edit' &&
			(
				strpos($oDocument->type, 'brutto') !== false ||
				$oDocument->type == 'storno'					// Man darf auch CNs für Storno anlegen T3771
			) &&
			//strpos($oDocument->type, 'proforma') === false &&
			$oCreditnote !== null &&
			$oInquiry->agency_id > 0 && (
				!$oCreditnote->isReleased() ||
				$oDocument->getCompany()->invoice_item_description_changeable
			)
		){
			return 1;
		} else if(
			// Creditnote editieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'creditnote_subagency_edit' &&
			(
				strpos($oDocument->type, 'brutto') !== false ||
				strpos($oDocument->type, 'netto') !== false ||
				$oDocument->type == 'storno'					// Man darf auch CNs für Storno anlegen T3771
			) &&
			//strpos($oDocument->type, 'proforma') === false &&
			$oCreditnoteSubAgency !== null &&
			$oInquiry->agency_id > 0 && (
				!$oCreditnoteSubAgency->isReleased() ||
				$oDocument->getCompany()->invoice_item_description_changeable
			)
		){
			return 1;
		} else if(
			// Creditnote aktualisieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'creditnote_refresh' &&
			(
				strpos($oDocument->type, 'brutto') !== false ||
				$oDocument->type == 'storno'					// Man darf auch CNs für Storno anlegen T3771
			) &&
			//strpos($oDocument->type, 'proforma') === false &&
			$oCreditnote !== null &&
			$oInquiry->agency_id > 0 &&
			!$oCreditnote->isReleased() &&
			$oCreditnote->isMutable()
		){
			return 1;
		} else if(
			// Creditnote aktualisieren
			$oElement->task == 'openDialog' &&
			$oElement->action == 'creditnote_subagency_refresh' &&
			(
				strpos($oDocument->type, 'brutto') !== false ||
				strpos($oDocument->type, 'netto') !== false ||
				$oDocument->type == 'storno'					// Man darf auch CNs für Storno anlegen T3771
			) &&
			//strpos($oDocument->type, 'proforma') === false &&
			$oCreditnoteSubAgency !== null &&
			$oInquiry->agency_id > 0 &&
			!$oCreditnoteSubAgency->isReleased()
		){
			return 1;
		} /*else if(
			$oElement->task == 'confirm' &&
			$oElement->action == 'delete_additional_document'
		){

			if($oDocument->type == 'additional_document') {
				return 1;
			} else {
				return 0;
			}

		} */ else if(
			$oElement->task == 'openDialog' &&
			$oElement->action == 'negate_invoice' &&
			(
				$oLastInvoice->type == 'brutto' ||
				$oLastInvoice->type == 'netto' ||
				$oLastInvoice->type == 'netto_diff' ||
				$oLastInvoice->type == 'brutto_diff'
			) &&
			#$isLatestProformaOrInvoice &&
			$oDocument->type != 'storno' &&
			strpos($oDocument->type, 'proforma') === false &&
			$oDocument->is_credit != 1 &&
			!$oDocument->hasInstalments() &&
			!$oDocument->isDraft()
		) {
			return 1;
		} else if (
			$oElement->action == 'finalize' &&
			$oDocument->isDraft()
		) {
			return 1;
		} else if (
			$oElement->action == 'finalize_creditnote' &&
			(
				$oCreditnote?->isDraft() ||
				$oCreditnoteSubAgency?->isDraft()
			)
		) {
			return 1;
		}

		return 0;
	}

	private function getAdditionalDocumentStatus(Ext_Gui2_Bar_Icon|stdClass $oElement, array $aSelectedIds) {

		if ($oElement->action === 'new_additional_document') {
			return true;
		}

		if (
			$oElement->action === 'edit_additional_document' &&
			count($aSelectedIds) === 1
		) {
			return true;
		}

		if (
			$oElement->action === 'delete_additional_document' &&
			!empty($aSelectedIds)
		) {
			return true;
		}

		if (
			$oElement->action === 'merge_additional_document' &&
			count($aSelectedIds) > 1
		) {
			return true;
		}

		if (
			$oElement->action === 'finalize' &&
			count($aSelectedIds) > 1
		) {
			return true;
		}

		return false;

	}

}
