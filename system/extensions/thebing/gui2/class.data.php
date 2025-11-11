<?php

/**
 * @TODO Diese Klasse ist quasi ein Trait für Inquiry-Funktionen, Dokumente und Payments und sollte nicht mehr verwendet werden, wenn nicht zwingend notwendig!
 * @deprecated
 *
 * Die klasse leitet die Datenklasse der GUI2 ab für das Thebing Projekt
 */
class Ext_Thebing_Gui2_Data extends Ext_TC_Gui2_Data {
			
	/**
	 * doppelte Inquiry-IDs rauswerfen (falls gewünscht)...bei der Schülerliste für Versicherungen darf das
	 * zum Beispiel nicht passieren, da ein Schüler mehrere Versicherungen haben kann; es soll aber für jede Versicherung
	 * der Buchung ein Dokument erzeugt werden
	 * 
	 * @var boolean 
	 */
	protected $_bUniqueInquiriesDocuments = true;
	
	
	protected function _getPdfMergeObject() {
		$oMergeClass = new Ext_Thebing_Pdf_Merge();

		return $oMergeClass;
	}
	
	public static function getOrderby()
	{
		return ['ts_i.created' => 'DESC'];
	}

	public static function getBookingType(\Ext_Gui2 $oGui)
	{
		$aBookingType = [
			'customer'	=>	$oGui->t('Direktbuchungen'),
			'agency'	=>	$oGui->t('Agenturbuchungen')
		];

		return $aBookingType;
	}

	public static function getAgencies()
	{
		$oClient = \Ext_Thebing_System::getClient();
		$aAgencies = $oClient->getAgencies(true);

		return $aAgencies;
	}

	public static function getInboxes()
	{
		$oClient = \Ext_Thebing_System::getClient();
		$aInboxes = $oClient->getInboxList(true, true);

		return $aInboxes;
	}

	public function getTranslations($sL10NDescription){

		// Übersetzungspfad der für die generellen ATG Übersetzungen
		$sL10NAllGUIs					= Ext_Gui2::$sAllGuiListL10N;

		$aData = parent::getTranslations($sL10NDescription);

		// global verfügbar! Da sonst in jeder Liste zu übersetzen
		$aData['amount_exceeded']			= $this->t('Maximal erlaubter Betrag wurde überschritten!');
		$aData['amount_overpayment_impossible'] = $this->t('Eine Überbezahlung ist nicht möglich.');
		$aData['discount']					= $this->t('Rabatt!');
		$aData['search']					= $this->t('Suche', $this->_oGui->gui_description);
		$aData['confirmCanceled']			= $this->t('Möchten Sie diesen Kunden wirklich stornieren?');
		$aData['confirmCanceledProforma']	= $this->t('Proformarechnungen werden gelöscht.');
		$aData['compare_prepay_date']		= $this->t('Das errechnete Anzahlungsdatum entspricht, bzw. liegt zeitlich nach dem Restzahlungsdatum. Der Anzahlungsbetrag wird auf 0 gesetzt.');
		$aData['compare_document_date']		= $this->t('Das An/Restzahlungsdatum liegt zeitlich vor dem Rechnungsdatum. Die Daten werden angepasst.');


		return $aData;

	}
	
	/*
	 * Liefert das Label wenn existierende joined Items noch mit der ID verknüpft sind im Fehlerdialog
	 */
	protected function _getJoinedItemsErrorLabel($sLabel) {
		switch($sLabel){
			case 'subcategory_jointables_account':
				$sLabel = $this->t('Kontozuweisungen');
				break;
			case 'accommodations':
				$sLabel = $this->t('Unterkünften');
				break;
			case 'subcategory_jointables':
				$sLabel = $this->t('Unterkategorien');
				break;
			case 'accommodations_allocations':
				$sLabel = $this->t('Raumbelegungen');
				break;
			case 'rooms':
				$sLabel = $this->t('Räumen');
				break;
			case 'room':
				$sLabel = $this->t('Räumen');
				break;
			case 'accommodation_selaries':
				$sLabel = $this->t('Unterkünften');
				break;
			case 'teacher_selaries':
				$sLabel = $this->t('Lehrern');
				break;
			case 'classes_courses':
				$sLabel = $this->t('Klassen');
				break;
			case 'course_costs':
				$sLabel = $this->t('Kurszusatzkosten');
				break;
			case 'validity_jointables':
				$sLabel = $this->t('Schulstorno');
				break;
			default:
				$sLabel = parent::_getJoinedItemsErrorLabel($sLabel);
		}
		
		return $sLabel;
	}
	
	// Export methode
	protected function export($sTitle, $aExport, $sType = 'csv') {

		if($sType == 'csv') {
			$this->exportCSV($sTitle, $aExport);
		} else if($sType == 'xls') {
			//TODO
		} else if($sType == 'pdf') {
			//TODO
		}

	}

