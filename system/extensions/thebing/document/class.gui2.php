<?php

use Illuminate\Support\Str;

class Ext_Thebing_Document_Gui2 extends Ext_Thebing_Gui2_Data {

	const HOOK_DEFAULT_NUMBERRANGE = 'ts_document_default_numberrange';
	
	/**
	 * See parent
	 */
	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);

		$aData['delete_document_position'] = L10N::t('Möchten Sie diese Position wirklich löschen? Dieser Vorgang kann nicht rückgängig gemacht werden!', $sL10NDescription);
		$aData['deposit'] = $this->t('Anzahlung'); // Zeile für Anzahlung hinzufügen
		$aData['change_payment_condition_question'] = $this->t('Die Veränderung der Zahlungsbedingung überschreibt die aktuellen Zahlungsbedingungen.');
		$aData['change_payment_conditions_question'] = $this->t('Die Veränderung der Zahlungsbedingungen löscht die ausgewählte Zahlungsbedingung.');
		$aData['free'] = L10N::t('gratis', $sL10NDescription);
		$aData['document_position_description'] = $this->t('Positionsbeschreibung');

		return $aData;
	}

	private function finalizeDocument(\Ext_Thebing_Inquiry_Document $document, array $_VARS): array
	{
		$result = $document->finalize();
		$transfer = [];
		if ($result === true) {
			$transfer['action'] = 'saveDialogCallback';
			$transfer['error'] = [];
		} else {
			$aErrors[] = $this->_oGui->t('Fehler beim Finalisieren');
			foreach ((array)$result as $error) {
				$aErrors[] = $this->_getErrorMessage($error, '');
			}
			$transfer['action'] = 'showError';
			$transfer['error'] = $aErrors;
		}

		$parents = $this->_oGui->getParentGuiData();
		$guiParent = $this->_getParentGui();
		$parentIds = (array)$_VARS['parent_gui_id'];
		$parentId = (int)reset($parentIds);
		$sHistoryHtml = Ext_Thebing_Document::getHistoryHtml($guiParent, $document->getEntity(), 'invoice');

		$transfer['parent_gui'] = $parents;
		$transfer['parent_hash'] = $guiParent->hash;
		$transfer['parent_id'] = $parentId;
		$transfer['history_html'] = $sHistoryHtml;
		return $transfer;
	}

	public function confirmFinalize(array $_VARS): array
	{
		$document = Ext_Thebing_Inquiry_Document::getInstance((int)$_VARS['id'][0]);
		if ($_VARS['creditnote']) {
			$documentSubagency = $document->getCreditNoteSubagency();
			if (
				$documentSubagency &&
				$documentSubagency->isDraft()
			) {
				$document = $documentSubagency;
			} else {
				$document = $document->getCreditNote();
			}
			if (!$document) {
				$errors[] = $this->_oGui->t('Das Dokument hat keine Gutschrift.');
				$transfer['action'] = 'showError';
				$transfer['error'] = $errors;
				return $transfer;
			}
		}
		return $this->finalizeDocument($document, $_VARS);
	}

	/**
	 * WRAPPER Ajax Request verarbeiten
	 * @param $_VARS
	 */
	public function switchAjaxRequest($_VARS) {

		// Bei Gruppenrechnungen mit vielen Positionen sind 256MB anscheinend zu wenig…
		// Dies betrifft das Speichern und auch das Öffnen des Rechnungsdialogs.
		ini_set('memory_limit', '512M');

		// Cache Init Flag zurücksetzen und Cache leeren
		if(
			$_VARS['task'] == 'openDialog' &&
			$_VARS['action'] == 'document_edit'
		) {
			$this->_oGui->setDocumentPositionsInitialized(false);
			$this->_oGui->resetDocumentPositions();
		}

		/**
		 * In folgenden Fällen werden die Positionen übermittelt und müssen im Cache aktualisiert werden
		 */
		if(
			(
				(
					(
						$_VARS['task'] == 'saveDialog' &&
						//rechnungspositionen der sonstigen dokumente beim speichern nicht aktualisieren, die darf man nicht verändern
						($_VARS['type'] ?? null) != 'additional_document'
					)&&
					$_VARS['action'] == 'document_edit'
				) ||
				$_VARS['task'] == 'reloadTemplateLanguageSelect' ||
				$_VARS['task'] == 'reloadPositionsTable' ||
				$_VARS['task'] == 'openPositionDialog'
			) &&
			is_array($_VARS['position'])
		) {

			$oObject = $this->getSelectedObject();

//			// Inquiry Objekt ermitteln
//			$aSelectedIds	= $this->_getSelectedIdsForDocument($_VARS);
//
//			$iInquiryId		= reset($aSelectedIds);
//
//			if(isset($_VARS['save']['template_id']))
//			{
//				$oTemplate	= Ext_Thebing_Pdf_Template::getInstance($_VARS['save']['template_id']);
//				$oInquiry	= $oTemplate->getObjectFromType($iInquiryId);
//			}
//			else
//			{
//				//In den oben genannten request Fällen sollte eigentlich die template_id immer mitgeschickt sein,
//				//falls dies aus irgendeinem Grund nicht der Fall sein sollte, dann versuchen wir mal wenigstens die Buchungen zu retten :)
//				$oInquiry = Ext_TS_Inquiry::getInstance((int)$iInquiryId);
//			}

			// Sprache kann in zwei verschiedenen Variablen stehen
			$sLanguage = $_VARS['language'];
			if(!empty($_VARS['save']['language'])) {
				$sLanguage = $_VARS['save']['language'];
			}

			// Wenn die Positionen übermittelt werden, darf die Sprache nicht fehlen!
			if(empty($sLanguage)) {
				throw new Exception('Language is missing in request!');
			}

			if ($oObject instanceof \Ext_TS_Inquiry) {
				$oPositions = Ext_Thebing_Document_Positions::getInstance();
				$oPositions->oInquiry = $oObject;
				$oPositions->oGui = $this->_oGui;
				$oPositions->sLanguage = $sLanguage;

				$oPositions->updatePositions($_VARS['position']);
			}

		}

		// Wrapper um die neue Struktur in die bisherige umzuändern
		if(
			$_VARS['task'] == 'confirm' &&
			$_VARS['action'] == 'convert_proforma'
		){
			$_VARS['task'] = 'convertProformaDocument';
			$_VARS['iDocumentId'] = reset($_VARS['id']);
		} elseif(
			$_VARS['task'] == 'request' &&
			$_VARS['action'] == 'mark_as_canceled'
		){
			// Fragen ob gecancelt werden soll
			$_VARS['task'] = 'markAsCanceled';
			$_VARS['iDocumentId'] = reset($_VARS['id']);
		} elseif(
			$_VARS['task'] == 'markAsCanceled'
		){
			$_VARS['iDocumentId'] = $_VARS['document_id'];
		} elseif(
			$_VARS['task'] == 'confirm' &&
			$_VARS['action'] == 'delete_proforma'
		){
			$_VARS['task'] = 'deleteProformaDocument';
			$bResetInquiryAmount	= true;
			$sShowHistoryType		= 'invoice';
		} elseif(
			$_VARS['task'] == 'confirm' &&
			$_VARS['action'] == 'delete_additional_document'
		){
			$_VARS['task'] = 'deleteProformaDocument';
			$bResetInquiryAmount = false;
			$sShowHistoryType		= 'additional_document';
		} elseif(
			$_VARS['task'] == 'confirm' &&
			$_VARS['action'] == 'delete_invoice'
		){
			$_VARS['task'] = 'deleteInvoiceDocument';
			$bResetInquiryAmount = true;
			$sShowHistoryType = 'invoice';
		} elseif (
			$_VARS['task'] == 'confirm' &&
			$_VARS['action'] == 'finalize_creditnote'
		) {
			$_VARS['action'] = 'finalize';
		}

		$aTransfer = array();

		switch($_VARS['task']) {

			// $_VARS['id'] = Positions-Index (bei Extrapositionen EP1 usw.)
			case 'savePositionDialog':

				// Daten in Session speichern
//				$iMainId		= (int)$_VARS['main_id'];
//				$iTemplateId	= (int)$_VARS['template_id'];
				
//				$oTemplate		= Ext_Thebing_Pdf_Template::getInstance($iTemplateId);
//				$oInquiry		= $oTemplate->getObjectFromType($iMainId);

				$oObject = $this->getSelectedObject();

				if ($oObject instanceof Ext_Thebing_Teacher) {
					$oSchool = $oObject->getSchool();
				} else {
					$oSchool = $oObject->getSchool();
				}

				$oDateFormat = new Ext_Thebing_Gui2_Format_Date(false, $oSchool->id);
				$aPositions = $this->_oGui->getDocumentPosition($_VARS['id'][0]);
	
				if(
					!empty($aPositions) &&
					is_array($aPositions)
				) {

					foreach($aPositions as $iKey => &$aPosition) {
						
						$aNewPosition = $_VARS['position']['SI'.$iKey];
						$aNewDiscount = $_VARS['position']['SC'.$iKey];

						$aPosition['description']			= $aNewPosition['description'];
						$aPosition['description_discount']	= $aNewDiscount['description'];
						if(isset($aNewPosition['amount'])) {
							$aPosition['amount']			= Ext_Thebing_Format::convertFloat($aNewPosition['amount']);
						}
						if(isset($aNewPosition['amount_provision'])) {
							$aPosition['amount_provision']	= Ext_Thebing_Format::convertFloat($aNewPosition['amount_provision']);
						}
						if(isset($aNewPosition['amount_discount'])) {
							$aPosition['amount_discount']	= Ext_Thebing_Format::convertFloat($aNewPosition['amount_discount']);
						}

						$aPosition['amount_net']			= round(($aPosition['amount'] - $aPosition['amount_provision']), 2);
						$aPosition['onPdf']					= $aNewPosition['onPdf'];
						$aPosition['position_key']			= $_VARS['id'][0];

						// Provision bei Discount umrechnen
						if(
							$aPosition['amount_provision'] != 0 &&
							$aPosition['amount_discount'] != 0
						) {
							$aPosition['amount_provision'] = $aPosition['amount_provision'] / (1 - ($aPosition['amount_discount'] / 100));
						}

						// Leistungszeitraum ändern
						if (
							!empty($aNewPosition['index_from']) &&
							!empty($aNewPosition['index_until'])
						) {
							$sFrom = $oDateFormat->convert($aNewPosition['index_from']);
							$sUntil = $oDateFormat->convert($aNewPosition['index_until']);

							if (
								\Core\Helper\DateTime::isDate($sFrom, 'Y-m-d') &&
								\Core\Helper\DateTime::isDate($sUntil, 'Y-m-d')
							) {
								$aPosition['index_from'] = $sFrom;
								$aPosition['index_until'] = $sUntil;
							}
						}
						
						\System::wd()->executeHook('ts_document_position_detail_save', $aNewPosition, $aPosition);
						
					}

					$this->_oGui->setDocumentPosition($_VARS['id'][0], $aPositions);

					$this->_oGui->groupPositionsByDescriptionAndDiscount($_VARS['id'][0]);
				} 

				$aTransfer = $this->reloadPositionsTable($_VARS);
				$aTransfer['data']['close_dialog_id'] = 'DOCUMENT_POSITIONS_0';
				
				echo json_encode($aTransfer);
				
				break;

			case 'deleteProformaDocument':
			case 'deleteInvoiceDocument':

				DB::begin('deleteProformaDocument');

				$_VARS['id'] = (array)$_VARS['id'];

				$aParentIds		= (array)$_VARS['parent_gui_id'];
//				$iDocumentId	= reset($_VARS['id']);

				$aParents = $this->_oGui->getParentGuiData();
				$oGuiParent	 = $this->_getParentGui();

				$sInquiryIdFieldEncoded = $oGuiParent->getOption('decode_inquiry_id_additional_documents');
				if(!empty($sInquiryIdFieldEncoded)){
					$aParentIds = $oGuiParent->decodeId($aParentIds, $sInquiryIdFieldEncoded);
				}

				$iParentId	= (int)reset($aParentIds);

				// Komischer Code wird auch für Zusatzdokumente verwendet
				foreach ($_VARS['id'] as $iDocumentId):

				$oDocument	= Ext_Thebing_Inquiry_Document::getInstance((int)$iDocumentId);
				
				$oInquiry	= $oDocument->getEntity();

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Reset inquiry amount fields

				if ($_VARS['task'] === 'deleteInvoiceDocument' && $oDocument->isInvoice()) {

					if($oInquiry instanceof Ext_TS_Inquiry) {
						// TODO Gruppen?
						$mSuccess = $oInquiry->deleteInvoice($oDocument, $bResetInquiryAmount);
					}

				} else if(
					$oDocument->isProforma()
				) {
					if($oInquiry instanceof Ext_TS_Inquiry)
					{

						$mSuccess = $oInquiry->deleteProforma($bResetInquiryAmount, $oDocument);

						if($oInquiry->hasGroup())
						{
							$oGroup		= $oInquiry->getGroup();

							$aMembers	= $oGroup->getInquiries();

							foreach($aMembers as $oMember)
							{
								$mSuccess2 = $oMember->deleteProforma($bResetInquiryAmount);
								if(
									$mSuccess2 !== true &&
									$mSuccess2 !== false
								) {
									$mSuccess = array_merge($mSuccess, $mSuccess2);
								}
							}
						}
					}
					else
					{
						$mSuccess = $oDocument->delete();
					}
				}
				else
				{
					$mSuccess = $oDocument->delete();
				}

				endforeach;

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

				// deleteRow teilweise nachbasteln, da hier ja aus irgendeinem Grund wieder etwas komplett eigenes gemacht wurde
				if($mSuccess === true) {
					DB::commit('deleteProformaDocument');
					$aTransfer['action'] = 'deleteCallback';
					$aTransfer['error'] = [];
				} else {
					DB::rollback('deleteProformaDocument');
					$aErrors[] = L10N::t('Fehler beim Löschen', $this->_oGui->gui_description);
					if(is_array($mSuccess)) {
						foreach($mSuccess as $sField => $aFieldErrors) {
							$aErrors[] = $this->_getErrorMessage(reset($aFieldErrors), $sField);
						}
					}
					$aTransfer['action'] = 'showError';
					$aTransfer['error'] = $aErrors;
				}

				$aTransfer['parent_gui']		= $aParents;
				$aTransfer['parent_hash']		= $oGuiParent->hash;
				$aTransfer['parent_id']			= $iParentId;
 
				$sHistoryHtml					= Ext_Thebing_Document::getHistoryHtml($oGuiParent, $oInquiry, $sShowHistoryType);
				$aTransfer['history_html']		= $sHistoryHtml;

				echo json_encode($aTransfer);

				break;

			case 'markAsCanceled':

				$oDocument = new Ext_Thebing_Inquiry_Document($_VARS['iDocumentId']);
				$oInquiry = $oDocument->getInquiry();
				
				if($oInquiry->hasGroup())
				{
					$oGroup		= $oInquiry->getGroup();
					$aMembers	= $oGroup->getInquiries();
				}
				else
				{
					$aMembers	= array($oInquiry);
				}
				
				// Prüfen ob nur Proformas vorhanden sind
				$bDeleteProforma	= true;
				$aProformaNumbers	= array();

				foreach($aMembers as $oMember)
				{
					$oProforma			= $oMember->getLastDocument('invoice_proforma');
					
					if($oProforma){
						
						$aProformaDocuments[] = $oProforma;
						
						$aWithoutDocuments = Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, 'invoice_without_proforma', true, true);
						
						if(count($aWithoutDocuments) > 0){
							$bDeleteProforma = false;
						} else{
							$aProformaNumbers[] = $oProforma->document_number;
						}
						
					}	
				}
				
				$aProformaNumbers = array_unique($aProformaNumbers);

				if($_VARS['confirmedCancelation'] != 1){
					// Abfrage ob storniert werden soll
					// Dialog mit Warnhinweis
					$aTransfer['action']					= 'markAsCanceledConfirm';
					$aTransfer['task']						= 'markAsCanceledConfirm';
					$aTransfer['data']['id']				= 'DOCUMENTS_LIST_'.(int)$oInquiry->id;
					$aTransfer['data']['document_id']		= $_VARS['iDocumentId'];
					$aTransfer['data']['hash']				= $this->_oGui->parent_hash;
					$aTransfer['data']['proforma_numbers']	= $aProformaNumbers;
					$aTransfer['error']						= array();
					echo json_encode($aTransfer);
				}else{

					// Proformas löschen
					if($bDeleteProforma){
						foreach((array)$aProformaDocuments as $oProforma){
							
							$oProforma->active = 0;
							$oProforma->save();
							
							$oInquiry = $oProforma->getInquiry();
							
							if($oInquiry instanceof Ext_TS_Inquiry) {

								// Beträge der Buchung neu schreiben (ansonsten ändert sich da nichts)
								$fAmount = $oInquiry->getAmount(false, true);
								$oInquiry->getAmount(true, true);
								//$oInquiry->getCreditAmount(true);

								// Endgültiges Stornieren nach Bestätigung
								$oInquiry->confirmCancellation($fAmount);

							}
						}
					} else{
						throw new Exception('Error!');
					}
				
					$aTransfer['action']					= 'closeDialogAndReloadTable';
					$aTransfer['success_message']			= $this->t('Der Kunde wurde erfolgreich als Storniert makiert!');
					$aTransfer['data']['id']				= 'DOCUMENTS_LIST_'.(int)$oInquiry->id;
					$aTransfer['data']['hash']				= $this->_oGui->parent_hash;
					$aTransfer['error']						= array();
					echo json_encode($aTransfer);
				}	
				break;
			case 'getNewCommissionAmounts': // Berechnet den neuen Provisionsbetrag zu einem Item

				$documentType = $_VARS['type'];
				
				$aCommissionAmounts = [];
				foreach($_VARS['positions'] as $aPosition) {
					$aCachePosition = $this->_oGui->getDocumentPosition($aPosition['position_key']);
					$fCommission = 0;
					$iPositionId = 0;

					if(is_array($aCachePosition)) {
						// Kommt hier trotzdem als Array mit einer Position zurück
						$aFirstPosition = reset($aCachePosition);
						$iPositionId = $aFirstPosition['position_id'];
					}

					if($iPositionId > 0) {
						// Wenn Position bereits gespeichert wurde: Mit ID direkt Methode aufrufen
						$oItem = new Ext_Thebing_Inquiry_Document_Version_Item($iPositionId);
						$fCommission = $oItem->getNewProvisionAmount($aPosition['amount'], $documentType);
					} elseif(
						$_VARS['parent_gui_id'][0] > 0 &&
						$aPosition['type'] !== ''
					) {
						$oInquiry = $this->getSelectedObject();

						if(
							$oInquiry instanceof Ext_TS_Inquiry_Abstract &&
							$oInquiry->id > 0 &&
							$oInquiry->hasAgency()
						) {
							$oAgency = $oInquiry->getAgency();
							$fAmount = Ext_Thebing_Format::convertFloat($aPosition['amount']);

							// Optionen
							$aOptions = array();
							$aOptions['type'] = $aPosition['type'];
							$aOptions['type_id'] = $aPosition['type_id'];
							$aOptions['type_object_id'] = $aPosition['type_object_id'];
							$aOptions['parent_booking_id'] = $aPosition['parent_booking_id'];
							$aOptions['additional'] = $aPosition['additional'];

							$fCommission = $oAgency->getNewProvisionAmountByType($oInquiry, $fAmount, $aOptions, $documentType);
						}
					}

					$sPositionKey = $aPosition['position_key'];
					if(!empty($aPosition['subposition_key'])) {
						$sPositionKey = $aPosition['subposition_key'];
					}

					$aCommissionAmounts[$sPositionKey] = $fCommission;
				}

				$aTransfer['action'] = 'updateCommissionPositions';
				$aTransfer['data']['positions'] = $aCommissionAmounts;

				echo json_encode($aTransfer);
				break;
		
			default:
				parent::switchAjaxRequest($_VARS);

		}

	}

	/**
	 * @param array $_VARS
	 * @param Ext_TS_Inquiry_Abstract $oInquiryAbstract
	 * @param string $sType
	 * @return Ext_Thebing_Inquiry_Document
	 */
	public function getDocument($_VARS, Ext_TS_Inquiry_Abstract $oInquiryAbstract, $sType) {
		
		$oInquiryDocument = $oInquiryAbstract->newDocument($sType);
		
		return $oInquiryDocument;
	}

	protected function _getErrorData($aErrorData, $mAction, $sType, $bShowTitle = true) {
		$aErrorData = parent::_getErrorData($aErrorData, $mAction, $sType, $bShowTitle);

		// Felder für PaymentTerms der Version haben keine ID
		foreach($aErrorData as &$aError) {
			if(
				isset($aError['identifier']) &&
				Str::startsWith($aError['identifier'], 'paymentterms')
			) {
				preg_match('/\.(.+)\..*(\d+)/', $aError['identifier'], $aMatches);
				$aError['error_id'] = null;
				$aError['input']['id'] = null;
				$aError['input']['name'] = 'paymentterm['.$aMatches[1].'][]';
				$aError['input']['index'] = $aMatches[2];
			}
		}

		return $aErrorData;
	}

	protected function  _getErrorMessage($sError, $sField, $sLabel='', $sAction=null, $sAdditional=null) { 
		
		$bTranslate = true;
		switch($sError){
			case 'ENQUIRY_CONVERT_ERROR':
				$sErrorMessage = 'Anfrage konnte nicht umgewandelt werden.';
				break; 
			case 'ENQUIRY_IS_CONVERTED':
				$sErrorMessage = 'Anfrage wurde bereits umgewandelt.';
				break; 
			case 'ENQUIRY_CONVERT_PDF_EXCEPTION':
				$sErrorMessage = 'Es gibt ein Problem mit der Rechnungsvorlage.';
				break; 
			case 'ENQUIRY_CONVERT_DOCUMENT_ERROR':
				$sErrorMessage = 'Das Angebot wurde nicht gefunden';
				break; 
			case 'INVALID_PROFORMA':
				$sErrorMessage = 'Dokument fehlerhaft: Speichern einer Proforma nicht möglich.';
				break;
			case 'INVOICE_EXISTS':
				$sErrorMessage = 'Dokument fehlerhaft: Es existiert bereits eine Rechnung.';
				break;
			case 'INVALID_INVOICE':
				$sErrorMessage = 'Dokument fehlerhaft: Speichern einer Rechnung nicht möglich.';
				break;
			case 'WRONG_VERSION_DATE':
				$sErrorMessage = 'Das Rechnungsdatum hat ein falsches Format.';
				break;
			case 'VERSION_PAYMENTTERM_DATE_EMPTY':
				$sErrorMessage = 'Bitte geben Sie ein Datum bei der Zahlungsbedingung ein.';
				break;
			case 'VERSION_PAYMENTTERM_DATE_FORMAT':
				$sErrorMessage = 'Das Datumsformat der Zahlungsbedingung ist nicht korrekt.';
				break;
			case 'VERSION_PAYMENTTERM_DATE_BEFORE_VERSION_DATE':
				$sErrorMessage = 'Das Datum der Zahlungsbedingung liegt vor dem Rechnungsdatum.';
				break;
			case 'VERSION_PAYMENTTERM_DATE_CHRONOLOGICAL':
				$sErrorMessage = 'Die Datumsangaben der Zahlungsbedingungen müssen chronologisch sein.';
				break;
//			case 'VERSION_PAYMENTTERM_AMOUNT_NEGATIVE':
//				$sErrorMessage = 'Der Betrag der Zahlungsbedingung darf nicht negativ sein.';
//				break;
			case 'VERSION_PAYMENTTERM_AMOUNT_EQUAL_OR_HIGHER_THAN_TOTAL':
				$sErrorMessage = 'Die Anzahlungsbeträge können nicht gleich oder höher als der Gesamtbetrag der Rechnung sein.';
				break;
			case 'VERSION_PAYMENTTERM_AMOUNT_NOT_EQUAL_TO_TOTAL';
				$sErrorMessage = 'Die Anzahlungsbeträge und die Restzahlung ergeben nicht die Summe der Totale.';
				break;
			case 'NUMBERRANGE_LOCKED':
				$sErrorMessage = Ext_Thebing_Document::getNumberLockedError();
				$bTranslate = false;
				break;
			case 'NUMBERRANGE_REQUIRED':
				$sErrorMessage = Ext_Thebing_Document::getNumberrangeNotFoundError();
				$bTranslate = false;
				break;
			case 'DOCUMENT_NUMBER_BUT_NO_NUMBERRANGE_ID':
				$sErrorMessage = 'Es besteht ein fataler Fehler beim Verwenden des Nummernkreises.';
				break;
			case $sField == 'pdf':
				$sErrorMessage	= $sError;
				//nicht übersetzen, übersetzt wurde es schon in Ext_Thebing_Document, wegen $e->getMessage()
				$bTranslate		= false;
				break;
			case $sField == 'accounting':
				//alle Buchhaltungsfehler sind schon ganze Sätze, darum direkt Übersetzen ohne eine Äbderung
				//da saveTransaction() von mehreren Stellen benutzt wird, konnte ich die Sätze nicht hierhin
				//verschieben
				$sErrorMessage	= $sError;
				break;
			case 'VERSION_AMOUNT_ZERO_FORBIDDEN':
				$sErrorMessage = 'Der Rechnungsbetrag darf nicht 0 sein.';
				break;
			case 'MUST_BE_DRAFT':
				$sErrorMessage = 'Eine neue Rechnung muss ein Entwurf sein.';
				break;
			case 'ONLY_ONE_DRAFT':
				$sErrorMessage = 'Es darf nur einen Entwurf geben.';
				break;
			case 'INVOICE_IS_IMMUTABLE':
				$sErrorMessage = 'Die Rechnung kann nicht mehr bearbeitet werden.';
				break;

			default:
				$sErrorMessage	= parent::_getErrorMessage($sError, $sField, $sLabel);
				$bTranslate		= false;
				break;
		}

		if($bTranslate) {
			// Mit dem allgemeinen Fehler-pfad übersetzen, da Dokumente an mehreren Stellen im System
			// benutzt wird.
			$sErrorMessage = L10N::t($sErrorMessage, 'Thebing » Errors');
		}

		return $sErrorMessage;
	}

	/**
	 * Neuer Ansatz bei diesem ID-Chaos, um das selektierte Objekt zu holen
	 *
	 * @return \Ts\Interfaces\Entity\DocumentRelation
	 * @throws Exception
	 */
	public function getSelectedObject(int $iObjectId = null): \Ts\Interfaces\Entity\DocumentRelation {

		if ($iObjectId !== null) {
			return Ext_TS_Inquiry::getInstance($iObjectId);
		}

		// Man ging in _getSelectedIdsForDocument schon immer davon aus, dass bei einer Page die obere Liste die Inquiry ist
		$aSelectedIds = (array)$this->request->input('parent_gui_id');

		if (empty($aSelectedIds)) {
			$aSelectedIds = (array)$this->request->input('id');
		}

		// Unterkunftskommunikation und andere alten GUIs sind encoded
		$aSelectedIds = $this->_decodeSelectedIdsForDocument($aSelectedIds);

		$iId = reset($aSelectedIds);

		return Ext_TS_Inquiry::getInstance($iId);

	}

	protected function buildDocumentDialogId(array $aSelectedIds): string {

		// Provisionsliste ist encoded
		$aSelectedIds = $this->_decodeSelectedIdsForDocument($aSelectedIds);

		sort($aSelectedIds);

		return 'DOCUMENT_'.join('_', $aSelectedIds);

	}

	/**
	 *
	 * @return array
	 */
	protected function _decodeSelectedIdsForDocument(array $aSelectedIds, $bGetAllData=false) {

		$oGuiParent = $this->_getParentGui();

		if(
			is_object($oGuiParent) && 
			$oGuiParent instanceof Ext_Gui2
		) {
			$sInquiryIdField = null;

			if(!$bGetAllData) {
				$sInquiryIdField = $oGuiParent->getOption('decode_inquiry_id_additional_documents');
			}

			if(!empty($sInquiryIdField) || $bGetAllData) {
				$aSelectedIds = $oGuiParent->decodeId($aSelectedIds, $sInquiryIdField);
			}

		} else {
			$sInquiryIdField = null;

			if(!$bGetAllData) {
				$sInquiryIdField = $this->_oGui->getOption('decode_inquiry_id_additional_documents');
			}

			if(!empty($sInquiryIdField) || $bGetAllData) {
				$aSelectedIds = $this->_oGui->decodeId($aSelectedIds, $sInquiryIdField);
			}

		}
		
		return $aSelectedIds;
	}

	/**
	 * @param Ext_Gui2|null $oGui
	 * @return \Elastica\Query\BoolQuery
	 */
	public static function getListWhere(Ext_Gui2 $oGui = null) {

		$oParentGui = $oGui->getParentClass();
        $bIsAllSchools = Ext_Thebing_System::isAllSchools();
        $oBoolQuery = new Elastica\Query\BoolQuery();

		if(!$bIsAllSchools) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$oQuery = new Elastica\Query\Term();
			$oQuery->setTerm('school_id', (int)$oSchool->id);
			$oBoolQuery->addMust($oQuery);
		}
        
		$sDocumentType = $oGui->getOption('document_type');

		if($sDocumentType) {
			$oDocSearch	= new Ext_Thebing_Inquiry_Document_Type_Search();
			$oDocSearch->addSection($sDocumentType);
			
			if(
				$sDocumentType !== 'invoice' &&
				$sDocumentType !== 'additional_document'
			) {
				// bei sonstigen Dokumenten Dialog nicht Gutschrift-Storno's anzeigen
				$oDocSearch->add('creditnote_cancellation');
			}
			// Elastica 8 erlaubt keine assoziativen keys mehr
			$oQuery = new Elastica\Query\Terms('type_status', array_values($oDocSearch->getTypes()));
			$oBoolQuery->addMust($oQuery);
        }

		// Wenn Recht nicht vorhanden, dürfen nur die Dokumente der Liste angezeigt werden
		if(
			$sDocumentType === 'additional_document' &&
			!Ext_Thebing_Access::hasRight('thebing_gui_document_areas')
		) {
			// Nur Dokumente anzeigen, die in der gleichen GUI erstellt wurden

			if(
				$oParentGui instanceof Ext_Gui2 &&
				$oParentGui->getOption('only_documents_from_same_gui')
			) {

				$sParentGuiName = $oParentGui->name;
				$sParentGuiSet = $oParentGui->set;

				$sGuiList = $sParentGuiName;
				if(!empty($sParentGuiSet)) {
					$sGuiList .= '_'.$sParentGuiSet;
				}

				// Außerdem nach leerem String suchen, da alte Dokumente das noch nicht haben
				$oQuery = new Elastica\Query\Terms('gui2_list', ['no_allocation', $sGuiList]);
				$oBoolQuery->addMust($oQuery);

			} else {
				// Nur Dokumenttypen der gleichen Template-Art anzeigen
				$aTemplateTypes = (array)$oGui->getOption('template_type'); // String oder Array
				if(!empty($aTemplateTypes)) {
					$oQuery = new Elastica\Query\Terms('template_type',$aTemplateTypes);
					$oBoolQuery->addMust($oQuery);
				}
			}
		}
		/*if (!$oParentGui instanceof Ext_Gui2) { // Anzeige von Entwürfen nur in Rechnungsdialog
			$oQuery = new Elastica\Query\Term();
			$oQuery->setTerm('draft', '0');
			$oBoolQuery->addMust($oQuery);
		}*/
		// Zusätzliche Rechteprüfung mit Inboxrechten
		// Dies wäre durch »thebing_gui_document_areas« schon abgedeckt, allerdings nur für neue Dokumente
		// TODO: Irgendwann mal entfernen? Das wurde damals nur für DID (Legacy) eingebaut
		/*if(
			System::d('ts_check_inbox_rights_for_document_templates') && (
				!$oParentGui instanceof Ext_Gui2 ||
				$oParentGui->name !== 'ts_enquiry'
			)
		) {

			$oUser = System::getCurrentUser();
			$aInboxes = $oUser->getInboxes('id');

			$oInboxQuery = new \Elastica\Query\Terms('template_inboxes', $aInboxes);
			$oTypeQuery = new \Elastica\Query\QueryString();
			$oTypeQuery->setDefaultField('type_filter', 'manual_creditnote OR offer');

			// Entweder Inboxen stimmen und Typen sind nicht Manuelle CNs und Angebote (haben keine Inboxen)
			$oBool1 = new \Elastica\Query\BoolQuery();
			$oBool1->addMust($oInboxQuery);
			$oBool1->addMustNot($oTypeQuery);

			// Oder Typen sind Manuelle CNs und Angebote
			$oBool2 = new \Elastica\Query\BoolQuery();
			$oBool2->addMust($oTypeQuery);

			$oMasterBool = new \Elastica\Query\BoolQuery();
			$oMasterBool->addShould($oBool1);
			$oMasterBool->addShould($oBool2);
			$oMasterBool->setMinimumShouldMatch(1);

			$aWhere['template_inboxes'] = $oMasterBool;
		}*/

		return $oBoolQuery;

	}

	/**
	 * @param Ext_Gui2|null $oGui
	 * @return \Elastica\Query\BoolQuery
	 */
	public static function getProformaListWhere(Ext_Gui2 $oGui = null) {

		$oBoolQuery = self::getListWhere($oGui);

		$oQuery = new Elastica\Query\Terms('type_original', array_values(Ext_Thebing_Inquiry_Document_Search::getTypeData('proforma')));
		$oBoolQuery->addMust($oQuery);

		$oQuery = new Elastica\Query\Term();
		$oQuery->setTerm('is_converted', false);
		$oBoolQuery->addMust($oQuery);

		return $oBoolQuery;

	}
	
	public static function getIndexWhere()
	{
		return array();
	}
	
	public static function getOrderby()
	{
		return array(
			'created_original' => 'DESC'
		);
	}

	public static function convertPlaceholderErrors(array $aErrors) {

		if(!empty($aErrors)) {
			if(isset($aErrors[0]['UNKNOWN_TAG'])) {
				$sPlaceholder = '{'.reset($aErrors[0]).'}';
				$sMessage = str_replace('{placeholder}', '<em>'.$sPlaceholder.'</em>', L10N::t('Der Platzhalter {placeholder} konnte nicht ersetzt werden.', 'Thebing » Errors'));
			} else {
				$sMessage = L10N::t('Beim Ersetzen der Platzhalter ist ein Fehler aufgetreten.', 'Thebing » Errors');
			}

			return [
				'type' => 'error',
				'message' => $sMessage
			];

		}

		return [];

	}
	
	/**
	 * Zahlungen neu berechnen
	 * @param array $_VARS
	 */
	public function requestPaymentTermRows(array $_VARS) {
				
		$oInquiry = $this->getSelectedObject();

		$oPaymentConditionService = new Ext_TS_Document_PaymentCondition($oInquiry, true);
		
		$oPaymentCondition = Ext_TS_Payment_Condition::getInstance((int)$_VARS['save']['payment_condition_select']);
		
		$sDate = Ext_Thebing_Format::ConvertDate($_VARS['save']['date'], null, true);

		$oPaymentConditionService->setPaymentCondition($oPaymentCondition);
		$oPaymentConditionService->setDocumentDate($sDate);

		$aLineItems = array_map(function($aLineItem) {
			$aLineItem['amount'] = Ext_Thebing_Format::convertFloat($aLineItem['amount']);
			$aLineItem['additional_info'] = json_decode($aLineItem['additional'], true);
			return $aLineItem;
		}, $_VARS['position']);

		// TODO Externe Steuer fehlt komplett
		$aPaymentRows = $oPaymentConditionService->generateRows($aLineItems);

		$aTransfer = [
			'action' => 'reloadPaymentTermRows',
			'data' => $aPaymentRows
		];
		
		return $aTransfer;
	}
	
	public function reloadPositionsTable($requestVars) {

		$aSelectedIds			= (array)$requestVars['iDocumentId'];
		$iSelectedId			= reset($aSelectedIds);

		// Logik von _getSelectedIdsForDocument()
		$aOriginalSelectedIds = (array)$requestVars['parent_gui_id'];
		if (empty($aOriginalSelectedIds)) {
			$aOriginalSelectedIds = (array)$requestVars['id'];
		}

		$aInquiryIds			= $aOriginalSelectedIds;
		$iParentId				= (int)reset($aInquiryIds);

		$sLanguage				= $requestVars['language'];
		$sType					= $requestVars['document_type'];
		if(empty($sType)){
			$sType				= $requestVars['type'];
		}
		$iTemplateId			= (int)$requestVars['template_id'];

		if($requestVars['change_user_signature'] == 1) {
			$iSignatureUserId	= (int)$requestVars['save']['signature_user_id'];
		}

		$aEditableFields		= array();
		$aTemplateData			= array();

		// Unterkunftskommunikation

		// Inhalte erst vorbereiten, wenn Sprache gesetzt
		if($sLanguage != '') {

			$oDocument = new Ext_Thebing_Document();
			$oDocument->oGui = $this->_oGui;

			$iInquiryDocumentId = 0;

			$oTemplate = new Ext_Thebing_Pdf_Template($iTemplateId);
			$oTemplateType = new Ext_Thebing_Pdf_Template_Type($oTemplate->template_type_id);

			// Sollte obsolet sein, da die Werte unten nochmal in die Platzhalterklasse gesetzt werden
			// Unterkunftskommunikationsliste ---------------------------------------------
			/*if($oTemplate->type == 'document_accommodation_communication'){

				$oGuiParent = $this->_getParentGui();

				if(
					is_object($oGuiParent) &&
					$oGuiParent instanceof Ext_Gui2
				) {
					// verschlüsselte IDs
					$aSelectedEncodeIds = $aInquiryIds;

					$aSelectedIds = array();
					$aInquiryIds = array();

					foreach((array)$aSelectedEncodeIds as $iId) {

						$aSelectedIdsDecoded = $oGuiParent->decodeId($iId);

						$oDocument->oGui->setOption('document_selected_ids_decoded', $aSelectedIdsDecoded);

						// TODO $aDecodedIds existiert doch überhaupt nicht?
						// Inquiry Accommodation Id
						$iInquiryAccommodationId	= $aDecodedIds['id'];
						// Inquiry Allocation Id
						$iInquiryAllocationId		= $aDecodedIds['allocation_id'];
						// Inquiry Id
						$iInquiryId					= $aDecodedIds['accommodation_inquiry_id'];

						$aSelectedIds[] = (int)$iInquiryAllocationId;
						$aInquiryIds[] = (int)$iInquiryId;
					}

					$iSelectedId = (int)reset($aInquiryIds);
				}

			}*/
			// --------------------------------------------------------------------------

			// Wenn mehrere Inquiries ausgewählt sind
			if(count($aInquiryIds) > 1) {

				$sInquiryIdField = $this->_oGui->getOption('decode_inquiry_id_additional_documents');
				if(!empty($sInquiryIdField)){
					$aInquiryIds = $this->_oGui->decodeId($aInquiryIds, $sInquiryIdField);
				}

				// Templateinhalte holen
				$aTemplateData = $oDocument->getTemplateDataPreview($oTemplate, $iSignatureUserId, $sLanguage);
				$bPreview = true;
				$oInquiry = false;

			} else {

				// Dokumenttyp Insurance speziell behandeln
				if($sType == 'insurance') {

					$oLink		= new Ext_TS_Inquiry_Journey_Insurance($iParentId);
					$iInquiryId = (int)$oLink->inquiry_id;
					$iInquiryDocumentId = (int)$oLink->document_id;

				} else {

					$iInquiryId = (int)$iParentId;
					$iInquiryDocumentId = (int)$iSelectedId;

				}

				// Falls enkodiert, Inquiry ID dekodieren
//						$aConvertedInquiryIds	= $this->_decodeSelectedIdsForDocument(array($iInquiryId));
//						$iInquiryId = reset($aConvertedInquiryIds);

//						/* @var $oInquiry Ext_TS_Inquiry_Abstract */
//						$oInquiry = $oTemplate->getObjectFromType($iInquiryId);
				$oInquiry = $this->getSelectedObject();

				//neu oder alt spielt keine Rolle, wichtig ist nur der Typ,das Template und der Inquiry
				//das wird benötigt für die Anzeige der Rechnungspositionen
				//wenn kein Rechnungsdokument, dann nur Positionen anzeigen falls schon Rechnungen vorhanden sind
				$oInquiryDocument = $oInquiry->newDocument($sType, false);

				// Optionen
				$aOptions = array();
				$aOptions['selected_ids'] = $aSelectedIds;
				$aOptions['parent_id'] = $iParentId;

				// Templateinhalte holen
				$aTemplateData = $oDocument->getTemplateData($oInquiry, $oTemplate, $oInquiryDocument, $iSignatureUserId, $sLanguage, $aOptions);
				$bPreview = false;

			}

			$iInquiryPositionsView = (int)$aTemplateData['inquirypositions_view'];

			// Alle editierbaren Felder dieses Layouts bestimmen
			$aTemplateTypeEditableFields = $oTemplateType->getEditableElements();

			if(!$aTransfer['data']) {
				$aTransfer['data'] = array();
			}

			if(
				$iInquiryPositionsView > 0 //&&
				//$iInquiryDocumentId <= 0 // nur wenn es nicht auf einer Rechnung bassiert da dann die Pos fest vorgegegeben sind
			) {
				// Selbes Objekt wie in \Ext_Thebing_Document_Positions gesetzt wird
				$document = \Ext_Thebing_Inquiry_Document::getInstance((int)$requestVars['document_id']);

				$oPaymentConditionService = new Ext_TS_Document_PaymentCondition($oInquiry);
				$oPaymentConditionService->setPaymentTerms(Ext_TS_Document_PaymentCondition::convertRequestPaymentTerms($this->_oGui->getRequest()));

				// Vielleicht auch aus $requestVars['save']['date']?
				if ($document->exist() && $document->isReleased()) {
					// Bei freigegebenen Rechnungen darf hier kein neues Datum gesetzt werden
					$oPaymentConditionService->setDocumentDate($document->getLastVersion()->date);
				} else if(!empty($aTemplateData['element_date_html'])) {
					$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
					$sDate = $oDateFormat->convert($aTemplateData['element_date_html']);
					$oPaymentConditionService->setDocumentDate($sDate);
				} else {
					// Falls Datumsfeld fehlt oder {today} nicht im Platzhalter steht (sollte eigentlich nicht vorkommen)
					$oPaymentConditionService->setDocumentDate(date('Y-m-d'));
				}

				if(!empty($requestVars['save']['payment_condition_select'])) {
					// Wenn ID vorhanden, immer überschreiben, da bei eigenen Einträgen das Select leer sein muss
					$oPaymentConditionService->setPaymentCondition(Ext_TS_Payment_Condition::getInstance($requestVars['save']['payment_condition_select']));
				} elseif(
					!empty($requestVars['is_credit']) ||
					strpos($sType, 'creditnote') !== false
				) {
					// Auf leer setzen, da das bei Credit/Creditnote so keinen Sinn macht (auch in getEditDialog())
					$oPaymentConditionService->setPaymentCondition(new Ext_TS_Payment_Condition());
				}

				$oPositions = Ext_Thebing_Document_Positions::getInstance();
				$oPositions->oGui					= $this->_oGui;
				$oPositions->oInquiry				= $oInquiry;
				$oPositions->sType					= $sType;
				$oPositions->iInquiryDocumentId		= (int)$requestVars['document_id'];
				$oPositions->iSourceDocumentId		= (int)$requestVars['source_document_id'];
				$oPositions->iTemplateId			= $iTemplateId;
				$oPositions->sLanguage				= $sLanguage;
				$oPositions->bNegate				= (boolean)$requestVars['negate'];
				$oPositions->bRefresh				= (boolean)$requestVars['refresh'];
				$oPositions->bIsCredit				= (bool)$requestVars['is_credit'];
				$oPositions->sDialogId				= $this->buildDocumentDialogId($aInquiryIds);
				$oPositions->sSelectedAddress = $requestVars['save']['address_select'];
				$oPositions->iPartialInvoice = (int)$requestVars['save']['partial_invoice'];
				$oPositions->oPaymentConditionService = $oPaymentConditionService;
				$oPositions->companyId = (int)$requestVars['save']['company_id'];

				if(isset($requestVars['save']['invoice_select'])) {
					$oPositions->iInvoiceSelectDocumentId = (int)$requestVars['save']['invoice_select'];
				}

				$sPositionHtml = $oPositions->getTable();

				$aTransfer['data']['total_amount_column'] = $oPositions->sTotalAmountColumn;

				$aTransfer['data']['position_tooltips'] = $oPositions->aPositionsTooltips;

				$aTransfer['data']['html']		= $sPositionHtml;
				$aTransfer['data']['update']	= true;

				$iSchoolId = null;
				// Nummernkreis
				if($oInquiry instanceof Ext_TS_Inquiry) {
					$oInbox = $oInquiry->getInbox();
					Ext_TS_NumberRange::setInbox($oInbox);
					Ext_TS_NumberRange::setCurrency($oInquiry->getCurrency());
					$iSchoolId = $oInquiry->getSchool()->id;
				}

				if(!empty($oPositions->companyId)) {
					Ext_TS_NumberRange::setCompany($oPositions->companyId);
				}

				$sTypeNumberRange = $oInquiry->getTypeForNumberrange($sType);

				$aNumberranges = (array)Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangesByType($sTypeNumberRange, $oPositions->bIsCredit);
				$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($sTypeNumberRange, $oPositions->bIsCredit, $iSchoolId);

				\System::wd()->executeHook(self::HOOK_DEFAULT_NUMBERRANGE, $sTypeNumberRange, $oPositions->bIsCredit, $iSchoolId, $oNumberrange, $oTemplate);

				// Prüfen, ob Defaultnummernkreis in den Optionen ist
				if(
					$oNumberrange->id > 0 &&
					!array_key_exists($oNumberrange->id, $aNumberranges)
				) {
					$aNumberranges[$oNumberrange->id] = $oNumberrange->name;
				}

				$aTransfer['data']['numberrange'] = [
					'options' => \Ext_Thebing_Util::convertOptionArrayToJsOptionsArray($aNumberranges),
					'default' => $oNumberrange->id
				];

			} else {
				$aTransfer['data']['update']	= false; 
			}

			/**
			 * Editierbare Felder
			 */
			$aEditableFields = array();

			foreach((array)$aTemplateTypeEditableFields as $oField){
				if($oField->editable == 1) {
					$aFieldData = array();
					$aFieldData['id']		= $oField->id;
					$aFieldData['type']		= $oField->element_type;
					$aFieldData['name']		= $oField->name;

					// Value und Platzhalter ersetzen
					$sValue = $oField->getValue($sLanguage, $oTemplate->id);

					// Wenn nicht Mehrfachauswahl
					if(!$bPreview) {

						if($oTemplate->use_smarty) {

							$oReplace = $oDocument->getPdfPlaceholderObject($oInquiryDocument->getLatestVersionOrNew());
							$oReplace->setDisplayLanguage($sLanguage);

						} else {

							if ($oInquiry instanceof Ext_Thebing_Teacher) {
								$oReplace = new Ext_Thebing_Teacher_Placeholder($oInquiry->id);
							} else {
								$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool();
								$aParams = array(
									'inquiry' => $oInquiry,
									'contact' => $oInquiry->getCustomer(),
									'school_format' => $oSchoolForFormat->id,
									'template_type' => $oTemplate->type,
									'options' => array(
										'selected_ids' => $aSelectedIds,
										'parent_id' => (int)reset($requestVars['parent_gui_id'])
									),
								);

								$oReplace = $oInquiry->createPlaceholderObject($aParams);
							}


							$oReplace->sTemplateLanguage = $sLanguage;

							// Selektierte Adresse manuell einfügen in die Platzhalterklasse
							// Wird benötigt für die speziellen Dokumente-Platzhalter, die sich auf die Adresse beziehen
							$aSelectedAddress = Ext_Thebing_Document_Address::getValueOfAddressSelect($requestVars['save']['address_select']);
							$oReplace->setAdditionalData('document_address', $aSelectedAddress);

							// Enkodierte Daten setzen; wichtig für Platzhalter
							$aSelectedIdsDecoded = (array)$this->_decodeSelectedIdsForDocument($aOriginalSelectedIds, true);

//									// Deprecated. Eigentlich reicht es das in das Platzhalterobjekt zu setzen
//									$this->_oGui->getParent()->setOption('document_selected_ids_decoded', reset($aSelectedIdsDecoded));
							$oReplace->setOption('document_selected_ids_decoded', reset($aSelectedIdsDecoded));
							$oReplace->oGui = $this->_oGui;

							// Objekte die die Platzhalterklasse noch benötigt
//									if(
//										$oTemplate->type == 'document_accommodation_communication' &&
//										count($aSelectedIds) == 1
//									) {
//
//										$iAllocationId = (int) reset($aSelectedIds);
//										$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iAllocationId);
//										$oReplace->_oAllocation = $oAccommodationAllocation;
//									}

						}

						$sValue	= $oReplace->replace($sValue, 0);

						if($oTemplate->use_smarty) {
							$aErrors = $oReplace->getErrors();
							if(!empty($aErrors)) {
								// In data reinschreiben, weil man im JS das originale Data nicht mehr hat
								$aTransfer['data']['error'][] = self::convertPlaceholderErrors($aErrors);
								break;
							}
						}

					}

					$aFieldData['value']	= $sValue;

					$aEditableFields[] = $aFieldData;

				}
			}

			// Daten für Dokumente-Tab (Attached Additional Documents)
			if(
				$requestVars['load_attached_documents'] &&
				Ext_Thebing_Access::hasRight('thebing_invoice_dialog_document_tab')
			) {
				$aTransfer['data']['documents_tab'] = $oDocument->getAttachedAdditionalDocumentTabHtml($oInquiry);
			}

		}

		$bWriteValues = 1;
		if(
			$requestVars['task'] == 'savePositionDialog' || 
			$requestVars['change_user_signature'] == 1
		) {
			// Beim speichern einer Position, sollen die anderen Templatedaten nicht neu geladen werden
			$bWriteValues = 0;
		}

		$aTransfer['data']['document_type']			= $requestVars['document_type'];
		$aTransfer['data']['write_values']			= $bWriteValues;
		$aTransfer['data']['template_field_data']	= $aTemplateData;
		$aTransfer['data']['editable_fields']		= $aEditableFields;
		$aTransfer['data']['']		= $aEditableFields;
		$aTransfer['data']['id']					= $this->buildDocumentDialogId($aInquiryIds); // Wichtig, damit der Dialog »sich selber wieder findet«
		$aTransfer['action']						= 'reloadPositionsTable';
		$aTransfer['data']['inquirypositions_view']	= $iInquiryPositionsView;
		$aTransfer['data']['change_user_signature']	= (int)$requestVars['change_user_signature'];

		return $aTransfer;
	}

	public function reloadTemplateLanguageSelect($_VARS) {

		$iTemplate		= $_VARS['iTemplate'];

		$oObject = $this->getSelectedObject();

//				$aSelectedIds	= $this->_getSelectedIdsForDocument($_VARS);
//				$aSelectedIds	= $this->_decodeSelectedIdsForDocument($_VARS['id']);
//
//				$oTemplate		= Ext_Thebing_Pdf_Template::getInstance($iTemplate);
//
//				$iInquiryId		= (int)reset($aSelectedIds);
//				$iBasicId		= (int)reset($aSelectedIds);
//
//				$oObject		= $oTemplate->getObjectFromType($iBasicId);

		if ($oObject instanceof \Ext_Thebing_Teacher) {
			$oSchool = $oObject->getSchool();
			$sLangCustomer	= $oObject->getLanguage();
		} else {
			$oSchool		= $oObject->getSchool();
			/* @var $oSchool Ext_Thebing_School */
			$oCustomer		= $oObject->getCustomer();
			$sLangCustomer	= $oCustomer->getLanguage();
		}

		$sLangSchool	= $oSchool->getInterfaceLanguage();
		$aSchoolLanguages = $oSchool->getLanguageList(true);

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($iTemplate);
		$aLanguages = $oTemplate->languages;

		$aLangs = array();

		$i = 1;

		$aLanguagesLabels = Ext_Thebing_Data::getAllCorrespondenceLanguages();

		$aLangs[0] = array('', '&nbsp;');

		foreach((array)$aLanguages as $sLang){				
			// Es muss geprüft werden ob die gerade aktive Schule auch die Templatesprache verwenden darf
			if(!isset($aSchoolLanguages[$sLang])){
				continue;
			}

			$aLangs[$i][] = $sLang;
			$aLangs[$i][] = $aLanguagesLabels[$sLang];
			$i++;
		}

		$oTemp = $this->_oGui->createDialog();
		$oDiv = $oTemp->createNotification($this->_oGui->t('Achtung'), $this->_oGui->t('Die Standardsprache steht in diesem Template nicht zur Verfügung'), 'hint', array('row_id' => 'error_template_language'));
		$sInfoHtml = $oDiv->generateHTML();

		$sPlaceholderTabContent = $oTemplate->getPlaceholderTabContent();

		// Logik von _getSelectedIdsForDocument
		$aSelectedIds = (array)$_VARS['parent_gui_id'];
		if (empty($aSelectedIds)) {
			$aSelectedIds = (array)$_VARS['id'];
		}
		sort($aSelectedIds);

		$aTransfer['data']['id'] = $this->buildDocumentDialogId($aSelectedIds);
		$aTransfer['data']['languages']				= $aLangs;
		$aTransfer['data']['default_language']		= $sLangCustomer;
		$aTransfer['data']['school_language']		= $sLangSchool;
		$aTransfer['data']['info_html']				= $sInfoHtml;
		$aTransfer['data']['placeholder_html'] = $sPlaceholderTabContent;
		$aTransfer['action']						= 'reloadTemplateLanguageSelect';

		return $aTransfer;
	}
	
	public function openPositionDialog($_VARS) {

		$sLanguage = $_VARS['language'];

		$oLanguage = new \Tc\Service\Language\Frontend($sLanguage);

		$oDocument = Ext_Thebing_Inquiry_Document::getInstance($_VARS['document_id']);

		$oInquiry		= $this->getSelectedObject();

		$oSchool		= $oInquiry->getSchool();

		$mPositionKey		= $_VARS['position_key'];

		$aSubPositions		= (array)$this->_oGui->getDocumentPosition($mPositionKey);

		$aSelectedIds = array();

		// TODO: Das ist hier redundant mit der eigentlichen Logik in \Ext_Thebing_Document_Positions::getTable() und anderswo
		$iEditable = 1;
		if(
			// Freigegeben oder Gutschrift: Beträge dürfen nicht editierbar sein
			$oDocument->exist() &&
			$oDocument->isReleased() ||
			$_VARS['negate']
		) {
			$iEditable = 2;
		}

		$oPositions = Ext_Thebing_Document_Positions::getInstance();
		$oPositions->oGui = $this->_oGui;
		$oPositions->oInquiry = $oInquiry;
		$oPositions->bPositionTable = true;

		$bGroup = $oInquiry->hasGroup();

		$sView = 'gross';
		if(isset($_VARS['amount_provision'])) {
			if($_VARS['document_type'] == 'creditnote') {
				$sView = 'creditnote';	
			} else {
				$sView = 'net';	
			}
		}

		$aPositionColumns	= $oPositions->getColumns($sView, $bGroup, true);
		$iPositionColumns = count($aPositionColumns);

		$sHtml = '';

		$sHtml .= $oPositions->writeTableHead($aPositionColumns, 'tblSubDocumentPositions');

		$oRow = new Ext_Thebing_Document_Positions_Row;
		$oRow->bDetailView = true;
		if($oSchool) {
			$oRow->iSchoolId = $oSchool->id;
		}

		$aSubPositions	= (array)$aSubPositions;
		$iPositionCount = 0;
		$iCount			= count($aSubPositions);

		foreach($aSubPositions as $aItem) {

			$iTempPositionKey = $aItem['position_key'];

			$aItem['position_key'] = 'SI'.$iPositionCount;

			$oRow->aPosition = $aItem;
			$oRow->aPositionColumns = $aPositionColumns;
			$oRow->iEditable = $iEditable;
			$oRow->bPositionTable = true;

			if($iCount - $iPositionCount == 1)
			{
				$oRow->bLast = true;
			}

			$sHtml .= $oRow->generateHtml($aTotalAmounts, $aTaxAmounts);

			$aItemCommission = array(); 

			$aItemCommission['amount_discount'] = $aItem['amount_discount'];
			$aItemCommission['amount'] = (Ext_Thebing_Format::convertFloat($aItem['amount'], $oSchool->id) / 100) * Ext_Thebing_Format::convertFloat($aItem['amount_discount'], $oSchool->id);

			$aItemCommission['description'] = $aItem['description_discount'];

			$aItemCommission['position_key'] = 'SC'.$iPositionCount;

			$aItemCommission['status'] = $aItem['status'];

			$aItemCommission['onPdf'] = $aItem['onPdf']; // Für die korrekte Classe im HTML

			$oRow->aPosition = $aItemCommission;
			$oRow->aPositionColumns = $aPositionColumns;
			$oRow->iEditable = $iEditable;
			$oRow->bDiscount = true;

			$sHtml .= $oRow->generateHtml($aTotalAmounts, $aTaxAmounts);

			$iPositionCount++;

		}

		$sHtml .= '</table>';

		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->id = 'position_key_hidden';
		$oHidden->value = $_VARS['position_key'];
		$oHidden->type = 'hidden';
		$sHtml .= $oHidden->generateHtml();

		$sHtml .= Admin_Html::generateHiddenField('negate', $_VARS['negate']);
		$sHtml .= Admin_Html::generateHiddenField('refresh', $_VARS['refresh']);
		$sHtml .= Admin_Html::generateHiddenField('iDocumentId', $_VARS['document_id']);
		$sHtml .= Admin_Html::generateHiddenField('language', $_VARS['language']);
		$sHtml .= Admin_Html::generateHiddenField('document_type', $_VARS['document_type']);
		$sHtml .= Admin_Html::generateHiddenField('template_id', $_VARS['template_id']);
//				$sHtml .= Admin_Html::generateHiddenField('main_id', $iSelectedId);

		// Das war früher main_id und wurde in Ext_TS_Enquiry_Offer_Gui2_Data::_getSelectedIdsForDocument() speziell abgefangen vor allen  anderen IDs
		if ($this->oWDBasic instanceof Ext_TS_Inquiry_Journey) {
			// Möglichst merkwürdiger Key, damit die Steigerung der zusammengehackten IDs klar erkenntlich ist
			$sHtml .= Admin_Html::generateHiddenField('major_journey_id', $oInquiry->getJourney()->id);
		}

		$oDialog = $this->_oGui->createDialog($this->t('Positionsdetails'));

		$oDialog->width = 1200;
		$oDialog->height = 600;
		$oDialog->sDialogIDTag = 'DOCUMENT_POSITIONS_';

		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		$aData['html']					= $sHtml;
		$aData['title']					= L10N::t('Position bearbeiten', $this->_oGui->gui_description);
		$aData['task']					= 'savePositionDialog';
		$aData['save_id']				= $_VARS['position_key'];
		$aData['selectedRows']			= $aSelectedIds;
		$aData['bSaveButton']			= 1;
		$aData['discount_description']	= Ext_Thebing_Document::getDiscountDescription(null, $oLanguage);

		$aTransfer['action'] 	= 'openDialog';
		$aTransfer['task']		= 'openPositionDialog';
		$aTransfer['data']		= $aData;
		$aTransfer['error'] 	= array();

		return $aTransfer;
	}		
	
}