	/**
	 * {@inheritdoc}
	 *
	 * $sIconAction muss hier per REFERENZ übergeben werden! (wird innerhalb der Methode verändert)
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false) {
		global $_VARS;

		if (in_array($sIconAction, ['edit_dialog_info_icon'])) {
			// Core-/Framework-Aktionen weitergeben
			return parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		}

		$aSelectedIds		= (array) $aSelectedIds;
		$iSelectedId		= (int) reset($aSelectedIds);
		$bIsMultipleAdditionalDocuments = false;
		$bNegate			= false;
		$originalAction = $_VARS['action'];

		$oSchoolForFormat	= Ext_Thebing_Client::getFirstSchool($this->_oGui->access);

		$oInquiryDocument = null;
		if(!empty($iSelectedId)) {
			$oInquiryDocument = Ext_Thebing_Inquiry_Document::getInstance($iSelectedId);
		}
		
		// Document Edit aufrufe wurden verändert
		// hier werden die daten ensprechend ummodeliert damit
		// es mit der alten struktur läuft
		if(isset($_VARS['convert_document_data']) && $_VARS['convert_document_data'] == 1){

			$_VARS['iDocumentId'] = $iSelectedId;
			if($_VARS['bNoDocumentId'] == 1){
				$_VARS['iDocumentId'] = 0;
			}

			if($_VARS['type'] == "") {
				$_VARS['type'] = $oInquiryDocument->type;
			}
			$sIconAction = 'document_edit';
			$aSelectedIds = (array) $_VARS['parent_gui_id'];
			if($_VARS['negate'] == 1){
				$bNegate = true;
			}
		}

		if(
			count($aSelectedIds) > 1 &&
			$sIconAction == 'additional_document'
		){
			$bIsMultipleAdditionalDocuments = true;
			$sIconAction = 'document_edit';
			$_VARS['type'] = 'additional_document';
			$_VARS['iDocumentId'] = 0;
		}

		$aCommunicationApplicationAllocations = Ext_Thebing_Communication::getApplicationAllocations();

		$bOnlyCreditNotes	= false;
		$bStornoError		= false;
		// get dialog object
		switch($sIconAction) {
			case 'invoice':
			case 'additional_document':
			case 'insurance':
			case 'proforma':
			case 'invoice_only_cn':{
					$aIconData = $this->aIconData[$sIconAction];

					if(empty($aIconData['additional'])){
						$sType = $sIconAction;
					}else{
						$sType = $aIconData['additional'];
					}

					// Provisionen ausbezahlen
					if($sIconAction == 'invoice_only_cn'){
						$sType = 'invoice';
						$bOnlyCreditNotes = true;
					}

					$sInquiryIdField = $this->_oGui->getOption('decode_inquiry_id_additional_documents');
					if(!empty($sInquiryIdField)){
						$aSelectedIds = $this->_oGui->decodeId($aSelectedIds, $sInquiryIdField);
					}

					if(empty($_VARS['template_type'])){
					   $_VARS['template_type'] = $this->_oGui->getOption('template_type');
					}

					$oDialog = Ext_Thebing_Document::getDialog($this->_oGui, $aSelectedIds, $sType, $bOnlyCreditNotes, $_VARS['template_type'], $oSchoolForFormat->id, $_VARS['data_class']);

					break;
				}
			case 'document_edit':{

					if ($this->_oGui->getOption('document_class')) {
						$oDocument = $this->_oGui->getOption('document_class');
						break;
					}

					$aStornoCheck = array();
					
					// Bei Storno muss erst geprüft werde ob storniert werden darf
					if(
						$originalAction == 'storno' &&
						$oInquiryDocument
					) {
						$oInquiry = $oInquiryDocument->getInquiry();						
						$aStornoCheck = $oInquiry->checkStornoConditions();
					}

					if(empty($aStornoCheck)){
						
						$oDocument = new Ext_Thebing_Document();

						$oParentGui = $this->_getParentGui();

						$sInquiryIdField = $this->_oGui->getOption('decode_inquiry_id_additional_documents');

						if(is_object($oParentGui) && $oParentGui instanceof Ext_Gui2) {
						
							$sInquiryIdField = $oParentGui->getOption('decode_inquiry_id_additional_documents');
							if(!empty($sInquiryIdField)){
								$aSelectedIds = $oParentGui->decodeId($aSelectedIds, $sInquiryIdField);
							}
			
						} elseif(!empty($sInquiryIdField)) {
							$aSelectedIds = $this->_oGui->decodeId($aSelectedIds, $sInquiryIdField);
						}

						$oDocument->bNegate = $bNegate;

						// Neue Logik weil das alte Chaos ist (das alte schmeißen wir eh weg bald)
						switch($originalAction) {
							// Komplett neues Dokument 
							case 'new_proforma':
							case 'new_invoice':
							case 'diff_customer_partial':
							case 'storno':
								break;
							case 'diff_customer':
							case 'diff_agency':
								
								// Manipulation für Diff-Proformas
								if(
									$_VARS['type'] == 'brutto_diff' &&
									$oInquiryDocument &&
									strpos($oInquiryDocument->type, 'proforma_') !== false
								) {
									$_VARS['type'] = 'proforma_brutto_diff';
								} elseif(
									$_VARS['type'] == 'netto_diff' &&
									$oInquiryDocument &&
									strpos($oInquiryDocument->type, 'proforma_') !== false
								) {
									$_VARS['type'] = 'proforma_netto_diff';
								}								
								
								break;							
							// Vorhandenes Dokument
							case 'edit_proforma':
							case 'refresh_proforma':
							case 'edit_invoice':
							case 'edit_additional_document':
							case 'refresh_invoice':
								$oDocument->documentId = $iSelectedId;
								break;
							case 'creditnote_refresh':
								$oDocument->sourceDocumentId = $iSelectedId;
							case 'creditnote_edit':
								// In der Liste wird die Rechnung markiert, daher muss man die zugehörige Creditnote ermitteln
								$creditNote = $oInquiryDocument->getCreditNote();
								$oDocument->documentId = $creditNote->id;
								// Damit im Dialog-Titel die richtigen Werte ersetzt werden, braucht man hier das richtige Objekt
								$this->oWDBasic = $creditNote;
								break;
							// Neues Dokument auf Basis eines vorhandenen
							case 'creditnote_subagency_refresh':
								$oDocument->sourceDocumentId = $iSelectedId;
							case 'creditnote_subagency_edit':
								// In der Liste wird die Rechnung markiert, daher muss man die zugehörige Creditnote ermitteln
								$creditNote = $oInquiryDocument->getCreditNoteSubAgency();
								$oDocument->documentId = $creditNote->id;
								// Damit im Dialog-Titel die richtigen Werte ersetzt werden, braucht man hier das richtige Objekt
								$this->oWDBasic = $creditNote;
								break;
							// Neues Dokument auf Basis eines vorhandenen
							case 'negate_invoice':
								$oDocument->bNegate = true;
								$oDocument->documentId = 0;
								$oDocument->sourceDocumentId = $iSelectedId;
								break;
							case 'mark_as_canceled':
							case 'creditnote_new':
							case 'creditnote_subagency_new':
								$oDocument->documentId = 0;
								$oDocument->sourceDocumentId = $iSelectedId;
								break;
							// Zusatzdokumente
							case 'additional_document':
							case 'new_additional_document':
							case 'delete_additional_document':
							case 'merge_additional_document':
								break;
							default:
								throw new RuntimeException('Unknown action "'.$originalAction.'"');
						}
						
						$oDialog = $oDocument->getEditDialog($this->_oGui, $oDocument->documentId, $_VARS['type'], $aSelectedIds);
						
					} else {
						$bStornoError = true;
						// Hinweisdialog
						$oDialog = Ext_Thebing_Storno_Condition::getDialog($iSelectedId, $aStornoCheck, $this->_oGui);
					}
					
					break;
				}
			case 'payment':{
				$oDialog = Ext_Thebing_Inquiry_Payment::getDialog($this->_oGui, $aSelectedIds, $_VARS['parent_gui_id'], $this->_oGui->access, $sAdditional);
				break;
			}
		}

		if(
			$sIconAction == 'communication' && 
			!isset($aCommunicationApplicationAllocations[$sAdditional])
		) {
			$oDialog = Ext_Thebing_Communication::getDialog($this->_oGui, $aSelectedIds, $sAdditional, $_VARS);
		}

		// Wenn kein Dialog Objekt, dann Fehler zurückgeben
		if(!$oDialog instanceof Ext_Gui2_Dialog){
			$aErrors = array();
			foreach((array) $oDialog as $sError){
				if($sError){
					$aErrors[] = L10N::t($sError, $this->_oGui->gui_description);
				}
			}
			$aTransfer = array();
			$aTransfer['action'] = 'showError';
			$aTransfer['error'] = $aErrors;
			$aTransfer['dialog_id'] = 'DOCUMENTS_LIST_' . implode('_', (array) $aSelectedIds);
			echo json_encode($aTransfer);
			$this->_oGui->save();
			die();
		}

		// Der direkte Aufruf ist nicht korrekt, da die Dialoge so nicht eine eigene Data-Klasse haben können
		//$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		$aData = $oDialog->getDataObject()->getHtml($sIconAction, $aSelectedIds, $sAdditional);

		// get dialog values
		switch($sIconAction){
			case 'invoice':
			case 'additional_document':
			case 'insurance':
			case 'proforma':
					$aData['values'] = array();
					break;
			case 'document_edit':

				if(!$bStornoError) {
					
					$oInquiryDocument = Ext_Thebing_Inquiry_Document::getInstance($oDocument->documentId);

					//Default Inquiry Wert falls $oInquiryDocument ein neues Objekt ist, muss von außerhalb gesetzt werden,
					//weil die Angebote auch diese Funktion benutzt
					// TODO Entfernen, das scheint oder muss schon von \Ext_Thebing_Document::getEditDialog() gesetzt sein
					if(
						$_VARS['template_type'] !== 'document_teacher' &&
						!$oDocument->getInquiry()
					) {
						throw new \LogicException('Deprecated code.');

						$iSelectedId = (int) reset($aSelectedIds);
						$oInquiry = Ext_TS_Inquiry::getInstance($iSelectedId);
						$oDocument->setInquiry($oInquiry);

						// Abstürzen, da die Enquiry vorher irgendwie hätte gesetzt werden müssen und sonst jetzt überschrieben wäre
						if($_VARS['template_type'] === 'document_student_requests') {
							throw new RuntimeException('Enquiry for additional document not set and missing!');

						}
					}

					$aData = $oDocument->getAdditionalDataForDialog($oInquiryDocument, $aData);

					break;
				}				
		}

		if(
			$sIconAction == 'communication' && 
			!isset($aCommunicationApplicationAllocations[$sAdditional])
		) {
			$aData['values'] = array();
		}

		return $aData;
	}
	
	/*
	 * Die Methode speichert den Editierdialog
	 * überschreibt die parent methode
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {
		global $_VARS;

		$aCommunicationApplicationAllocations = Ext_Thebing_Communication::getApplicationAllocations();

		if($sAction == 'document_edit')	{

			$oDocument = new Ext_Thebing_Document();
			$oDocument->oGui = $this->_oGui;

			$aTransfer = $oDocument->saveDialogData($aSelectedIds, $_VARS, $this->_bUniqueInquiriesDocuments);

			if(
				isset($aTransfer['error']) &&
				!empty($aTransfer['error']) 
			){
				
				//Fehlermeldungen in der DokumentenData auslagern
				$oDataDocument		= new Ext_Thebing_Document_Gui2($this->_oGui);
				
				if(isset($this->aIconData[$sAction])){
					//Dialog setzen für die Labels in den Fehlermeldungen
					$oDialogData						= $this->aIconData[$sAction];
					$oDataDocument->aIconData[$sAction] = $oDialogData; 
				}elseif(isset($this->aIconData['additional_document'])){
					//Dialog setzen für die Labels in den Fehlermeldungen
					$oDialogData						= $this->aIconData['additional_document'];
					$oDataDocument->aIconData[$sAction] = $oDialogData;
				}

				$aErrors[]			= L10N::t('Fehler beim Speichern', Ext_Gui2::$sAllGuiListL10N);
				//Fehler für den Dialog korrekt formen
				$aErrorsConverted	= $oDataDocument->getErrorData($aTransfer['error'], $sAction, 'error', false);
				$aErrors			= array_merge($aErrors,$aErrorsConverted);

				$aTransfer['error'] = $aErrors;
			}

			return $aTransfer; 

		} elseif($sAction == 'communication' && !isset($aCommunicationApplicationAllocations[$sAdditional])) {

			// aSelected Ids aus Hiddenfeld da nicht auto. mitgeschickt :(
			$aSelectedIds = explode('_', ($_VARS['save']['selected_ids'] ?? ''));

			$sIconKey = Ext_Thebing_Gui2_Data::getIconKey($sAction, $sAdditional);
			$aIconData = $this->aIconData[$sIconKey];

			if($bSave) {
				$aData = [];
				$aData['additional'] = $aIconData['additional'];
				$aData['action'] = 'communication';
				$aTransfer = Ext_Thebing_Communication::saveDialogData($this->_oGui, $aSelectedIds, $aData, $_VARS);
			} else {
				$aData = $this->prepareOpenDialog($sAction, $aSelectedIds, 0, $sAdditional, false);
				$aTransfer['data'] = $aData;
			}

			return $aTransfer;
			
		} elseif($sAction == 'payment') {

			if($_VARS['payment_type'] == 'payment') {

				$mErrors = Ext_Thebing_Inquiry_Payment::saveDialogPaymentTab($this->_oGui, $aSelectedIds, $_VARS['save'], $sAdditional);

				$sDialogId	= $_VARS['dialog_id'];
				$sId		= str_replace(Ext_Thebing_Inquiry_Payment::$_sIdTag, '', $sDialogId);
				
				// Request Daten definieren
				$aTransfer						= array();
				$aTransfer['action']			= 'saveDialogCallback';
				$aTransfer['dialog_id_tag']		= Ext_Thebing_Inquiry_Payment::$_sIdTag;
				$aTransfer['success_message']	= L10N::t('Das Payment wurde gespeichert.', $this->_oGui->gui_description);

				// Immer auf den letzten Tab springen
				// Das funktioniert auch, wenn die anderen Tabs aufgrund fehlender Rechte nicht angezeigt werden
				$aTransfer['tab'] = -1;

				$aTransferData					= $this->_oGui->getDataObject()->prepareOpenDialog('payment', $aSelectedIds, false, $sAdditional);
				$aTransfer['data']				= $aTransferData;
				$aTransfer['data']['id']		= $sDialogId;
				$aTransfer['data']['save_id']	= $sId;

				$aTransfer['error'] = array();

				if($mErrors !== true) {
					$aTransfer['error'] = $mErrors;
				}

			} else if($_VARS['payment_type'] == 'overpay') {
				$aTransfer = Ext_Thebing_Inquiry_Payment::saveDialogOverpaymentTab($aSelectedIds, $_VARS['save'], $this->_oGui, $sAdditional);
			}
			
			// Aufgelaufene Fehler ergänzen
			Ext_TC_Error_Handler::mergeErrors($aTransfer['error'], $this->_oGui->gui_description);

			return $aTransfer;

		} else {
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		}

		return $aTransfer;
	}

	public static function convertErrorKeyToMessage($sKey) {
		$sMessage = parent::convertErrorKeyToMessage($sKey);
		if($sMessage === $sKey){
			switch ($sKey) {
				case 'placeholder_document_needed':
					$sMessage = 'Für einen oder mehrere Platzhalter wird mindestens eine Rechnung benötigt';
					break;
			}
		}
		return $sMessage;
	}

	/**
	 * umschreiben der IDS in bestimmten Listen z.b Transferliste damit die Felder der Inquiry korrekt ausgelesen werden
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param array $aData
	 * @param array $aSelectedIds
	 * @return array
	 */
	public function setFlexData(\Gui2\Dialog\DialogInterface $oDialog, $aData, $aSelectedIds) {

		if($this->_oGui->query_id_alias == 'kit') {
			// Inquiry ID holen vorallem damit Flex Felder angezeigt werden
			$oInquiryTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance((int)reset($aSelectedIds));
			$aSelectedIds = array($oInquiryTransfer->inquiry_id);
		}elseif($this->_oGui->query_id_alias == 'kia') {
			$iInquiryID = $this->_oGui->decodeId((int) reset($aSelectedIds), 'accommodation_inquiry_id');
			$aSelectedIds = array($iInquiryID);
		}

		if($this->bWriteFlexFields){
			$aData = parent::setFlexData($oDialog, $aData, $aSelectedIds);
		}
		
		return $aData;
	}

	/**
	 * WRAPPER Ajax Request verarbeiten
	 * @param $_VARS
	 */
	public function switchAjaxRequest($_VARS) {

		$aTransfer = array();

		switch($_VARS['task']) {

			case 'deleteInquiryPayment':

				$oPayment = Ext_Thebing_Inquiry_Payment::getInstance($_VARS['iPaymentId']);

				// Alle Inquries holen
				$aInquiries = $oPayment->getAllInquiries();

				if(!empty($aInquiries)) {

					// Erste Inquiry
					$oInquiry = reset($aInquiries);

					$oPayment->delete();

					// Dialog neu holen mit den zurückgesetzten Einstellungen
					$aTransferData = $this->prepareOpenDialog('payment', $_VARS['id'], false, $_VARS['additional']);

					// Request Daten definieren
					$aTransfer['action'] = 'saveDialogCallback';
					$aTransfer['dialog_id_tag'] = $oPayment->getIdTag();
					$aTransfer['success_message'] = L10N::t('Das Payment wurde gelöscht.');
					$aTransfer['error'] = $oPayment->aErrors;
					$aTransfer['data'] = $aTransferData;
					$aTransfer['tab'] = count($aTransferData['tabs']) - 1;

					Ext_Gui2_Index_Registry::insertRegistryTask($oInquiry);

					echo json_encode($aTransfer);

				}
				break;

			case 'calculateSchoolAmount': // @deprecated
				// Schulwährung ausrechnen
				if($_VARS['inquiry'] > 0){
					$oInquiry = Ext_TS_Inquiry::getInstance($_VARS['inquiry']);
					$oSchool = $oInquiry->getSchool();
					$iSchoolId = $oSchool->id;
				} else {
					$iSchoolId = $_VARS['school'];
				} 

				$iSchoolCurrency = $_VARS['currency_school'];

				if($iSchoolCurrency <= 0){
					$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
					$iSchoolCurrency = $oSchool->getCurrency();
				}

				$fSchoolAmount = Ext_Thebing_Format::ConvertAmount($_VARS['amount'], $_VARS['currency'], $iSchoolCurrency, $iSchoolId);
				$fSchoolAmount = Ext_Thebing_Format::convertFloat($fSchoolAmount, $iSchoolId);
				$fSchoolAmount = round($fSchoolAmount, 2);

				$aTransfer['data']['vars']			= $_VARS;
				$aTransfer['data']['value_old']		= $_VARS['amount'];
				$aTransfer['data']['value_new']		= $fSchoolAmount;
				$aTransfer['data']['action']		= $_VARS['action'];
				$aTransfer['data']['input']			= $_VARS['input'];
				$aTransfer['action']				= 'calculateSchoolAmountCallback';
				
				echo json_encode($aTransfer);

				break;
			case 'getCurrencyConversionFactor':

				$iCurrencyFrom = (int)$_VARS['currency_from'];
				$iCurrencyTo = (int)$_VARS['currency_to'];
				$sDate = Ext_Thebing_Format::ConvertDate($_VARS['date'], null, 1);

				$oCurrencyFrom = Ext_Thebing_Currency::getInstance($iCurrencyFrom);
				$fFactor = $oCurrencyFrom->getConversionFactor($iCurrencyTo, $sDate);

				$aTransfer = [];
				$aTransfer['action'] = 'getCurrencyConversionFactorCallback';
				$aTransfer['data']['date'] = $sDate;
				$aTransfer['data']['date_input'] = $_VARS['date'];
				$aTransfer['data']['currency_from'] = $iCurrencyFrom;
				$aTransfer['data']['currency_to'] = $iCurrencyTo;
				$aTransfer['data']['factor'] = $fFactor;
				$aTransfer['data']['additional'] = $_VARS['additional']; // Ping-Pong wegen Anti-Closure-RequestCallback

				echo json_encode($aTransfer);

				break;
			case 'request':
				if($_VARS['action'] == 'transfer_provider_assign') {
					
					// Zuweisen von Transferen zu Providern
					$aTransfer = Ext_TS_Pickup_Gui2_Data::saveProviderAssign($_VARS['id'], $this->_oGui);
					echo json_encode($aTransfer);

				}elseif($_VARS['action'] == 'openDocumentPdf'){

					$sTemplateType	= null;
					if(isset($_VARS['template_type'])) {
						$sTemplateType = $_VARS['template_type'];
					}
					
					$aInquiryIds = (array)$_VARS['id'];
					$iInquiryId = (int)reset($aInquiryIds);

					$sInquiryIdField = $this->_oGui->getOption('decode_inquiry_id_additional_documents');
					if(!empty($sInquiryIdField)){
						$iInquiryId = $this->_oGui->decodeId($iInquiryId, $sInquiryIdField);
					}

//					if($sTemplateType === 'document_student_requests') {
//						$oInquiry = Ext_TS_Enquiry::getInstance($iInquiryId);
//					} else {
						$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
//					}

//					if($oInquiry instanceof Ext_TS_Enquiry) {
//						$sPdfPath = $oInquiry->getLastAdditionalDocumentPDF();
//					} else {

						$oSearch = new Ext_Thebing_Inquiry_Document_Search($oInquiry->id);

						// Rechteprüfung, ob Dokument in dieser GUI erzeugt wurde
						if(
							$this->_oGui->getOption('only_documents_from_same_gui') &&
							!Ext_Thebing_Access::hasRight('thebing_gui_document_areas')
						) {
							$sGuiName = $this->_oGui->name;
							if(!empty($sGuiName)) {
								$oSearch->setGuiLists(array(array($sGuiName, $this->_oGui->set)));
							}
						}

						// Zusätzliche Rechteprüfung mit Inboxrechten
						// Dies wäre durch »thebing_gui_document_areas« schon abgedeckt, allerdings nur für neue Dokumente
						if(System::d('ts_check_inbox_rights_for_document_templates')) {
							$oUser = System::getCurrentUser();
							$aInboxes = $oUser->getInboxes('id');
							$oSearch->setTemplateInboxes($aInboxes);
						}

						$sPdfPath = $oInquiry->getLastDocumentPdf('additional_document', [], $oSearch);
//					}

					if(!empty($sPdfPath)) {
						$aTransfer['url']			= '/storage/download' . $sPdfPath;
						$aTransfer['action']		= 'openUrl';
						echo json_encode($aTransfer);
					}
				} elseif($_VARS['action'] == 'convertProformaDocument') {

					$logger = Log::getLogger('default', 'convert_proforma');
					
					// TODO Das hier kann entweder nur eine Gruppe ODER mehrere einzelne Rechnungen #13366
					// Außerdem funktioniert das nur mit einem Nummernkreis (falls im Dialog mehrere ausgewählt werden)

					if(isset($_VARS['document_ids'])) {
						// Bezahlungsdialog
						$aSelectedIds = (array)$_VARS['document_ids'];
					} else {
						$aSelectedIds = (array)$_VARS['id'];
					}

					$logger->info('Start', $aSelectedIds);
					
					$bShowDialog = false;
					$aNumberRangedDocuments = array();
					$aErrors = array();
					$aErrorCache = array();
					
					$aDocData = array();
					$bInquiryHasGroup = false;
					$bUnlockNumberrange = false;
					$aGroupDocumentNumbers = [];
					$groupDocuments = [];

					$date = null;
					if (!empty($_VARS['save']['date'])) {
						$dateValue = (new \Ext_Thebing_Gui2_Format_Date())->convert($_VARS['save']['date']);
						$date = \Carbon\Carbon::createFromFormat('Y-m-d', $dateValue);

						if (!$date) {
							$aErrors[] = $this->t('Das Format des Rechnungsdatums ist nicht korrekt.');
						}
					}

					foreach($aSelectedIds as $iSelectedId) {

						$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iSelectedId);
						$oInquiry = $oDocument->getInquiry();
						
						$iNumberRangeId = 0;

						if($oInquiry instanceof Ext_TS_Inquiry) {
							$bInquiryHasGroup = $oInquiry->hasGroup();

							if($bInquiryHasGroup) {
								$oGroup = $oInquiry->getGroup();
								$aMembers = $oGroup->getInquiries();

								$groupDocuments[$oGroup->id] = [];
								
								// Nummernkreis-ID kann jeder ID einer Gruppen-Proforma zugewiesen sein.
								$aProformas = [];
								foreach($aMembers as $oMember) {
									
									$oProforma = $oMember->getLastDocument('invoice_proforma');

									if(
										isset($_VARS['save']) &&
										isset($_VARS['save']['numberrange_id']) &&
										isset($_VARS['save']['numberrange_id'][$oProforma->id])
									) {
										$iNumberRangeId = (int)$_VARS['save']['numberrange_id'][$oProforma->id];
									}
									
									if($oProforma) {
										$aProformas[] = $oProforma;
									}
									
								}

								$isMainInquiry = true;
								$groupDocumentCount = 0;
								foreach($aProformas as $oProforma) {

									$groupDocumentCount++;
									
									/*
									 * Wichtig: Diese Reihenfolge muss wg. main_inquiry und create_pdf immer erhalten bleiben.
									 * PDF darf erst erstellt werden bei Gruppe, nach dem das letzte Gruppendokument erstellt wurde
									 */
									$aDocData[$oProforma->id] = array(
										'doc_id' => $oProforma->id,
										'numberrange_id' => $iNumberRangeId,
										'inquiry' => $oInquiry,
										'main_inquiry' => $isMainInquiry,
										'group_id' => $oGroup->id,
										'create_pdf' => ($groupDocumentCount == count($aProformas))?true:false // Nur beim letzten Gruppenmitglied PDF generieren
									);
									
									$isMainInquiry = false;
									
								}

							} else {

								if(
									isset($_VARS['save']) &&
									isset($_VARS['save']['numberrange_id']) &&
									isset($_VARS['save']['numberrange_id'][$iSelectedId])
								) {
									$iNumberRangeId = (int)$_VARS['save']['numberrange_id'][$iSelectedId];
								}
								
								$aDocData[$iSelectedId] = array(
									'doc_id' => $iSelectedId,
									'numberrange_id' => $iNumberRangeId,
									'inquiry' => $oInquiry,
									'main_inquiry' => true,
									'group_id' => 0,
									'create_pdf' => true
								);
							}
						} else {
							throw new Exception('you can only convert inquiry documents!');
						}
					}

					$logger->info('Prepared', $aDocData);

					// Alle ausgewählten Nummernkreise sperren
					$aLockedNumberranges = [];
					foreach($aDocData as $aDoc) {
						if(
							empty($aDoc['numberrange_id']) ||
							isset($aLockedNumberranges[$aDoc['numberrange_id']])
						) {
							continue;
						}

						$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($aDoc['numberrange_id']);
						if($oNumberrange->acquireLock()) {
							$bUnlockNumberrange = true;
							$aLockedNumberranges[$oNumberrange->id] = $oNumberrange;
						} else {
							$bUnlockNumberrange = false;
							$aErrors[] = Ext_Thebing_Document::getNumberLockedError();
							foreach($aLockedNumberranges as $oNumberrange2) {
								// Wenn irgendein Nummernkreis nicht gesperrt werden konnte, alle gesperrten wieder entsperren
								$oNumberrange2->removeLock();
							}
							break;
						}
					}

					$logger->info('Begin');
					
					DB::begin('convert_proforma_to_inquiry');

					foreach($aDocData as $aDoc) {

						if(!empty($aErrors)) {
							continue;
						}
						
						$logger->info('Document', $aDoc);

						$iNumberRangeId = $aDoc['numberrange_id'];
						$bMainInquiry = $aDoc['main_inquiry'];
						$iGroupId = $aDoc['group_id'];
						$oInquiry = $aDoc['inquiry'];
						$createPdf = $aDoc['create_pdf'];
						$mConverted	= false;
						$sDocumentNumber = '';

						$oDocument = Ext_Thebing_Inquiry_Document::getInstance($aDoc['doc_id']);

						if($bUnlockNumberrange) {
							// Nummernkreis wurde oben bereits gesperrt, also soll dieser nicht noch eimmal gesperrt werden
							$oDocument->bLockNumberrange = false;
						}

						if(!empty($iGroupId)) {
							// Alle umgewandelten Proformas der Gruppe bekommen dieselbe Rechnungsnummer
							if(!empty($aGroupDocumentNumbers[$iGroupId])) {
								$sDocumentNumber = $aGroupDocumentNumbers[$iGroupId];
							}
						}

						if(
							empty($aErrors) &&
							!isset($aErrorCache[$oInquiry->id])
						) {

							// Prüfen, ob Kundennummer generiert werden muss
							// Nötig bei Buchung über Anmeldungsformular #5045
							$oCustomerNumber = new Ext_Thebing_Customer_CustomerNumber($oInquiry);
							$oCustomerNumber->saveCustomerNumber();

							if (
								// Die Umwandlung wurde über den Bezahlen-Button gestartet und die Option deaktiviert,
								// dann wird direkt zu normalen Rechnungen konvertiert
								isset($_VARS['initiated_by']) &&
								$_VARS['initiated_by'] === 'payment_dialog' &&
								!\System::d('ts_payments_without_invoice')
							) {
								$oDocument->overrideCreationAsDraft = true;
							}
							$mConverted = $oDocument->convertProformat2InquiryDocument(L10N::t('Proforma-Rechnung in Rechnung umwandeln', $this->_oGui->gui_description), $iNumberRangeId, $sDocumentNumber, true, $createPdf, $date);
						}

						if(is_array($mConverted)) {
							
							$aErrors = array_merge($aErrors, $mConverted);
							
							$logger->error('Conversion failed', $mConverted);
							
						} elseif($mConverted instanceof Ext_Thebing_Inquiry_Document_Numberrange_List) {

							$bShowDialog = true;
							$aNumberRangedDocuments = array_merge($aNumberRangedDocuments, $mConverted->getSelectedIds());
							$aErrorCache[$oInquiry->id] = 1;

							$logger->info('Conversion: Numberrange List', $aDoc);

						} elseif(
//							$bMainInquiry &&
							$mConverted instanceof Ext_Thebing_Inquiry_Document
						) {
							
							if($iGroupId > 0) {
								
								// Beim letzten Gruppenmitglied, PDF-Pfad auch in andere Rechnungen schreiben
								if($createPdf) {
									
									$thisDocumentVersion = $mConverted->getLastVersion();
									
									foreach($groupDocuments[$iGroupId] as $groupDocumentId) {
										$groupDocument = Ext_Thebing_Inquiry_Document::getInstance($groupDocumentId);
										$groupDocumentVersion = $groupDocument->getLastVersion();
										$groupDocumentVersion->path = $thisDocumentVersion->path;
										$groupDocumentVersion->updateField('path');
									}

								} else {
									$groupDocuments[$iGroupId][] = $mConverted->id;
								}
								
							}							
							
							// Gleiche Dokumentennummer für alle Gruppendokumente
							// Durch usort() ist die erste Buchung auch die Main-Inquiry, daher setzt diese dann $sGroupDocumentNumber
							if ($bMainInquiry) {
								$aGroupDocumentNumbers[$iGroupId] = $mConverted->document_number;
							}

							$logger->info('Conversion succeeded', $aDoc);

						}

						// Nummerkreis-Sperre für langwierige Gruppen-Dokument-Generierungen erneuern
						if(
							$bInquiryHasGroup &&
							isset($oNumberrange) &&
							$bUnlockNumberrange
						) {
							$oNumberrange->renewLock();
						}

					}

					$logger->info('End', ['errors'=>$aErrors, 'show_dialog'=>$bShowDialog]);

					// Überspringen wenn keine Proforma => wenn mehrfach auf das icon geklickt wird
					
					if(
						$bShowDialog
					) {

						$oNumberRangeList = new Ext_Thebing_Inquiry_Document_Numberrange_List($aNumberRangedDocuments);

						$sIconKey = self::getIconKey('numberrange', null);
						
						$oNumberRangeList->setL10NPart($this->_oGui->gui_description);
						$oDialog					= $oNumberRangeList->getDialog($this->_oGui);
						
						$this->aIconData[$sIconKey]['dialog_data'] = $oDialog;

						$sDialogId					= $this->_getDialogId($oDialog, $aSelectedIds);
						$_VARS['dialog_id']			= $sDialogId;

						$aTransfer					= parent::_switchAjaxRequest($_VARS);

						$aTransfer['action']		= 'openDialog';

						// Leider wird das hier in Buchungsliste (Zahlung auf Proforma) und Proformaliste (Dokumente) verwendet
						// prepareOpenDialog() ruft immer _getWDBasicObject auf
						if($this->_oGui->class_wdbasic === 'Ext_TS_Inquiry') {
							/* @var $oNumberrangeDialog \Ext_Gui2_Dialog */
							$oNumberrangeDialog = $this->aIconData['numberrange']['dialog_data'];
							$oNumberrangeDialog->getDataObject()->setWDBasic('Ext_Thebing_Inquiry_Document');
							$aData = $this->prepareOpenDialog('numberrange', $aSelectedIds);
						} else {
							$aData = $this->prepareOpenDialog('numberrange', $aSelectedIds);
						}

						$aTransfer['data'] = $aData;

						echo json_encode($aTransfer);
						
					} elseif(empty($aErrors)) {

						DB::commit('convert_proforma_to_inquiry');

						// Nummernkreis freigeben (nach BEENDIGUNG der Transaktion)
						if(
							isset($oNumberrange) &&
							$bUnlockNumberrange
						) {
							$oNumberrange->removeLock();
						}

						$aParentIds	= (array)$_VARS['parent_gui_id'];
						$iParentId	= (int)reset($aParentIds);
						
						$oParent	= $this->_getParentGui();

						$_VARS['id'] = (array)$_VARS['id'];
						
						$iSelectedId = reset($_VARS['id']);

						//der Parent task, damit die Parent Gui's neu geladen werden
						$aTransfer['action']				= 'saveDialogCallback';
						//der Studentlist task, damit die History aktualisiert wird
						$aTransfer['task']					= 'deleteCallback';
						$aTransfer['data']['id']			= 0;
						//damit das neu konvertierte Dokument selektiert wird
						$aTransfer['data']['selectedRows']	= array($mConverted);
						$aTransfer['error']					= array();
						$aTransfer['success_message']		= L10N::t('Proforma wurde erfolgeich umgewandelt.', $this->_oGui->gui_description);
						
						if($oParent) {
							//wird benötigt um die Parent Gui's neu zu laden
							$aTransfer['parent_gui']			= $this->_oGui->getParentGuiData();
							//Wird benögigt um die History zu laden
							$aTransfer['parent_id']	= $iParentId;
							$aTransfer['parent_hash']	= $oParent->hash;
							//History Html
							$sHistoryHtml = Ext_Thebing_Document::getHistoryHtml($oParent, $oDocument->getInquiry(), 'invoice', $this->_oGui);
							$aTransfer['history_html']			= $sHistoryHtml;	
						}

						echo json_encode($aTransfer);
						
					} else {

						DB::rollback('convert_proforma_to_inquiry');
						
						// Da nix in der DB gespeichert wurde (Rollback), braucht auch nix in den Index geschrieben werden
						Ext_Gui2_Index_Stack::clearStack();

						// Nummernkreis freigeben (nach BEENDIGUNG der Transaktion)
						if(
							isset($oNumberrange) &&
							$bUnlockNumberrange
						) {
							$oNumberrange->removeLock();
						}

						$aTransfer['action']				= 'saveDialogCallback';

						$aTransfer['data']['id']			= 0;

						array_unshift($aErrors, L10N::t('Fehler beim Umwandeln', 'Thebing » Errors'));

						$aTransfer['error']					= $aErrors;

						echo json_encode($aTransfer);
					}
				} else {
					parent::switchAjaxRequest($_VARS);
				}
				
				break;

			case 'openDialog':

				$aSelectedIds = $_VARS['id'];

				if(
					$_VARS['action'] == 'additional_document' &&
					count($aSelectedIds) > 1
				) {
					$mOption = $this->_oGui->getOption('decode_inquiry_id_additional_documents');
					if(!empty($mOption)){
						$aSelectedIds = $this->_oGui->decodeId($aSelectedIds, $mOption);
					}
					
					$aTransfer = $this->_switchAjaxRequest($_VARS);
					
//					if($_VARS['template_type'] == 'document_student_requests')
//					{
//						$sType = 'enquiry';
//					}
//					else
//					{
						$sType = 'inquiry';
//					}
					
					$mUnityLanguage = Ext_TS_Contact::getUnityLanguagesForContacts($sType, $aSelectedIds);


					if(!$mUnityLanguage){
						$aTransfer['alert_messages']	= array(
								$this->t('Die ausgewählten Schüler haben nicht die gleiche Korrespondenzsprache, die Standard Schulsprache wird gewählt.')
						);
					}
					$aTransfer['data']['action'] = 'document_edit';

					echo json_encode($aTransfer);
					
				} elseif(
					$_VARS['action'] == 'payment'
				){
					$aSelectedIds = (array)$_VARS['id'];

					// Bezahlungsdialog und Mehrfachauswahl: Mehr als eine Währungskombination darf es nicht geben
					if(count($aSelectedIds) > 1) {

						$aSchools = [];
						$aInquiryCurrencies = [];
						$aSchoolCurrencies = [];
						foreach($aSelectedIds as $iSelectedId) {
							$oInquiry = Ext_TS_Inquiry::getInstance($iSelectedId);
							$aInquiryCurrencies[$oInquiry->getCurrency()] = true;
							$aSchools[$oInquiry->getSchool()->id] = true;
							$aSchoolCurrencies[$oInquiry->getSchool()->getCurrency()] = true;
						}

						if(
							count($aSchools) > 1 ||
							count($aInquiryCurrencies) > 1 ||
							count($aSchoolCurrencies) > 1
						) {
							$aErrors = [$this->t('Fehler beim Bezahlen')];

							if(count($aSchools) > 1) {
								$aErrors[] = $this->t('Schüler unterschiedlicher Schulen können nicht bezahlt werden.');
							}

							if(
								count($aInquiryCurrencies) > 1 ||
								count($aSchoolCurrencies) > 1
							) {
								$aErrors[] = $this->t('Die Währungen stimmen nicht überein.');
							}

							$aTransfer['action'] 	= 'showError';
							$aTransfer['error'] 	= $aErrors;
							echo json_encode($aTransfer);
							break;
						}
						else
						{
							parent::switchAjaxRequest($_VARS);
						}
					} else {
						parent::switchAjaxRequest($_VARS);
					}
				}
				else {
					parent::switchAjaxRequest($_VARS);
				}

				break;
//			case 'convertDate':
//				// convertiert datumsangaben um
//				$oFormat = new Ext_Thebing_Gui2_Format_Date();
//				$oDate = new WDDate();
//
//				$aFormated = array();
//				foreach((array)$_VARS['date'] as $iKey => $sValue){
//					$sFormat = $oFormat->convert($sValue);
//					$iFormat = 0;
//					if(WDDate::isDate($sFormat, WDDate::DB_DATE)){
//						$oDate->set($sFormat, WDDate::DB_DATE);
//						$iFormat = $oDate->get(WDDate::TIMESTAMP);
//					}
//
//
//					$aFormated[$iKey]['db_date'] = $sFormat;
//					$aFormated[$iKey]['timestamp'] = $iFormat;
//					$aFormated[$iKey]['formated'] = $sValue;
//				}
//				$aTransfer['action']			= 'convertDateCallback';
//				$aTransfer['data']['date']		= $aFormated;
//				$aTransfer['data']['action']	= $_VARS['action'];
//				echo json_encode($aTransfer);
//				break;
			case 'checkPaymentCurrency':
				// Bezahlmethode überprüfen ob Währung passt -> Buchhaltung Bezahllisten
				$iMethodId = (int)$_VARS['method'];			
				
				$iCurrencyId = Ext_Thebing_School::getCurrencyOfPaymentMethod($iMethodId);

				$aTransfer['action']				= 'checkPaymentCurrencyCallback';
				$aTransfer['data']['currency_id']	= $iCurrencyId;
				echo json_encode($aTransfer);
				break;
			case 'updateIdentity':

				$aTransfer = $this->_switchAjaxRequest($_VARS);

				$aTransfer = Ext_Thebing_Communication::getNewIdentitiesData($this->_oGui, $_VARS, $aTransfer);

				echo json_encode($aTransfer);
				break;
			case 'saveSort':
				parent::switchAjaxRequest($_VARS);  
				break;
			case 'saveDialog':
				if(
					$_VARS['action'] == 'numberrange'
				){
					$_VARS['old_task']	= $_VARS['task'];
					$_VARS['task']		= 'request';
					$_VARS['action']	= 'convertProformaDocument';
					$this->switchAjaxRequest($_VARS);
				}else{
					parent::switchAjaxRequest($_VARS); 
				}
				break;
			default:

				// Währungsformatierung
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
			
				$aTemp = $oSchool->getNumberFormatData();
				$aTransfer = $this->_switchAjaxRequest($_VARS);
				$aTransfer['number_format']['t'] = $aTemp['t'];
				$aTransfer['number_format']['e'] = $aTemp['e'];
				$aTransfer['number_format']['dec'] = 2;

				$sJson = json_encode($aTransfer);
				echo $sJson;

				if($sJson === false) {
					throw new RuntimeException('switchAjaxRequest: json_encode() returned false! Error: '.json_last_error());
				}

				break;

		}

	}

	public function getSimplePdfIcon($oBar) {

		$oDialogPdf	= $this->createDialog($this->t('PDF'),$this->t('PDF'));
		$oDialogPdf->width			= 900;
		$oDialogPdf->height			= 650;
		$oDialogPdf->sDialogIDTag = 'SIMPLEPDF_';
		$oDialogPdf->setElement(
								$oDialogPdf->createRow(
															L10N::t('PDF-Vorlage',
															$this->gui_description),
															'select',
															array(
																'db_alias'			=>'',
																'db_column'			=> 'pdf_template_id',
																'select_options'	=> $aPdfTemplates,
																'required'			=> 1
															)
														)
								);

		$oIcon = $oBar->createIcon(Ext_Thebing_Util::getIcon('pdf'), 'openDialog', $this->t('PDF erstellen'));
		$oIcon->action = 'simple_pdf';
		$oIcon->active = 0;
		$oIcon->dialog_data = $oDialogPdf;
		$oIcon->label = $this->t('PDF erstellen');
		$oIcon->multipleId 	= 1;

		return $oIcon;

	}

	/**
	 *
	 * @todo: Diese Methode gibt es auch nochmal in der Ext_Thebing_Inquiry_Gui2
	 * @param <type> $aIds
	 * @param <type> $aRowData
	 * @param <type> $oIcon
	 * @return <type>
	 */
	public function getRowIconInfoText(&$aIds, &$aRowData, &$oIcon) {

		$sHtml = '';
		$aDocuments = array();
		$aIds = (array)$aIds;
		$iInquiryId = reset($aIds);
		$sInquiryIdField = $this->_oGui->getOption('decode_inquiry_id_additional_documents');
		if(!empty($sInquiryIdField)){
			$iInquiryId = $this->_oGui->decodeId($iInquiryId, $sInquiryIdField);
		}

		if(!$this->oWDBasic){
			try {
				$this->_getWDBasicObject($aIds);
			} catch(InvalidArgumentException $e) {
				// Keine Ahnung was hier in der Transferliste los ist
				$this->_getWDBasicObject([0]);
			}
		}

		switch($oIcon->task){
			case 'openInvoice':
				break;
			case 'openDocument':
				// Sonstige PDFs
				$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($iInquiryId, 'additional_document', true);
				break;
			default:
				// ACHTUNG: KOMPLETT REDUNDANT in Ext_Thebing_Inquiry_Gui2::getRowIconInfoText()
				if(strpos($oIcon->request_data, 'openInvoicePdf') !== false) {
					// RechnungsPDFs
					$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($iInquiryId, 'invoice', true);
					// Creditnote muss extra hinzugefügt werdenda nicht in 'invoice' enthalten sein darf
					$aDocumentsCredit = Ext_Thebing_Inquiry_Document_Search::search($iInquiryId, 'creditnote', true);
					$aDocuments = array_merge($aDocuments, $aDocumentsCredit);
				} elseif($oIcon->action == 'openDocumentPdf') {

					$oSearch = new Ext_Thebing_Inquiry_Document_Search($iInquiryId);
					$oSearch->setType('additional_document');
					$oSearch->setObjectType($this->oWDBasic->getClassName());

					// Rechteprüfung, ob Dokument in dieser GUI erzeugt wurde
					if(
						$this->_oGui->getOption('only_documents_from_same_gui') &&
						!Ext_Thebing_Access::hasRight('thebing_gui_document_areas')
					) {
						$sGuiName = $this->_oGui->name;
						if(!empty($sGuiName)) {
							$oSearch->setGuiLists(array(array($sGuiName, $this->_oGui->set)));
						}
					}

					// Zusätzliche Rechteprüfung mit Inboxrechten
					// Dies wäre durch »thebing_gui_document_areas« schon abgedeckt, allerdings nur für neue Dokumente
					// Bei Anfragen darf das nicht passieren, da diese Dokumente keine Inbox haben
					if(
						$this->oWDBasic->getClassName() !== 'Ext_TS_Enquiry' &&
						System::d('ts_check_inbox_rights_for_document_templates')
					) {
						$oUser = System::getCurrentUser();
						$aInboxes = $oUser->getInboxes('id');
						$oSearch->setTemplateInboxes($aInboxes);
					}

					$aDocuments = $oSearch->searchDocument(false);
				}
				break;
		}

		foreach($aDocuments as $aDocument) {
			if(is_array($aDocument)){
				$oDocument = new Ext_Thebing_Inquiry_Document($aDocument['id']);
			} else {
				$oDocument = $aDocument;
			}
			$oLastVersion = $oDocument->getLastVersion();
			if($oLastVersion->path != "") {
				$sHtml .= sprintf(
					'<a href="%s" class="block w-full hover:bg-gray-50 rounded-md p-2" target="_blank">%s</a>',
					'/storage/download/'.$oLastVersion->path,
					$oLastVersion->getLabel()

				);
			}
		}

		return $sHtml;

	}

	/**
	 * Liefert den default Zeichensatz für den CSV Export
	 * @return string 
	 */
	protected function _getCharsetForExport() {

		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$sCharset = $oSchool->getCharsetForExport();

		return $sCharset;
	}
	
	/**
	 * Liefert das Trennzeichen für den CSV Export
	 * @return string
	 */
	protected function _getSeparatorForExport() {

		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$sSeperator	= $oSchool->export_delimiter;
		
		if(empty($sSeperator)) {
			$sSeperator = ';';
		}

		return $sSeperator;
	}

	public function sortPositions($aPosition1,$aPosition2){

		$aSub1	= reset($aPosition1);
		$aSub2	= reset($aPosition2);

		if($aSub1['sort'] < $aSub2['sort']) {
			return 1;
		} else {
			return 0;
		}
	}

	public static function getGroupColumnTitle(){
		$sTitle = '<div style="text-align:center"><i class="fa '.Ext_TC_Util::getIcon('group').'" alt="'.L10N::t('Gruppe').'" title="'.L10N::t('Gruppe').'"></i></div>';

		return $sTitle;
	}
	
}
