<?php

use Core\Entity\ParallelProcessing\Stack;

class Ext_Thebing_Document {

	/**
	 * Translated and replaced template data
	 */
	public $aTemplateData = array();

	public $bGroup = false;
	
	public $bNegate = false;

	/**
	 * Letzte Document Version
	 */
	public $oLastVersion = NULL;

	/**
	 * Variable dient zur Sicherung, dass nicht 2 mal gespeichert wird
	 */
	public static $bDocumentSaveCheck = true;

	public static $sL10NDescription = 'Thebing » Documents';

	const MAX_ATTACHED_ADDITIONAL_DOCUMENTS = 5;

	public $oSchool = null;

	/**
	 * Gesamtbetrag Spalte
	 */
	public $sTotalAmountColumn;
	
	/**
	 * Zwischenspeicher für die Tooltips
	 */
	public $aPositionsTooltips;

	/**
	 *
	 * @var \Ts\Interfaces\Entity\DocumentRelation
	 */
	protected $_oEntity = null;

	/**
	 *
	 * @var Ext_TS_Inquiry_Abstract 
	 */
	protected $_oInquiry = null;

	/**
	 * Wird _bedarfsweise_ gesetzt
	 * @var Ext_Thebing_Gui2
	 */
	public $oGui;

	public $documentId = null;
	public $sourceDocumentId = null;
	
	/**
	 * Erzeugt die Discountbeschreibung für ein Item oder gibt ein Template zurück
	 *
	 * @param null $aItem
	 * @param object $oLanguage
	 * @return mixed
	 */
	public static function getDiscountDescription($aItem, $oLanguage) {
		
		$sDiscountDescription = $oLanguage->translate('Rabatt ({percent} %) {description}');

		if($aItem) {
			$sDiscountDescription = str_replace('{percent}', Ext_Thebing_Format::Number($aItem['amount_discount']), $sDiscountDescription);
			$sDiscountDescription = str_replace('{description}', $aItem['description'], $sDiscountDescription);
		}

		return $sDiscountDescription;
	}

	/**
	 * Get documents list and history
	 *
	 * @param Ext_Thebing_Gui2 $oGui
	 * @param array $aSelectedIDs
	 * @param string $sType
	 * @param bool $bOnlyCreditNotes
	 * @param bool $mTemplateType
	 * @param int $iSchoolForFormat
	 * @return mixed
	 */
	public static function getDialog(&$oGui, $aSelectedIDs, $sType, $bOnlyCreditNotes, $mTemplateType, $iSchoolForFormat, $sDataClass=null) {

		$aSelectedIDs	= (array)$aSelectedIDs;
		$iSelectedID	= (int)reset($aSelectedIDs);
		//nur relevant für parent primary key, encodiert wird schon vorher
		$mOptionInquiryIdForDocuments = $oGui->getOption('decode_inquiry_id_additional_documents');

		$sDocumentType = $sType;
        $sIndexSet = 'inquiry_invoice';
		$sTitle = 'Dokumente';

		switch($sDocumentType) {

			case 'additional_document':
			{
				//$sTitle = 'Dokumente "{customer_name}"'; // <== das klappt wegen den IDs nicht!
				$sTitle = 'Dokumente'; // <== das klappt wegen den IDs nicht!
				$sTab = 'Dokumente';
                $sIndexSet = 'additional_document';
				break;
			}
			case 'invoice':
			case 'proforma':
			{
				//$sTitle = 'Rechnungen "{customer_name}"';
				$sTitle = 'Rechnungen';
				$sTab = 'Rechnungen';

				break;
			}

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDocumentDialog = $oGui->createDialog($oGui->t($sTitle));

		// Set ID
		$oDocumentDialog->id = 'DOCUMENTS_LIST_' . $iSelectedID;
		$oDocumentDialog->sDialogIDTag = 'DOCUMENTS_LIST_';

		$oDocumentTab = $oDocumentDialog->createTab(L10N::t($sTab, $oGui->gui_description));
		$oHistoryTab = $oDocumentDialog->createTab(L10N::t('Historie', $oGui->gui_description));
		$oHistoryTab->no_scrolling = true;

		if ($mTemplateType === 'document_course') {
			$oEntity = Ext_Thebing_Tuition_Course::getInstance($iSelectedID);
		} else if ($mTemplateType === 'document_job_opportunity') {
			$oEntity = \TsCompany\Entity\JobOpportunity\StudentAllocation::getInstance($iSelectedID);
		} else if ($mTemplateType === 'document_teacher') {
			$oEntity = \Ext_Thebing_Teacher::getInstance($iSelectedID);
		//} else if ($mTemplateType == 'document_student_requests') {
		//	$oInquiry = Ext_TS_Enquiry::getInstance($iSelectedID);
		} else {
			$oEntity = Ext_TS_Inquiry::getInstance($iSelectedID);
		}

		// Wenn es ein gruppenmitglied ist holde das Inquiry Object des ersten Mitgliedes
		// damit alles Rechnungsdaten zur Verfügung stehen
		// Edit: Nicht das erste sondern das mit den meisten Rechnungen
		// Darf außerdem nicht bei Zusatzdokumenten passieren, da diese nur einzeln generiert werden
		/**
		 * @todo Bescheuerte Idee, da das je nach dem echt lange dauert!
		 */
		if(
			$sDocumentType !== 'additional_document' &&
			$oEntity instanceof Ext_TS_Inquiry &&
			$oEntity->hasGroup()
		) {
			$oGroup = $oEntity->getGroup();
			$oEntity = $oGroup->getMainDocumentInquiry();
		}

		// Wenn das Recht nicht vorhanden ist, dürfen nur diese drei Dokumenttypen angezeigt werden
		// Im Gegensatz zu anderen Listen wird das in der Inquiry nicht gesetzt, also hier setzen
		// Rechteprüfung, da ungewiss, was passiert, wenn man das generell machen würde
		if(
			$sDocumentType === 'additional_document' &&
			empty($mTemplateType) &&
			!Ext_Thebing_Access::hasRight('thebing_gui_document_areas')
		) {
			$mTemplateType = array(
				'document_loa',
				'document_studentrecord_additional_pdf',
				'document_studentrecord_visum_pdf',
				'document_student_cards'
			);
		}

        if(!empty($mTemplateType) && is_string($mTemplateType)){
            $sTemplateType = '&template_type='.$mTemplateType;
        }elseif(!empty($mTemplateType) && is_array($mTemplateType)){
            $sTemplateType	= '';
            foreach($mTemplateType as $sType){
                $sTemplateType .= '&template_type[]='.$sType;
            }
        }else{
            $sTemplateType = false;
        }

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oFactory	= new Ext_Gui2_Factory('ts_inquiry_document');

		if($sDataClass !== null) {
			$oFactoryConfig = $oFactory->getConfig();
			$oFactoryConfig->set(array('class', 'data'), $sDataClass);
		}

		$oInnerGui	= $oFactory->createGui($sIndexSet, $oGui, array(
			'document_type' => $sDocumentType,
			'template_type' => $mTemplateType,
		));

		$oInnerGui->load_admin_header			= 0;

		if ($sType === 'additional_document') {
			$oInnerGui->multiple_selection = true;
			$oInnerGui->multiple_pdf_class = Ext_TS_Document_Gui2_PdfMerge::class; // Zauber: openMultiplePdf
		}

		if($oEntity instanceof Ext_TS_Inquiry) {

//        	if($oInquiry instanceof Ext_TS_Inquiry)
//        	{
					$oInnerGui->foreign_key					= 'inquiry_id';
//        	} else {
//            	$oInnerGui->foreign_key					= 'enquiry_id';
//        	}

			if(empty($mOptionInquiryIdForDocuments)) {
				$oInnerGui->parent_primary_key			= 'id';
			} else {
				$oInnerGui->decode_parent_primary_key	= true;
				$oInnerGui->parent_primary_key			= $mOptionInquiryIdForDocuments;
			}
		} else {

			$oInnerGui->foreign_key = 'entity_id';
			$oInnerGui->parent_primary_key = 'id';
			$oInnerGui->setTableData('where', ['entity' => \ElasticaAdapter\Facade\Elastica::escapeTerm($oEntity::class)]);

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDocumentTab->setElement($oInnerGui);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sHistoryHtml = self::getHistoryHtml($oGui, $oEntity, $sDocumentType, $oInnerGui);
		$oHistoryTab->setElement($sHistoryHtml);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDocumentDialog->setElement($oDocumentTab);
		$oDocumentDialog->setElement($oHistoryTab);

		$oDocumentDialog->save_button	= false;
		#$oDocumentDialog->width			= 980;

		return $oDocumentDialog;
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @param \Ts\Interfaces\Entity\DocumentRelation $oEntity
	 * @param $sDocumentType
	 * @param $oDocumentGui
	 * @return array|string|string[]
	 * @throws Exception
	 */
	public static function getHistoryHtml(Ext_Gui2 $oGui, \Ts\Interfaces\Entity\DocumentRelation $oEntity, $sDocumentType, $oDocumentGui=null) {

		/**
		 * @todo: Bitte ändern, da nicht optimal umgesetzt, erzeugt fehler wenn verschiedene document dialoge gleichzeitig offen sind
		 */
		$_SESSION['thebing']['document_type'] = $sDocumentType;

		$mDocumentType = $sDocumentType;

		$oSchool = $oEntity->getSchool();
		
		// Bei Rechnungen auch Credit notes auflisten
		if($sDocumentType == 'invoice') {
			$mDocumentType = [$sDocumentType, 'creditnote', 'creditnote_subagency', 'proforma_creditnote'];
		}

		if(!$oEntity->exist()) {
			// Mit ID 0 werden alle Agenbote der Software geladen
			throw new InvalidArgumentException('Non existing entity given for getHistoryHtml()!');
		}

		$oSearch = new Ext_Thebing_Inquiry_Document_Search($oEntity->id);
		$oSearch->setType($mDocumentType);
		$oSearch->searchAlsoInactive();
		$oSearch->setObjectType($oEntity::class);

		if(
			$sDocumentType === 'additional_document' &&
			!Ext_Thebing_Access::hasRight('thebing_gui_document_areas')
		) {

			// Nur Dokumente anzeigen, die auch in dieser Liste generiert wurden
			if($oGui->getOption('only_documents_from_same_gui')) {

				// Alte Dokumente haben keine Zuweisung, also auch Leersuche erlauben
				$oSearch->bEmptyGuiListSearch = true;

				$sGuiName = $oGui->name;
				if(!empty($sGuiName)) {
					$oSearch->setGuiLists(array(array($oGui->name, $oGui->set)));
				}

			} elseif($oDocumentGui instanceof Ext_Gui2) {
				// Wenn Recht nicht da, dürfen nur die Dokumente der übergebenen Template-Typen angezeigt werden
				// Die Typen stehen in der Document-GUI, also daraus holen
				$aTypes = (array)$oDocumentGui->getOption('template_type');
				if(!empty($aTypes)) {
					$oSearch->setTemplateTypes($aTypes);
				}
			}
		}

		// Zusätzliche Rechteprüfung mit Inboxrechten
		// Dies wäre durch »thebing_gui_document_areas« schon abgedeckt, allerdings nur für neue Dokumente
		// Bei Anfragen gibt es keine Inboxen, also dort nicht prüfen!
		if(
			System::d('ts_check_inbox_rights_for_document_templates') &&
			$oDocumentGui instanceof Ext_Gui2 &&
			$oDocumentGui->getOption('template_type') !== 'document_student_requests'
		) {
			$oUser = System::getCurrentUser();
			$aInboxes = $oUser->getInboxes('id');
			$oSearch->setTemplateInboxes($aInboxes);
		}

		$aDocumentsHistory = $oSearch->searchDocument();

		$sHistoryHtml = Ext_Thebing_Inquiry_Gui2_Html::getHistoryHTML($aDocumentsHistory, $oGui->gui_description, $oSchool->id, $oEntity->getCurrency(), $sDocumentType);
		$sHistoryHtml = Ext_Thebing_Util::compressForJson($sHistoryHtml);

		return $sHistoryHtml;

	}

	/**
	 *  Get edit dialog data and placeholders overview
	 *
	 * @param Ext_Thebing_Gui2 $oGui
	 * @param int $iDocumentID
	 * @param string $sType
	 * @param array $aSelectedIds
	 * @return array
	 */
	public function getEditDialog(&$oGui, $iDocumentID, $sType, $aSelectedIds) {
		global $user_data, $_VARS;

		if($this->oGui === null) {
			$this->oGui = $oGui;
		}
		
		if(empty($this->documentId)) {
			$this->documentId = $iDocumentID;
		}
		
		if($this->documentId) {
			$document = \Ext_Thebing_Inquiry_Document::getInstance($this->documentId);
		}
		
		if($this->sourceDocumentId) {
			$sourceDocument = \Ext_Thebing_Inquiry_Document::getInstance($this->sourceDocumentId);
		}
		
		// Position Cache zurücksetzen
        $oGui->setDocumentPositionsInitialized(false);
		$oGui->resetDocumentPositions();

		$iCurrentUser = (int)$user_data['id'];

		$sAction = $_VARS['action'];

		$bRefreshCreditnote = true;
		$bNegate = $this->bNegate;
		$bShowAddressSelect = Ext_Thebing_Access::hasRight('thebing_gui_addressee_export');

		if($sType == 'creditnote_edit') {
			$bRefreshCreditnote = false;
			$sType = 'creditnote';
		} elseif($sType == 'creditnote_subagency_edit') {
			$bRefreshCreditnote = false;
			$sType = 'creditnote_subagency';
		}


		// für eine spezielle Kunden Differenzrechnung muss ein Flag gesetzt werden
		$iIsCredit = 0;
		if(
			$bNegate ||
			($document && $document->is_credit)
		) {
			$iIsCredit = 1;
		}

		$iSelectedID = reset($aSelectedIds);

		if($iSelectedID == false) {
			$iSelectedID = 0;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$mTemplateType = 'document_invoice_customer';
		$sDocumentType = 'brutto';

		if(empty($_VARS['template_type'])) {
			$_VARS['template_type'] = $oGui->getOption('template_type');
		}

		// TODO Das sollte rausfliegen und die Inquiry/Enquiry sollte immer bereits gesetzt sein!
		if($sType == 'insurance') {

			$oLink = new Ext_TS_Inquiry_Journey_Insurance($iSelectedID);
			$oEntity = Ext_TS_Inquiry::getInstance($oLink->inquiry_id);
			$oDocument = new Ext_Thebing_Inquiry_Document($oLink->document_id);

		} else if (
			$sType == 'teacher' || $_VARS['template_type'] === 'document_teacher'
		) {

			$oEntity = Ext_Thebing_Teacher::getInstance($iSelectedID);

			if($iDocumentID > 0) {
				$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentID);
			} else {
				$oDocument = $oEntity->newDocument($sDocumentType, false);
			}

			$bShowAddressSelect = false;

		} else {

			// Anmerkung Enquiry: Bei Offer steht Enquiry in _oInquiry, bei additional_document geht das über $_VARS['template_type'] direkt hier drunter
			if($this->_oInquiry === null) {
				$oEntity = Ext_TS_Inquiry::getInstance($iSelectedID);
			} else {
				$oEntity = $this->_oInquiry;
			}

			if($iDocumentID > 0) {
				$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocumentID);
			} else {
				$oDocument = $oEntity->newDocument($sDocumentType, false);
				$oDocument->is_credit = (int)$iIsCredit;
			}

		}

		$this->setEntity($oEntity);

		if ($oEntity instanceof Ext_TS_Inquiry_Abstract) {
			// Muss für self::getAdditionalDataForDialog() gesetzt werden (vor allem bei Enquiry additional_document!)
			$this->setInquiry($oEntity);
		}

		/**
		 * Fehler abfangen
		 */
		$aErrors = array();
 
		if($oEntity instanceof Ext_TS_Inquiry_Abstract && $oEntity->getCurrency() == 0) {
			$aErrors[] = 'INQUIRY_NO_CURRENCY_ID';
		}

		if(!empty($aErrors)) {
			return $aErrors;
		}

		$oSchool = $oEntity->getSchool();
		$iSchoolId = $oSchool->id;

		$sLanguage = $oEntity->getDocumentLanguage();

		// Schulsprachen
		$aSchoolLanguages = $oSchool->getLanguageList();

		// Firma
		$companyId = $this->oGui->getRequest()->get('company_id');

		$InvoiceItemService = new Ts\Service\Invoice\Items($oSchool);
		$companySettings = $InvoiceItemService->getCompanySettings();
		$allCompanyIds = [];
		if ($oEntity instanceof Ext_TS_Inquiry_Abstract) {
			$inboxId = $oEntity->getInbox()->id;

			if ($inboxId) {
				$allCompanyIds = $companySettings[$oSchool->id][$inboxId] ?? [];
			} elseif (!empty($companySettings[$oSchool->id])) {
				$allCompanyIds = reset($companySettings[$oSchool->id]);
			}
			$allCompanyIds = array_unique(array_merge(...array_values($allCompanyIds)));
		}

		$lockCompanyId = false;
		if(empty($companyId)) {

			if($document && $document->exist()) {
				$companyId = $document->getLastVersion()->company_id;
				if (
					$document->isReleased() ||
					(
						isset($sourceDocument) &&
						$sourceDocument?->exist()
					)
				) {
					$lockCompanyId = true;
				}
			} elseif($sourceDocument && $sourceDocument->exist()) {
				$companyId = $sourceDocument->getLastVersion()->company_id;
				$lockCompanyId = true;
			} else {
				$companyId = reset($allCompanyIds);	
			}		

		}

		// Sonstiges
		$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);
		$iSchoolForFormat = $oSchoolForFormat->id;

		$this->oSchool = $oSchool;

		$aLanguages = array('' => L10N::t('Bitte wählen Sie zuerst eine Vorlage aus', $oGui->gui_description));

		$oFormat = new Ext_Thebing_Gui2_Format_Date(false, $iSchoolForFormat);

		$bPositionsEditable = true;

		if($sType == 'invoice_text') {
			$bPositionsEditable = false;
		}

		if($sType == 'invoice_current') {
			$bPositionsEditable = true;
		}

		$aUsers = \Ext_Thebing_User::getList(true);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		switch($sType) {

			case 'group_invoice':
			case 'diff':
			case 'brutto_diff':
			case 'brutto_diff_special':
			case 'brutto_diff_partial':
			case 'netto_diff':
			case 'credit_brutto':
			case 'credit_netto':
			case 'invoice':
			case 'invoice_text':
			case 'invoice_current':
			case 'brutto':
			case 'netto':
				$sDialogTitle = L10N::t('Neue Rechnung', $oGui->gui_description);
				if($iDocumentID > 0) {
					// TODO : document_number prüfen
					// rausgenommen da er immer Doc. ID 1 genommen hat und ich keine zeit hab das nachzuprüfen warum
					//$sDialogTitle = L10N::t('Dokument "{customer_name}"', $oGui->gui_description);
					$sDialogTitle = L10N::t('Rechnung "{document_number}"', $oGui->gui_description);
				}
				break;

			case 'creditnote':
				$sDialogTitle = L10N::t('Neue Agenturgutschrift', $oGui->gui_description);
				if($this->documentId > 0) {
					// TODO : document_number prüfen
					// rausgenommen da er immer Doc. ID 1 genommen hat und ich keine zeit hab das nachzuprüfen warum
					//$sDialogTitle = L10N::t('Creditnote "{customer_name}"', $oGui->gui_description);
					$sDialogTitle = L10N::t('Agenturgutschrift "{document_number}"', $oGui->gui_description);
				}
				$oOtherDocument = $oDocument->getCreditNote();
				if(!$oOtherDocument) {
					$oOtherDocument = $oEntity->newDocument('creditnote');
				}
				break;

			case 'creditnote_subagency':

				$sDialogTitle = L10N::t('Neue Unteragenturgutschrift', $oGui->gui_description);
				if($this->documentId > 0) {
					$sDialogTitle = L10N::t('Unteragenturgutschrift "{document_number}"', $oGui->gui_description);
				}
				$oOtherDocument = $oDocument->getCreditNoteSubAgency();
				if(!$oOtherDocument) {
					$oOtherDocument = $oEntity->newDocument('creditnote_subagency');
				}

				break;
			case 'storno':
				$sDialogTitle = L10N::t('Stonierung', $oGui->gui_description);
				break;

			case 'proforma_netto_diff':
			case 'proforma_brutto_diff':
			case 'proforma':
				$sDialogTitle = L10N::t('Neue Proforma', $oGui->gui_description);
				if($iDocumentID > 0) {
					// TODO : document_number prüfen
					// rausgenommen da er immer Doc. ID 1 genommen hat und ich keine zeit hab das nachzuprüfen warum
					//$sDialogTitle = L10N::t('Proforma "{customer_name}"', $oGui->gui_description);
					$sDialogTitle = L10N::t('Proforma "{document_number}"', $oGui->gui_description);
				}
				break;

			case 'teacher':
			case 'job_opportunity':
			case 'additional_document':
				$sDialogTitle = L10N::t('Neues Dokument', $oGui->gui_description);
				if($iDocumentID > 0) {
					// rausgenommen da er immer Doc. ID 1 genommen hat und ich keine zeit hab das nachzuprüfen warum
					//$sDialogTitle = L10N::t('Dokument - "{customer_name}"', $oGui->gui_description);
					$sDialogTitle = L10N::t('Dokument editieren', $oGui->gui_description);
				}
				break;

			case 'insurance':
				$sDialogTitle = $oGui->t('Versicherung "{contact_name}"');
				break;

			case 'diff_proforma_brutto':
			case 'proforma_brutto':
			case 'proforma_netto':
				if($iDocumentID > 0) {
					$sDialogTitle = L10N::t('Proforma "{document_number}"', $oGui->gui_description);
				}
				break;

			case 'offer':
				$sDialogTitle = L10N::t('Neues Angebot', $oGui->gui_description);
				if($iDocumentID > 0) {
					$sDialogTitle = L10N::t('Angebot "{document_number}"', $oGui->gui_description);
				}
				break;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Set default values

		$iDocumentId = (int)$iDocumentID;
		$iTemplateId = 0;
		$sDate = '';
		$sSubject = '';
		$sAddress = '';
		$sIntro	= '';
		$sOutro = '';
		$sSignaturText = '';
		$sSignaturTmg = '';

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get last version, set version values
		if($document) {
			$oVersion = $document->getLastVersion();
		} elseif($sType == 'creditnote') {
			// version für Template daten auf die vom credit note doc. anpassen
			$oVersion = $oOtherDocument->getLastVersion();
		} else {
			$oVersion = $oDocument->getLastVersion();
		}
		

		$this->oLastVersion = $oVersion;
		$iSignatureUserId = 0;

		$oPaymentConditionService = null;
		if(!in_array($sType, ['additional_document', 'job_opportunity', 'teacher'])) {
			$oPaymentConditionService = new Ext_TS_Document_PaymentCondition($oEntity);
		}

		if($oVersion) {
			$iTemplateId = $oVersion->template_id;
			$sDate = $oVersion->date;
			$sSubject = $oVersion->txt_subject;
			$sAddress = $oVersion->txt_address;
			$sIntro = $oVersion->txt_intro;
			$sOutro = $oVersion->txt_outro;
			$sSignaturText = $oVersion->txt_signature;
			$sSignaturTmg = $oVersion->signature;
			$iSignatureUserId = $oVersion->signature_user_id;

			if($oPaymentConditionService) {
				if(($oPaymentCondition = $oVersion->getPaymentCondition()) !== null) {
					// Wenn eine Zahlungsbedingung gesetzt ist, muss immer diese verwendet/gesetzt werden
					$oPaymentConditionService->setPaymentCondition($oPaymentCondition);
				}

				$oPaymentConditionService->setDocumentDate($oVersion->date);

				$aPaymentTerms = array_values($oVersion->getPaymentTerms());

				// Bei Gruppen müssen die Anzahlungsbeträge wieder summiert werden (#9430)
				if($oEntity->hasGroup()) {
					$oVersion->calculateBackPrepayAmount($aPaymentTerms);
				}

				$oPaymentConditionService->setPaymentTerms($aPaymentTerms);

				if($oDocument->partial_invoice) {
					$oPaymentConditionService->setPartialInvoice();
				}
			}

		} else {
			$oVersion = $oDocument->newVersion();

			if($oPaymentConditionService && ($iIsCredit || $sType === 'creditnote')) {
				// Auf leer setzen, da das bei Credit/Creditnote so keinen Sinn macht (auch in reloadPositionsTable)
				$oPaymentConditionService->setPaymentCondition(new Ext_TS_Payment_Condition());
			}
		}

		$sView = 'net';
		$bGroup = false;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		// ID, welche für die eindeutige Dialog-ID zuständig ist!
		// Ist bei Gutschriften unterschiedlich zur aktuellen Doc-ID

		$iDialogDocumentId = $iDocumentId;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Template- und Dokument-Typ suchen

		## START Wenn additional_documents editiert werden sollen brauchen wir wieder
		// die Rechnungspos des LETZTEN Doc deswegen sTyp umschreiben damit keine Redundanz entsteht
		if(
			$sType == 'invoice_text' &&
			$oDocument->type == 'additional_document'
		) {
			$sType = 'additional_document';
		}
		## ENDE

		$bNoPacketPrices = false;
		$bDiffEdit = false;

		switch($sType) {

			case 'proforma':

				if(
					$oEntity->payment_method == 1 ||
					$oEntity->payment_method == 3
				) {
					$mTemplateType = 'document_invoice_customer';
					$sDocumentType = 'proforma_brutto';
					$sView = 'gross';
					$bGroup = $oEntity->hasGroup();
				} else {
					$mTemplateType = 'document_invoice_agency';
					$sDocumentType = 'proforma_netto';
					$sView = 'net';
					$bGroup = $oEntity->hasGroup();
				}

				break;

			case 'invoice':
			case 'brutto':
			case 'netto':
			case 'offer':
			case 'proforma_brutto':
			case 'proforma_netto':

				if($oDocument->id > 0) {

					if(strpos($oDocument->type, 'brutto') !== false) {
						$mTemplateType = 'document_invoice_customer';
						$sDocumentType = $oDocument->type;
						$sView = 'gross';
						$bGroup = $oEntity->hasGroup();
					} else {
						$mTemplateType = 'document_invoice_agency';
						$sDocumentType = $oDocument->type;
						$sView = 'net';
						$bGroup = $oEntity->hasGroup();
					}

				} elseif(!$oEntity->hasNettoPaymentMethod()) {

					$mTemplateType = 'document_invoice_customer';
					$sDocumentType = 'brutto';
					$sView = 'gross';
					$bGroup = $oEntity->hasGroup();

				} else {

					$mTemplateType = 'document_invoice_agency';
					$sDocumentType = 'netto';
					$sView = 'net';
					$bGroup = $oEntity->hasGroup();

				}

				if ($sType === 'offer') {
					$mTemplateType = $mTemplateType === 'document_invoice_agency' ? 'document_offer_agency' : 'document_offer_customer';
					if (!$oDocument->exist()) {
						// Beu neuen Dokumenten kommt nur brutto/netto rein, bei bestehenden ist der Typ aber schon offer_brutto/offer_netto
						$sDocumentType = 'offer_'.$sDocumentType;
					}
				}

				break;

			case 'creditnote':

				$mTemplateType = 'document_invoice_credit';
				$sView = 'net';
				$bGroup = $oEntity->hasGroup();

				// Ursprüngliche Rechnung (dokument)
				$iParentDocumentId = $oDocument->id;

				//$bPositionsEditable = false;

				if($bRefreshCreditnote !== true) {
					#$oDocument = $oOtherDocument;
				}

				$iDocumentId = (int)$document->id;

				$sDocumentType = $sType;

				if(
					$sourceDocument &&
					$sourceDocument->isProforma()
				) {
					$sDocumentType = 'proforma_creditnote';
				}

				// version wieder an ursprungsrechnung anpassen für Items
				$oVersion = $sourceDocument?->getLastVersion();
				// wenn noch keine, dann rechne prov. aus da items von brutto rechnung kommen

				if($oVersion === null) {
					$oVersion = $document->newVersion();
				}

				if(
					$bRefreshCreditnote === true ||
					!$document->exist()
				) {
					$oVersion->bCalculateProvisionNew = true;
				} else {
					$oVersion->bCalculateProvisionNew = false;
					$bPositionsEditable = false;
				}

				break;

			case 'creditnote_subagency':

				$mTemplateType = 'document_invoice_credit';
				$sView = 'net';
				$bGroup = $oEntity->hasGroup();

				// Ursprüngliche Rechnung (dokument)
				$iParentDocumentId = $oDocument->id;

				//$bPositionsEditable = false;

				if($bRefreshCreditnote !== true) {
					#$oDocument = $oOtherDocument;
				}

				$iDocumentId = (int)$document->id;

				$sDocumentType = $sType;

//				if(
//					$sourceDocument &&
//					$sourceDocument->isProforma()
//				) {
//					$sDocumentType = 'proforma_creditnote';
//				}

				// version wieder an ursprungsrechnung anpassen für Items
				$oVersion = $sourceDocument?->getLastVersion();
				// wenn noch keine, dann rechne prov. aus da items von brutto rechnung kommen

				if($oVersion === null) {
					$oVersion = $document->newVersion();
				}
				
				if(
					$bRefreshCreditnote === true ||
					!$document->exist()
				) {
					$oVersion->bCalculateProvisionNew = true;
				} else {
					$oVersion->bCalculateProvisionNew = false;
					$bPositionsEditable = false;
				}

				break;

			case 'invoice_text':
			case 'invoice_current':

				$sDocumentType = $oDocument->type;

				if(strpos($sDocumentType, 'netto') === false) {
					$mTemplateType = 'document_invoice_customer';
					$sView = 'gross';
					$bGroup = $oEntity->hasGroup();
				} else {
					$mTemplateType = 'document_invoice_agency';
					$sView = 'net';
					$bGroup = $oEntity->hasGroup();
				}

				if(strpos($sDocumentType, 'storno') !== false) {
					$mTemplateType = 'document_invoice_storno';
					if(
						$oEntity->payment_method == 0 ||
						$oEntity->payment_method == 2
					) {
						$sView = 'net';
					}
				}

				if(strpos($sDocumentType, 'diff') !== false) {
					$bDiffEdit = true;
				}

				break;

			case 'group_invoice':

				if(
					$oEntity->payment_method == 1 ||
					$oEntity->payment_method == 3
				) {
					$mTemplateType = 'document_invoice_customer';
					$sDocumentType = 'group_brutto';
					$sView = 'gross';
				} else {
					$mTemplateType = 'document_invoice_agency';
					$sDocumentType = 'group_netto';
					$sView = 'net';
				}

				break;

			case 'storno':

				$mTemplateType = 'document_invoice_storno';
				$sDocumentType = 'storno';
				$sView = 'gross';

				if(strpos($sDocumentType, 'storno') !== false) {
					$mTemplateType = 'document_invoice_storno';
					if(
						$oEntity->payment_method == 0 ||
						$oEntity->payment_method == 2
					) {
						$sView = 'net';
					}
				}

				$bGroup = $oEntity->hasGroup();
				$oDocument = $oEntity->newDocument('storno');

				break;

			case 'diff':

				$bTempNetto = true;

				if(
					(
						(
							$oEntity->payment_method == 1 ||
							$oEntity->payment_method == 3
						) && (
							$oDocument->id <= 0
						)
					) || (
						$oDocument->type == 'brutto_diff' ||
						$oDocument->type == 'brutto_diff_special'
					)
				) {
					$bTempNetto = false;
				}

				if(!$bTempNetto) {
					$mTemplateType = 'document_invoice_customer';
					$sDocumentType = 'brutto_diff';
					$sView = 'gross';
				} else {
					$mTemplateType = 'document_invoice_agency';
					$sDocumentType = 'netto_diff';
					$sView = 'net';
				}

				$bNoPacketPrices = true;

				break;

			case 'brutto_diff':
			case 'brutto_diff_special':
			case 'brutto_diff_partial':
			case 'proforma_brutto_diff':

				$mTemplateType = 'document_invoice_customer';
				$sDocumentType = $sType;
				$bNoPacketPrices = true;
				$sView = 'gross';
				if($oDocument->id > 0) {
					$bDiffEdit = true;
				}

				break;

			case 'netto_diff':
			case 'proforma_netto_diff':

				$mTemplateType = 'document_invoice_agency';
				$sDocumentType = $sType;
				$sView = 'net';
				$bNoPacketPrices = true;
				if($oDocument->id > 0) {
					$bDiffEdit = true;
				}

				break;

			case 'credit_brutto':

				$mTemplateType = 'document_invoice_customer';
				$sView = 'gross';
				$bGroup = $oEntity->hasGroup();

				// Leeres Dokument erstellen damit die pos. neu geladen werden
				$oDocument = $oEntity->newDocument();
				$iDialogDocumentId = 0;
				$sDocumentType = 'credit_brutto';

				break;

			case 'credit_netto':

				$mTemplateType = 'document_invoice_agency';
				$sView = 'net';
				$bGroup = $oEntity->hasGroup();

				// Leeres Dokument erstellen damit die pos. neu geladen werden
				$oDocument = $oEntity->newDocument();
				$iDialogDocumentId = 0;
				$sDocumentType = 'credit_netto';

				break;

			case 'teacher':
			case 'job_opportunity':
			case 'additional_document':

				// Andere Dokumente (additional_document)
				$mTemplateType = array();
				$mTemplateType[] = 'document_loa';
				$mTemplateType[] = 'document_studentrecord_additional_pdf';
				$mTemplateType[] = 'document_studentrecord_visum_pdf';
				$mTemplateType[] = 'document_job_opportunity';
				$mTemplateType[] = 'document_student_cards';
				$sDocumentType = 'additional_document';
				if(!empty($_VARS['template_type'])) {
					$mTemplateType = $_VARS['template_type'];
				} 

				// Falls das Dokument editiert wird hole keine ID von bestehenden Doc.
				if($oDocument->id > 0) {
					$iDocument = $oDocument->id;
				} else if ($oEntity instanceof Ext_TS_Inquiry_Abstract) {
					/**
					 * Letztes Dokument holen für z.B. LOA
					 * Hier wurde das letze additional_document gesucht, das war falsch!
					 * Für die Rechnungspositionen muss man doch das letzte Rechnungsdokumente suchen
					 */
					$iDocument = (int)$oEntity->getDocuments('invoice', false);
				}

				if($iDocument > 0) {
					$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iDocument);
					$oVersion = $oDocument->getLastVersion();
					if ($oVersion === null) {
						throw new RuntimeException('No version for document '.$iDocument.' (additional_document)');
					}

					$bGroup = false;
					if ($oEntity instanceof \Ext_TS_Inquiry_Abstract) {
						if(strpos($oDocument->type, 'brutto') !== false) {
							$sView = 'gross';
							$bGroup = $oEntity->hasGroup();
						} else {
							$sView = 'net';
							$bGroup = $oEntity->hasGroup();
						}
					}
				} else {
					// Kunde hat noch keine Rechnungsdoc also ein leeres für die Items auslesen
					if(!$oDocument instanceof Ext_Thebing_Inquiry_Document) {
						$oDocument = $oEntity->newDocument($sDocumentType);
					}
					$oVersion = $oDocument->newVersion();
				}

				// Nicht editierbar
				$bPositionsEditable = false;

				break;

			default:
				__pout($sType);

		}

		if(Ext_Thebing_Access::hasRight('thebing_invoice_document_refresh_always')) {
			$bPositionsEditable = true;
		}

		// Speichern ob Gruppe
		$this->bGroup = $bGroup;

		$mInbox = null;
		if ($oEntity instanceof Ext_TS_Inquiry) {
			$oInbox = $oEntity->getInbox();
			// Inbox ist bei Anfragen nur ggf. vorhanden
			if ($oInbox != null) {
				$mInbox = $oInbox->id;
			}
		}

		$aTemplates = Ext_Thebing_Pdf_Template_Search::s($mTemplateType, false, $iSchoolId, $mInbox, true);
		$aTemplates = Ext_thebing_Util::addEmptyItem($aTemplates);

		// Korrekten view anhand von Template ermitteln
		$oTemplate = null;
		if($iTemplateId > 0) {

			if($oVersion && $oVersion->template_language) {
				$sLanguage = $oVersion->template_language;
			}

			$oTemplate = Ext_Thebing_Pdf_Template::getInstance($iTemplateId);
			$this->aTemplateData = $this->getTemplateData($oEntity, $oTemplate, $oDocument, $iCurrentUser);
			$aLanguages = array('' => '');
			$aLanguagesLabels = Ext_Thebing_Data::getAllCorrespondenceLanguages();

			// Falls Dokument in anderer View generiert wurde…
			if(!isset($aTemplates[$oTemplate->id])) {
				$aTemplates[$oTemplate->id] = $oTemplate->getName();
			}

			foreach($oTemplate->languages as $sLang) {

				// Es muss geprüft werden ob die gerade aktive Schule auch die Templatesprache verwenden darf
				if(!isset($aSchoolLanguages[$sLang])) {
					continue;
				}

				$aLanguages[$sLang] = $aLanguagesLabels[$sLang];

			}

		} else {

			$this->aTemplateData = array();

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Templates

		$aSignaturImgs = array();
		$aSignaturImgs_ = $oSchool->getSchoolFiles(2, null, true);

		$aSignaturImgs[''] = '';
		foreach((array)$aSignaturImgs_ as $aTemp){
			$aSignaturImgs[$aTemp['id']] = $aTemp['description'];
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Dialog

		$oDialog = $oGui->createDialog(
			$sDialogTitle,
			$sDialogTitle,
			$sDialogTitle
		);

		// Set ID
		$oDialog->id = 'DOCUMENT_' . (int)$iSelectedID;
		$oDialog->sDialogIDTag = 'DOCUMENT_';

		$oDocumentTab = $oDialog->createTab(L10N::t('Dokument', $oGui->gui_description));
		$oPlaceholderTab = $oDialog->createTab(L10N::t('Platzhalter', $oGui->gui_description));
		$oPlaceholderTab->class = 'tab_placeholder';
		$oPlaceholderTab->no_padding = true;
		$oPlaceholderTab->no_scrolling = true;

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->id = 'document_template_items';

		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = "hidden";
		$oHidden->name = 'document_id';
		$oHidden->id= 'saveid[document_id]';
		$oHidden->value = (int)$this->documentId;
		$oDiv->setElement($oHidden);

		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = "hidden";
		$oHidden->name = 'source_document_id';
		$oHidden->id= 'saveid[source_document_id]';
		$oHidden->value = (int)$this->sourceDocumentId;
		$oDiv->setElement($oHidden);

//		if($sType == 'creditnote') {
//			$oHidden = new Ext_Gui2_Html_Input();
//			$oHidden->type = "hidden";
//			$oHidden->name = 'documentFromCreditNote_id';
//			$oHidden->value = (int)$iParentDocumentId;
//			$oDiv->setElement($oHidden);
//		}

		$iIsRefesh = 0;
		if($bPositionsEditable){
			$iIsRefesh = 1;
		}

		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = "hidden";
		$oHidden->name = 'is_refesh';
		$oHidden->id = 'saveid[is_refesh]';
		$oHidden->value = $iIsRefesh;
		$oDiv->setElement($oHidden);

		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = "hidden";
		$oHidden->name = 'is_credit';
		$oHidden->id = 'saveid[is_credit]';
		$oHidden->value = $iIsCredit;
		$oDiv->setElement($oHidden);

		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = "hidden";
		$oHidden->name = 'type';
		$oHidden->value = $sType;
		$oDiv->setElement($oHidden);

		$oHidden = new Ext_Gui2_Html_Input();
		$oHidden->type = "hidden";
		$oHidden->id = 'saveid[document_type]';
		$oHidden->name = 'document_type';
		$oHidden->value = $sDocumentType;
		$oDiv->setElement($oHidden);

		// Zahlungsbedingungen für Select und prüfen, ob Teilrechnung möglich
		$aPaymentConditionsOptions = [];
		$aPaymentConditionsWithInstallments = [];
		$aPaymentConditions = Ext_TS_Payment_Condition::getRepository()->findAll();
		foreach($aPaymentConditions as $oPaymentCondition) {
			if($oPaymentCondition->isEligibleForPartialInvoice()) {
				$aPaymentConditionsWithInstallments[] = (int)$oPaymentCondition->id;
			}
			$aPaymentConditionsOptions[$oPaymentCondition->id] = $oPaymentCondition->name;
		}

		// Checkbox Teilrechnung
		if(
			$sType !== 'additional_document' &&
			Ext_Thebing_Access::hasRight('thebing_invoice_difference_partial') &&
			!empty($aPaymentConditionsWithInstallments) &&
			!$bGroup &&
			$sView !== 'net' && (
				$oDocument->partial_invoice || // Edit, weil das irgendwie als $sType = invoice_text reinkommt
				$sType === 'invoice' || // Neue Rechnung
				$sType === 'brutto_diff_partial'
			)
		) {

			$oDivRow = $oDialog->create('div');
			$oDivRow->class = 'row';

			$oDivColLeft = $oDialog->create('div');
			$oDivColLeft->class = 'col-lg-6';

			$aCheckboxParameter = [
				'id' => 'saveid[partial_invoice]',
				'name' => 'save[partial_invoice]',
				'value' => '1',
				'checked' => (($oEntity instanceof Ext_TS_Inquiry && $oEntity->partial_invoices_terms > 0) || $oDocument->partial_invoice) ? 'checked' : ''
			];
			
			if($oEntity->partial_invoices_terms > 0) {
				$aCheckboxParameter['readonly'] = true;
				$oDivColLeft->setElement($oDialog->createSaveField('hidden', [
					'name' => 'save[partial_invoice]',
					'value' => 1,
				]));
			}
			
			$oInput = $oDialog->createSaveField('checkbox', $aCheckboxParameter);

			// Im JS werden alle nicht passenden Zahlungsbedingen deaktiviert, wenn Checkbox aktiviert wird
			$oInput->setDataAttribute('installment-ids', json_encode($aPaymentConditionsWithInstallments));

			$oDivCheckbox = $oDialog->create('div');
			$oDivCheckbox->class = 'checkbox';
			$oLabel = $oDialog->create('label');
			$oLabel->setElement($oInput);
			$oLabel->setElement('<span class="document_billing_period"></span>');
			$oDivCheckbox->setElement($oLabel);
			$oDivColLeft->setElement($oDialog->createRow($oGui->t('Teilrechnung'), $oDivCheckbox));

			$oDivRow->setElement($oDivColLeft);
			
			$oDocumentTab->setElement($oDivRow);
			
		}

		$oDivRow = $oDialog->create('div');
		$oDivRow->class = 'row';
		
		$oDivColLeft = $oDialog->create('div');
		$oDivColLeft->class = 'col-lg-6';
		
		$oDivColRight = $oDialog->create('div');
		$oDivColRight->class = 'col-lg-6';
		
		$sId = 'saveid[template_id]';
		$sName = 'save[template_id]';
		$aTemplateSaveFieldOptions = array(
			'id' => $sId,
			'name' => $sName,
			#'style'=>'max-width: 780px; width: auto;',
			'required' => 1,
			'select_options'=>$aTemplates,
			'default_value'=>$iTemplateId
		);

		$oTemplateRow = $oDialog->createRow(L10N::t('Vorlage', $oGui->gui_description), 'select',$aTemplateSaveFieldOptions);
		$oDivColLeft->setElement($oTemplateRow);

		// nur sicherheitshalber prüfen ob sprache noch vorhanden ist, da immer einge gewählt sein MUSS (sonst js probleme)
		if(
			count($aLanguages) > 0 &&
			!array_key_exists($sLanguage, $aLanguages)
		) {
			foreach(array_keys($aLanguages) as $sKey) {
				// den Leeren/Hinweis Eintrag NICHT wählen (falls vorhanden)
				if(!empty($sKey)) {
					$sLanguage = $sKey;
					break;
				}
			}
		}

		$sId = 'saveid[language]';
		$sName = 'save[language]';
		$aTemplateSaveFieldOptions = array(
			'id' => $sId,
			'name' => $sName,
			#'style'=>'max-width: 780px; width: auto;',
			'required' => 1,
			'select_options'=> $aLanguages,
			'default_value'=>$sLanguage
		);
		$oTemplateRow = $oDialog->createRow(L10N::t('Sprache', $oGui->gui_description), 'select', $aTemplateSaveFieldOptions);
		$oDivColLeft->setElement($oTemplateRow);

		$oDocumentAddress = new Ext_Thebing_Document_Address($oEntity);
		$sDefaultValue = $oDocumentAddress->getSelectedAdressSelect($oVersion, $sView, $sType);

		$bAddressSelectDisabled = $document && $document->isReleased();

		// Adresse
		if($bShowAddressSelect) {
			$oDivColLeft->setElement($oDialog->createRow($oGui->t('Adresse'), 'select', array(
				'id' => 'saveid[address_select]',
				'name' => 'save[address_select]',
				'required' => 1,
				'select_options' => $oDocumentAddress->getAddressSelectOptions($sType !== 'additional_document'),
				'default_value' => $sDefaultValue,
				'disabled' => $bAddressSelectDisabled
			)));
		}

		// Feld versteckt einbinden damit die Adresse gespeichert wird
		if (!$bShowAddressSelect || $bAddressSelectDisabled) {
			$oDivColLeft->setElement($oDialog->createSaveField('hidden', [
				'name' => 'save[address_select]',
				'value' => $sDefaultValue,
			]));
		}

		// Zahlungsbedingung
		if($oPaymentConditionService) {
			$iPaymentConditionId = 0;
			if(($oPaymentCondition = $oPaymentConditionService->getPaymentCondition()) !== null) {
				$iPaymentConditionId = $oPaymentCondition->id;
			}

			// Wenn Teilrechnung bereits vorhanden, muss verwendete Zahlungsbedingung gesetzt und gesperrt werden
			$bDisabled = false;
			if($sType === 'brutto_diff_partial') {
				$oSelectedDocument = Ext_Thebing_Inquiry_Document::getInstance(reset($_VARS['id']));
				if(($oPaymentCondition = $oSelectedDocument->getLastVersion()->getPaymentCondition()) !== null) {
					$iPaymentConditionId = $oPaymentCondition->id;
					$bDisabled = true;
				}
			}

			// Teilrechnungseinstellung nicht bei Storno anwenden
			if(
				$sType !== 'storno' &&
				$oEntity instanceof Ext_TS_Inquiry &&
				$oEntity->partial_invoices_terms > 0
			) {
				$iPaymentConditionId = $oEntity->partial_invoices_terms;
				$bDisabled = true;
			}

			// Wert wird in reloadPositionsTable benötigt. sonst würde die erste passende Zahlungsbedingung ermittelt
			// Das Feld wird auch vom JS nach reloadPositionsTable erzeugt, wenn Teilrechnung ausgewählt wurde
			if($bDisabled) {
				$oDivColRight->setElement($oDialog->createSaveField('hidden', [
					'name' => 'save[payment_condition_select]',
					'value' => $iPaymentConditionId,
				]));
			}

			$oDivColRight->setElement($oDialog->createRow($oDialog->oGui->t('Zahlungsbedingung'), 'select', [
				'id' => 'saveid[payment_condition_select]',
				'name' => 'save[payment_condition_select]',
				'select_options' => Util::addEmptyItem($aPaymentConditionsOptions),
				'default_value' => $iPaymentConditionId,
				'disabled' => $bDisabled
			]));
		}

		// Nummernkreis-Auswahl falls neu, notwendig und das Recht vorhanden ist
		if($iDocumentId == 0) {

			if($oEntity instanceof Ext_TS_Inquiry) {
				$oInbox = $oEntity->getInbox();
				Ext_TS_NumberRange::setInbox($oInbox);
				Ext_TS_NumberRange::setCurrency($oEntity->getCurrency());
			}

			if(
				$sType !== 'offer' &&
				!empty($companyId)
			) {
				Ext_TS_NumberRange::setCompany($companyId);
			}

			$sTypeNumberRange = $oEntity->getTypeForNumberrange($sDocumentType, $mTemplateType);

			$aTemplateSaveFieldOptions = array(
				'id' => 'saveid[numberrange_id]',
				'name' => 'save[numberrange_id]',
				#'style'=>'max-width: 780px; width: auto;',
				'required' => 1,
			);

			// Die Optionen werden durch reloadPositionsTable neu gesetzt
			$oNumberrangeRow = Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangeRow($oGui, $oDialog, $aTemplateSaveFieldOptions, $sDocumentType, $sTypeNumberRange, $this->oSchool);
			if($oNumberrangeRow) {
				$oDivColRight->setElement($oNumberrangeRow);
			}

		}

		if(
			$sType === 'additional_document' &&
			$oEntity instanceof \Ext_TS_Inquiry_Abstract /*&&
			$oInquiry->canShowPositionsTable()*/
		) {
			$aInvoices = $oEntity->getDocuments(['invoice_with_creditnote', 'offer'], true, true);
			$aInvoiceOptions = [];
			foreach($aInvoices as $oInvoice) {
				$aInvoiceOptions[$oInvoice->id] = $oInvoice->document_number;
			}

			if(!empty($oVersion->invoice_select_id)) {
				$iLastInvoiceId = $oVersion->invoice_select_id;
			} else {
				// Altes Verhalten: Letzte Rechnung auswählen
				$iLastInvoiceId = (int)$oEntity->getDocuments('invoice', false);
			}

			$oDivColRight->setElement($oDialog->createRow($oGui->t('Rechnung'), 'select', array(
				'id' => 'saveid[invoice_select]',
				'name' => 'save[invoice_select]',
				'required' => 1,
				'select_options' => $aInvoiceOptions,
				'row_style' => 'display: none',
				'default_value' => $iLastInvoiceId
			)));
		}

		if(
			$sType !== 'additional_document' &&
			$sType !== 'offer' &&
			$sType !== 'creditnote' &&
			count($allCompanyIds) > 1
		) {

			$companyOptions = \Ext_Thebing_System::getAccountingCompanies(true);
			$companyOptions = array_intersect_key($companyOptions, array_flip($allCompanyIds));

			// Proforma haben als Standard alle Positionen
			if(
				strpos($sType, 'proforma') !== false ||
				$oDocument->isProforma()
			) {
				$companyOptions = Ext_Thebing_Util::addEmptyItem($companyOptions);
				$companyId = null;
			}

			if ($lockCompanyId) {
				$oDivColRight->setElement($oDialog->createSaveField('hidden', array('name' => 'save[company_id]', 'value' => $companyId)));
			}
			$oDivColRight->setElement($oDialog->createRow($oGui->t('Firma'), 'select', array(
				'id' => 'saveid[company_id]',
				'name' => 'save[company_id]',
				'select_options' => $companyOptions,
				'default_value' => $companyId,
				'readonly' => $lockCompanyId
			)));
				
		}
		
		$oDivRow->setElement($oDivColLeft);
		$oDivRow->setElement($oDivColRight);
		
		$oDocumentTab->setElement($oDivRow);
		
		$oDialog->bSmallLabels = true;
		
		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement($oGui->t('Inhalte'));
		$oDiv->setElement($oH3);

		// Datum
		$oDivDate = new Ext_Gui2_Html_Div();
		$oDivDate->class = 'templateField';
		$oDivDate->style = 'display:none';

		// Dokumententypen von Typ Rechnung
		$aTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice');

		$sId = 'saveid[date]';
		$sName = 'save[date]';
		$sCalendarId = 'saveid[calendar][date]';
		$aOptions = array(
			'value' => $oFormat->formatByValue($sDate),
			'id' => $sId,
			'calendar_id' => $sCalendarId,
			'name' => $sName,
			'class' => 'docment_date_field keyup'
		);

		if ($document && $document->isReleased()) {
			$aOptions['disabled'] = true;
		}

		// Pflichtfeld bei Rechnungstypen
		if(in_array($sType, $aTypes)) {
			#$aOptions['required'] = 1;
		}

		// darf nie veränderbar sein außer beim 1. anlegen oder mit "speziel Recht"
		if(
			!Ext_Thebing_Access::hasRight('thebing_invoice_document_refresh_always') && // Wenn Recht nicht sperren
			strpos($sAction, 'new_') === false && // Bei vorhandenen sperren
			$sType != 'additional_document' && //  additional Docs immer erlauben
			!empty($aOptions['value'])
		) {
			$aOptions = array_merge($aOptions, ['readonly' => true]);
		}

		$oDateRow = $oDialog->createRow(L10N::t('Rechnungsdatum', $oGui->gui_description), 'calendar', $aOptions);
		$oDivDate->setElement($oDateRow);
		$oDiv->setElement($oDivDate);

		// Adresse
		$oDivAdress = new Ext_Gui2_Html_Div();
		$oDivAdress->class = 'templateField';
		$oDivAdress->style = 'display:none';
		$sId = 'saveid[address]';
		$sName = 'save[address]';
		$oAddressRow = $oDialog->createRow(L10N::t('Adresse', $oGui->gui_description), 'textarea', array('default_value' => $sAddress,'id' => $sId, 'name' => $sName,'style'=>'height:100px;'));
		$oDivAdress->setElement($oAddressRow);
		$oDiv->setElement($oDivAdress);

		// Betreff
		$oDivSubject = new Ext_Gui2_Html_Div();
		$oDivSubject->class = 'templateField';
		$oDivSubject->style = 'display:none';
		$sId = 'saveid[subject]';
		$sName = 'save[subject]';
		$oSubjectRow = $oDialog->createRow(L10N::t('Betreff', $oGui->gui_description), 'input', array('default_value' => $sSubject,'id' => $sId, 'name' => $sName,'style'=>''));
		$oDivSubject->setElement($oSubjectRow);
		$oDiv->setElement($oDivSubject);

		// Intro
		$oDivIntro = new Ext_Gui2_Html_Div();
		$oDivIntro->class = 'templateField';
		$oDivIntro->style = 'display:none';
		$sId = 'saveid[intro]';
		$sName = 'save[intro]';
		$oIntroRow = $oDialog->createRow(L10N::t('Text oben', $oGui->gui_description), 'html', array('default_value' => $sIntro,'id' => $sId, 'name' => $sName,'style'=>' height:150px;'));
		$oDivIntro->setElement($oIntroRow);
		$oDiv->setElement($oDivIntro);

		if($sType=='creditnote') {
			$iInquiryDocumentId = $iParentDocumentId;
		} else {
			$iInquiryDocumentId = $iDocumentId;
		}

		// Nur relevant beim Editieren! Bei Templateauswahl: reloadPositionsTable
		$sPositionHtml = '';
		if(
			$iDocumentId > 0 &&
			$oEntity instanceof \Ext_TS_Inquiry_Abstract &&
			$oEntity->canShowPositionsTable() && (
				$iTemplateId == 0 ||
				$oTemplate->canShowInquiryPositions()
			)
		) {

			// Anmerkung: Bei neuen Dokumenten baut reloadPositionsTable die Positionen auf
			$oPositions = Ext_Thebing_Document_Positions::getInstance();
			$oPositions->oGui = $oGui;
			$oPositions->oInquiry = $oEntity;
			$oPositions->sType = $sType;
			$oPositions->iInquiryDocumentId = $document->id;
			$oPositions->iSourceDocumentId = $sourceDocument->id;
			$oPositions->iTemplateId = $iTemplateId;
			$oPositions->sLanguage = $sLanguage;
			$oPositions->bNegate = $bNegate;
			$oPositions->bRefresh = (boolean)$iIsRefesh;
			$oPositions->sAction = $sAction;
			$oPositions->bRefreshCreditnote = $bRefreshCreditnote;
			$oPositions->bIsCredit = (bool)$iIsCredit;
			$oPositions->oPaymentConditionService = $oPaymentConditionService;
			$oPositions->companyId = $companyId;
 
			$sPositionHtml = $oPositions->getTable();

			$this->sTotalAmountColumn = $oPositions->sTotalAmountColumn;
			$this->aPositionsTooltips = $oPositions->aPositionsTooltips;

		}

		// Container für Positionstabelle
		$sPositionHtml = '<div style="display:none;" id="saveid[positionsTable]" class="divPositionContainer">'.$sPositionHtml.'</div>';
		$oDiv->setElement($sPositionHtml);

		// Outro
		$oDivOutro = new Ext_Gui2_Html_Div();
		$oDivOutro->class = 'templateField';
		$oDivOutro->style = 'display:none';
		$sId = 'saveid[outro]';
		$sName = 'save[outro]';
		$oOutroRow = $oDialog->createRow(L10N::t('Text unten', $oGui->gui_description), 'html', array('default_value' => $sOutro,'id' => $sId, 'name' => $sName,'style'=>'height:150px;'));
		$oDivOutro->setElement($oOutroRow);
		$oDiv->setElement($oDivOutro);

		## START Editierbare Layoutfelder
		$oDivEditable = new Ext_Gui2_Html_Div();
		$oDivEditable->id = 'editable_field_container';
		$oDivEditable->style = 'display:none';

		// HTML
		$sId = 'saveid[editable_html_field_0]';
		$sName = 'save[editable_html_field_0]';
		$oEditableRow = $oDialog->createRow(L10N::t('Block', $oGui->gui_description), 'textarea', array('id' => $sId, 'name' => $sName, 'row_style'=>'display:none;','style'=>'height:150px;', 'class' => 'editable_field'));
		$oDivEditable->setElement($oEditableRow);

		// Datum
		$sId = 'saveid[editable_date_field_0]';
		$sName = 'save[editable_date_field_0]';
		$oEditableRow = $oDialog->createRow(L10N::t('Block', $oGui->gui_description), 'calendar', array('id' => $sId, 'name' => $sName, 'row_style'=>'display:none;', 'class' => 'editable_field'));
		$oDivEditable->setElement($oEditableRow);

		// Input
		$sId = 'saveid[editable_text_field_0]';
		$sName = 'save[editable_text_field_0]';
		$oEditableRow = $oDialog->createRow(L10N::t('Block', $oGui->gui_description), 'input', array('id' => $sId, 'name' => $sName, 'row_style'=>'display:none;','style'=>'', 'class' => 'editable_field'));
		$oDivEditable->setElement($oEditableRow);

		$oDiv->setElement($oDivEditable);
		## ENDE

		// Signature Bild
		$oDivSignature = new Ext_Gui2_Html_Div();

		$iDefaultUserSignature = 0;

		if($iSignatureUserId > 0) {
			$iDefaultUserSignature = $iSignatureUserId;
		} elseif($oTemplate->user_signature == 1) {
			$iDefaultUserSignature = $iCurrentUser;
		}

		#if($oTemplate->user_signature != 1){
			$aUsers = Ext_Thebing_Util::addEmptyItem($aUsers, L10N::t('entsprechend Templateeinstellungen', $oGui->gui_description));
		#}

		$sId = 'saveid[signature_user_id]';
		$sName = 'save[signature_user_id]';
		$oSignaturImgRow = $oDialog->createRow(
			L10N::t('Benutzersignatur', $oGui->gui_description),
			'select',
			array(
				'select_options' => $aUsers,
				'default_value' => $iDefaultUserSignature,
				'id' => $sId,
				'name' => $sName,
				'style'=>''
			)
		);
		$oDivSignature->setElement($oSignaturImgRow);

		$sId = 'saveid[signature_img]';
		$sName = 'save[signature_img]';
		$oSignaturImgRow = $oDialog->createRow(
			L10N::t('Signature Bild', $oGui->gui_description),
			'select',
			array(
				'select_options' => $aSignaturImgs,
				'default_value' => $sSignaturTmg,
				'id' => $sId,
				'name' => $sName,
				'style'=>''
			)
		);
		$oDivSignature->setElement($oSignaturImgRow);

		// Signatur Text
		$sId = 'saveid[signature_txt]';
		$sName = 'save[signature_txt]';
		$oSignaturTextRow = $oDialog->createRow(
			L10N::t('Signature', $oGui->gui_description),
			'html',
			array(
				'default_value' => $sSignaturText,
				'id' => $sId,
				'name' => $sName,
				'style'=>''
			)
		);
		$oDivSignature->setElement($oSignaturTextRow);

		$oDiv->setElement($oDivSignature);

		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement($oGui->t('Interne Verwendung'));
		$oDiv->setElement($oH3);

		// Kommentar
		$oDivComment = new Ext_Gui2_Html_Div();
		$sId = 'saveid[comment]';
		$sName = 'save[comment]';
		$oAddressRow = $oDialog->createRow(L10N::t('Kommentar', $oGui->gui_description), 'textarea', array('default_value' => '', 'id' => $sId, 'name' => $sName,'style'=>'height:80px;'));
		$oDivComment->setElement($oAddressRow);
        $oDiv->setElement($oDivComment);
		$oDocumentTab->setElement($oDiv);
		$oDialog->setElement($oDocumentTab);

		if(
			$iDocumentID == 0 &&
			Ext_Thebing_Access::hasRight('thebing_invoice_dialog_document_tab') && (
				$sType === 'proforma' ||
				$sType === 'invoice' || // brutto und netto
				$sType === 'brutto_diff' ||
				$sType === 'netto_diff' //||
				//$sType === 'storno'
			)
		) {
			$oDocumentTab->sTitle = $oGui->t('Rechnung');
			$oTab = $oDialog->createTab($oGui->t('Dokumente'));
			$oTab->class = 'tab_documents';
			$oTab->setElement($oDialog->createNotification($oGui->t('Achtung'), $oGui->t('Die möglichen Dokumente werden erst nach Auswahl einer Vorlage angezeigt.'), 'info', aOptions: ['dismissible' => false]));
			$oDialog->setElement($oTab);
		}

		if(Ext_Thebing_Access::hasRight('thebing_gui_placeholdertab')) {

			$aFilter = array();

			if(isset($_VARS['template_type'])) {
				$sTemplateType = (array)$_VARS['template_type'];
				if(is_array($sTemplateType)) {
					$sTemplateType = reset($sTemplateType);
				}
				$aFilter = Ext_Thebing_Pdf_Template::getPlaceholderData($sTemplateType);
			}

			if($oTemplate) {
				$oPlaceholderTableContent = $oTemplate->getPlaceholderTabContent();
				$sSmartyPlaceholderTableContent = $oTemplate->getSmartyPlaceholderTabContent();
			} else {
				$oPlaceholderTableContent = $oDialog->createNotification($oGui->t('Achtung'), $oGui->t('Die Platzhalter werden erst nach Auswahl einer Vorlage angezeigt.'), 'info', aOptions: ['dismissible' => false]);
				$sSmartyPlaceholderTableContent = false;
			}			

			if (!empty($oPlaceholderTableContent)) {
				$oDiv = $oDialog->create('div');
				$oDiv->class = 'GUIDialogContentPadding';
				$oDiv->setElement($oPlaceholderTableContent);
				$oPlaceholderTab->setElement($oDiv);
			}

			$oDialog->setElement($oPlaceholderTab);

			// Es gibt eine Smarty-Platzhalter-Klasse
			if($sSmartyPlaceholderTableContent !== null) {
				$oPlaceholderTab = $oDialog->createTab(L10N::t('Erweiterte Platzhalter', $oGui->gui_description));
				$oPlaceholderTab->class = 'tab_placeholder_smarty';
				if(empty($sSmartyPlaceholderTableContent)) {
					$oPlaceholderTab->hidden = true;
				}
				$oPlaceholderTab->no_padding = true;
				$oPlaceholderTab->no_scrolling = true;

				$oPlaceholderTab->setElement($sSmartyPlaceholderTableContent);
				$oDialog->setElement($oPlaceholderTab);
			}
			
		}

		// Hinweis bei Storno einblenden
		if($oDocument->type === 'storno') {
			$aAlertMessage = array(
				'type' => 'hint',
				'message' => L10N::t('Bitte beachten Sie, dass eine Stornierung nicht mehr rückgängig gemacht werden kann! Falls es sich um eine Gruppe handelt, dann wird die komplette Gruppe unwiderruflich storniert.', self::$sL10NDescription)
			);
			$oDialog->addAlertMessage($aAlertMessage);
		}

		$oDialog->width = 1200;
		$oDialog->height = 1200;

		// Persistente Rechnung. Alle inputs sollen deaktiviert werden.
		// Siehe framework\system\legacy\admin\extensions\gui2\gui2.js.
		if (
			$document &&
			!$document->isMutable()
		) {
			$oDialog->setOption('readonly', true);
		}

		return $oDialog;

	}

	/**
	 * Gibt je nach Typ des Dokumentes eine Placeholder Klasse zurück
	 * 
	 * @return Ext_TC_Placeholder_Abstract
	 */
	public function getPdfPlaceholderObject(Ext_Thebing_Inquiry_Document_Version $oInquiryDocumentVersion) {
		
		$oPlaceholder = $oInquiryDocumentVersion->getPlaceholderObject();
		
		return $oPlaceholder;
	}

	/**
	 * @param WDBasic $oInquiry
	 * @param $oTemplate
	 * @param Ext_Thebing_Inquiry_Document $oInquiryDocument
	 * @param $iUserId
	 * @param bool $sLang
	 * @param array $aOptions
	 * @return array
	 */
	public function getTemplateData(\Ts\Interfaces\Entity\DocumentRelation $oEntity, $oTemplate, Ext_Thebing_Inquiry_Document $oInquiryDocument, $iUserId, $sLang = false, $aOptions = array()) {
		global $user_data, $_VARS;

		$oDateFormat = new Ext_Thebing_Gui2_Format_Date();

		$oSchool = $oEntity->getSchool();
		$iSchoolId = $oSchool->id;

		$this->oSchool = $oSchool;

		$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool();
		$iSchoolForFormat = $oSchoolForFormat->id;
		
		$aSelectedIds = array();
		if(isset($aOptions['selected_ids'])){
			$aSelectedIds = (array)$aOptions['selected_ids'];
		}

		if(!$sLang){
			$sLang = $oEntity->getDocumentLanguage();
		}

		$oTemplateType = new Ext_Thebing_Pdf_Template_Type($oTemplate->template_type_id);

		if($oTemplate->use_smarty) {

			$oReplace = $this->getPdfPlaceholderObject($oInquiryDocument->getLatestVersionOrNew());
			$oReplace->setDisplayLanguage($sLang);

		} else {

			if ($oEntity instanceof Ext_Thebing_Teacher) {
				$oReplace = new Ext_Thebing_Teacher_Placeholder($oEntity->id);
			} else {
				$aParams = array(
					'inquiry'		=> $oEntity,
					'contact'		=> $oEntity->getCustomer(),
					'school_format'	=> $iSchoolForFormat,
					'template_type'	=> $oTemplate->type,
					'options'		=> $aOptions,
				);

				$oReplace = $oEntity->createPlaceholderObject($aParams);
				$oReplace->setDocumentVersion($oInquiryDocument->getLatestVersionOrNew());
			}

			$oReplace->sTemplateLanguage = $sLang;
			$oReplace->bInitialReplace = true;

			// GUI-Objekt hinzufügen für das durchschleifen von Variablen
			if(
				$this->oGui instanceof Ext_Gui2 &&
				$this->oGui->getParent() instanceof Ext_Gui2
			) {
				$aSelectedIdsDecoded = (array)$this->oGui->getParent()->decodeId($aOptions['parent_id']);
				// Deprecated. Eigentlich reicht es das in das Platzhalterobjekt zu setzen
//				$this->oGui->getParent()->setOption('document_selected_ids_decoded', $aSelectedIdsDecoded);
				$oReplace->setOption('document_selected_ids_decoded', $aSelectedIdsDecoded);
				$oReplace->oGui = $this->oGui;
			}

			// Selektierte Adresse manuell einfügen in die Platzhalterklasse
			// Wird benötigt für die speziellen Dokumente-Platzhalter, die sich auf die Adresse beziehen
			$aSelectedAddress = Ext_Thebing_Document_Address::getValueOfAddressSelect($_VARS['save']['address_select']);
			$oReplace->setAdditionalData('document_address', $aSelectedAddress);

			// Unterkunftskommunikations PDFs
//			if(
//				$oTemplate->type == 'document_accommodation_communication' &&
//				count($aSelectedIds) == 1
//			){
//				$iAllocationId = (int) reset($aSelectedIds);
//				$oAccommodationAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($iAllocationId);
//				$oReplace->_oAllocation = $oAccommodationAllocation;
//				$oReplace->_oJourneyAccommodation = $oAccommodationAllocation->getInquiryAccommodation();
//			}elseif(
//				$oTemplate->type == 'document_accommodation_communication' &&
//				count($aSelectedIds) == 1
//			){
//				$iInsuranceId = (int) reset($aSelectedIds);
//				$oInsurance = Ext_TS_Inquiry_Journey_Insurance::getInstance($iInsuranceId);
//				$oReplace->_oInsurance = $oInsurance;
//			}

		}

		$aTemp = array();
		$aTemp['id']							= $oTemplate->id;
		$aTemp['element_address']				= $oTemplateType->element_address;
		$aTemp['element_date']					= $oTemplateType->element_date;
		$aTemp['element_inquirypositions']		= $oTemplateType->element_inquirypositions;
		$aTemp['element_subject']				= $oTemplateType->element_subject;
		$aTemp['element_text1']					= $oTemplateType->element_text1;
		$aTemp['element_text2']					= $oTemplateType->element_text2;
		$aTemp['element_signature_text']		= $oTemplateType->element_signature_text;
		$aTemp['element_signature_img']			= $oTemplateType->element_signature_img;

		$aTemp['element_address_html']			= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'address'), 0);
		$aTemp['element_date_html']				= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'date'), 0);
		// Wochentag ermitteln
		$aTempData = array();
		$aTempData['school_id'] = $oSchool->id;

		$sDBDate = $oDateFormat->convert($aTemp['element_date_html'], $aTempData, $aTempData);

		$iWeekday = Ext_Thebing_Util::getWeekDay(2, $sDBDate, false);

		$aTemp['element_date_day']				= (int)$iWeekday;
		$aTemp['element_subject_html']			= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'subject'), 0);
		$aTemp['element_text1_html']			= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'text1'), 0);
		$aTemp['element_text2_html']			= $oReplace->replace($oTemplate->getStaticElementValue($sLang, 'text2'), 0);

		$iUserSig = $oTemplate->user_signature;

		if($iUserId<=0 && $iUserSig==1){
			$iUserId = (int)$user_data['id'];
		}

		if($iUserId > 0) {
			// wenn userspezifische signatur
			$aTemp['signatur_text_html']		= nl2br((string)Ext_Thebing_User_Data::getData($iUserId, 'signature_pdf_'.$sLang));
			$aTemp['signatur_img_html']			= Ext_Thebing_User_Data::getData($iUserId, 'signature_img_'.$iSchoolId);
		} else {
			$aTemp['signatur_img_html']			= $oTemplate->getOptionValue($sLang, $iSchoolId, 'signatur_img', false);
			$aTemp['signatur_text_html']		= (string)$oTemplate->getOptionValue($sLang, $iSchoolId, 'signatur_text');
		}
		$aTemp['user_signature']				= $iUserSig;
		$aTemp['signature_user_id']				= (int)$iUserId;

		// Wenn Rechnungspositionen angezeitgt werden, ansicht (agentur / kunde) übermitteln
		$oVersion = $oInquiryDocument->getLastVersion();
		if(!$oVersion instanceof Ext_Thebing_Inquiry_Document_Version){
			$oVersion = $oInquiryDocument->newVersion();
		}

		if($oVersion->id > 0){
			$aTemp['inquirypositions_view'] = (int)$oVersion->canShowInquiryPositions();
		}else{
			$aTemp['inquirypositions_view'] = (int)$oVersion->canShowInquiryPositions($oTemplate,$oInquiryDocument);
		}

		if($oTemplate->use_smarty) {
			$aErrors = $oReplace->getErrors();
			if(!empty($aErrors)) {
				$aTemp['error'][] = Ext_Thebing_Document_Gui2::convertPlaceholderErrors($aErrors);
			}
		}

		return $aTemp;
	}

	/**
	 * @param $oTemplate
	 * @param bool $sLang
	 * @return array
	 */
	public function getTemplateDataPreview(Ext_Thebing_Pdf_Template $oTemplate, $userId, $sLang=false){
		global $user_data;
		
		$oDateFormat = new Ext_Thebing_Gui2_Format_Date();

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId = $oSchool->id;
		if(!$sLang){
			$sLang = $oSchool->getLanguage();
		}

		$this->oSchool = $oSchool;
		
		$oTemplateType = new Ext_Thebing_Pdf_Template_Type($oTemplate->template_type_id);

		$aTemp = array();
		$aTemp['id']							= $oTemplate->id;
		$aTemp['element_address']				= $oTemplateType->element_address;
		$aTemp['element_date']					= $oTemplateType->element_date;
		$aTemp['element_inquirypositions']		= $oTemplateType->element_inquirypositions;
		$aTemp['element_subject']				= $oTemplateType->element_subject;
		$aTemp['element_text1']					= $oTemplateType->element_text1;
		$aTemp['element_text2']					= $oTemplateType->element_text2;
		$aTemp['element_signature_text']		= $oTemplateType->element_signature_text;
		$aTemp['element_signature_img']			= $oTemplateType->element_signature_img;

		$aTemp['element_address_html']			= $oTemplate->getStaticElementValue($sLang, 'address');
		$aTemp['element_date_html']				= $oTemplate->getStaticElementValue($sLang, 'date');

		// Wochentag ermitteln
		$aTempData = array();
		$aTempData['school_id'] = $oSchool->id;

		$sDBDate = $oDateFormat->convert($aTemp['element_date_html'], $aTempData, $aTempData);
		$iWeekday = Ext_Thebing_Util::getWeekDay(2, $sDBDate, false);

		$aTemp['element_date_day']				= (int)$iWeekday;
		$aTemp['element_subject_html']			= $oTemplate->getStaticElementValue($sLang, 'subject');
		$aTemp['element_text1_html']			= $oTemplate->getStaticElementValue($sLang, 'text1');
		$aTemp['element_text2_html']			= $oTemplate->getStaticElementValue($sLang, 'text2');

		$iUserSig = $oTemplate->user_signature;

		if (
			$userId <= 0 &&
			$iUserSig==1
		){
			$userId = (int)$user_data['id'];
		}

		$aTemp['user_signature']				= $iUserSig;
		$aTemp['signature_user_id']				= (int)$userId;
		if($userId > 0) {
			// wenn userspezifische signatur
			$aTemp['signatur_text_html']		= nl2br((string)Ext_Thebing_User_Data::getData($userId, 'signature_pdf_'.$sLang));
			$aTemp['signatur_img_html']			= Ext_Thebing_User_Data::getData($userId, 'signature_img_'.$iSchoolId);
		} else {
			$aTemp['signatur_img_html']			= $oTemplate->getOptionValue($sLang, $iSchoolId, 'signatur_img', false);
			$aTemp['signatur_text_html']		= (string)$oTemplate->getOptionValue($sLang, $iSchoolId, 'signatur_text');
		}

		// keine Rechnungspositionen anzeigen f�r Massendokumente
		$aTemp['inquirypositions_view'] = 0;

		return $aTemp;
	}

	/**
	 * Save data from edit dialog
	 *
	 * @param array $aSelectedIds
	 * @param array $_VARS
	 * @param bool $bUniqueInquiriesDocuments
	 * @return mixed
	 */
	public function saveDialogData($aSelectedIds, $_VARS, $bUniqueInquiriesDocuments = true) {
		global $user_data;

		$oGui = $this->oGui;
		$oGuiData = $this->oGui->getDataObject(); /** @var Ext_Thebing_Document_Gui2 $oGuiData */
		$oLogger = Log::getLogger();

		// Sicherheitsabfrage, da es Toto aus unersichtlichem Grund auf mysteriöse Weise geschafft hat mit
		// einem "Klick" 2 Rechnungen zu generieren mit unterschiedlichen Nummern?!
		if(!self::$bDocumentSaveCheck) {
			// TODO: Fehler werfen
			return;
		} else {
			// ... wird am Ende wieder resettet
			self::$bDocumentSaveCheck = false;
		}

		$sAction						= $_VARS['type'];
		$aErrors						= array();
		$iErrorCount					= 0;
		$bVersionSuccess				= true; // Fehler beim speichern einer Version
		$bDocumentSuccess				= true; // Fehler beim Dokument speichern
		$bPriceIndexSuccess				= true; // Fehler beim speichern des Version Indexes
		$iIsCredit						= 0;
		$iPartialInvoice = 0;
		#$iParentDocumentId				= 0;

		$oDateFormat		= new Ext_Thebing_Gui2_Format_Date();
				
		$aIds = (array)$_VARS['id'];
		
		$iParentDocumentId = (int)reset($aIds);

		if($_VARS['is_credit']) {
			$iIsCredit = 1;
		}

		$sLanguage = '';
		if(!empty($_VARS['save']['language'])){
			$sLanguage = $_VARS['save']['language'];
		}

		// IDs
		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = reset($aSelectedIds);
		$iDocumentId = $_VARS['document_id'];

		// Achtung! Muss mittlerweile nicht immer zwangsläufig eine Buchung sein
		// TODO Dieses ganze ID-Guessing sollte entfernt werden
		if(is_array($_VARS['parent_gui_id'])) {
			$iInquiryId	= reset($_VARS['parent_gui_id']);
		} else {
			$iInquiryId	= false;
		}

		$sDocumentType = (string)$_VARS['document_type'];

		$iTemplateId = (int)$_VARS['save']['template_id'];
		if(empty($iTemplateId)){
			$iTemplateId = (int)$_VARS['save']['template_hidden_id'];
		}
		$oTemplate = new Ext_Thebing_Pdf_Template($iTemplateId);

		// Teilrechnung
		if($_VARS['save']['partial_invoice']) {
			$iPartialInvoice = 1;
		}

		if($sDocumentType === 'brutto_diff_partial') {
			$sDocumentType = 'brutto_diff';
			// Checkbox nicht aktiviert, dann wird alles abgerechnet und es ist trotzdem eine Teilzahlung
			$iPartialInvoice = 1;
		}

		// Parent-GUI generell setzen
		if($oGui instanceof Ext_Gui2) {
			$oParentGui = $oGui->getParentClass();
		}

		if(
			$iInquiryId === false &&
			count($aSelectedIds) > 0
		) {
			$bIsMultiple = true;
			$aInquiryIds = $aSelectedIds;
			$aSelectedIdsDecoded = $aSelectedIds;

			$bDecodedAndFound = null;

			if(is_object($oGui)){
				$sInquiryIdField = $oGui->getOption('decode_inquiry_id_additional_documents');
				if(!empty($sInquiryIdField)) {
					$bDecodedAndFound = false;
					$aSelectedIdsDecoded = $oGui->decodeId($aInquiryIds);
					$aInquiryIds = $oGui->decodeId($aInquiryIds, $sInquiryIdField);
					if(!empty($aInquiryIds)) {
						$bDecodedAndFound = true;
					}
				}
			}

			/*// Liste mit Entities bei denen $aInquiryIds sicher IDs von Ext_TS_Inquiry enthält
			$aEntityTypeWhitelist = [
				'Ext_Thebing_Inquiry_Certificates', // #9816
			];

			// Überprüfung, ob der Inhalt von $aInquiryIds auch wirklich IDs von Ext_TS_Inquiry enthält #9273
			if(
				!empty($aInquiryIds) && 
				!in_array($oGui->class_wdbasic, $aEntityTypeWhitelist) && (
					(
						// Wenn GUI von der Inquiry abstammt, darf entweder nicht dekodiert worden sein oder es musste dekodiert werden
						$oGui->class_wdbasic === $oTemplate->getObjectClassFromType() &&
						$bDecodedAndFound === false
					) || (
						// Wenn GUI nicht von der Inquiry abstammt, muss die ID dekodiert worden sein
						$oGui->class_wdbasic !== $oTemplate->getObjectClassFromType() &&
						$bDecodedAndFound !== true
					)
				)

			) {
				throw new RuntimeException('Selected ID (or decoded ID) doesn\'t belong to inquiry');
			}*/

			// doppelte Inquiry-IDs rauswerfen (falls gewünscht)...bei der Schülerliste für Versicherungen darf das
			// zum Beispiel nicht passieren, da ein Schüler mehrere Versicherungen haben kann; es soll aber für jede Versicherung
			// der Buchung ein Dokument erzeugt werden
			if($bUniqueInquiriesDocuments) {
				$aInquiryIds = array_unique($aInquiryIds);
			}

		} else {
			$bIsMultiple = false;
			if(isset($oParentGui)) {
				$sInquiryIdField = $oParentGui->getOption('decode_inquiry_id_additional_documents');
				if(!empty($sInquiryIdField)){
					$aSelectedIdsDecoded = $oParentGui->decodeId($iInquiryId);
					$iInquiryId = $oParentGui->decodeId($iInquiryId, $sInquiryIdField);
				}
			}
			$aInquiryIds	= (array)$iInquiryId;
		}

		if($sDocumentType == "") {
			$sDocumentType = "brutto";
		}

		if($sAction == 'additional_document' || $sAction == 'job_opportunity') {
			$sDocumentType = 'additional_document';
		}

		$bEdit = false;
		if($iDocumentId > 0) {
			$bEdit = true;
		}

//		if($sAction == 'offer') {
//			$aInquiryIds = (array)$_VARS['id'];
//		}
//
//		$iInquiryId = reset($aInquiryIds);
//
//		/* @var $oInquiry Ext_TS_Inquiry_Abstract|Ext_TS_Inquiry */
//		$oInquiry = $oTemplate->getObjectFromType($iInquiryId);

		/* @var \Ts\Interfaces\Entity\DocumentRelation $oEntity */
		$oEntity = $oGuiData->getSelectedObject();

		// Wenn globale Teilzahlungsbedingung gesetzt wurde, muss das für jedes Dokument aktiviert werden
		if(
			$oEntity instanceof Ext_TS_Inquiry && 
			$oEntity->partial_invoices_terms > 0 &&
			(
				$sDocumentType === 'invoice' ||
				$sDocumentType === 'brutto_diff_partial'
			)
		) {
			$iPartialInvoice = 1;
		}

		$sNumberrangeType = $oEntity->getTypeForNumberrange($sDocumentType, $oTemplate->type);

		$oNumberrange = null;
		$bNumberIsRequired = Ext_Thebing_Inquiry_Document::isNumberRequiredForType($sDocumentType, $sNumberrangeType);

		if(!empty($_VARS['save']['numberrange_id'])) {
			// Nummernkreis-ID wurde übermittelt: Diesen Nummernkreis benutzen
			$iNumberrangeId = (int)$_VARS['save']['numberrange_id'];
			$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($iNumberrangeId);
		} else {

			// Wenn Dokumenttyp eine Nummer braucht, dann den Nummernkreis suchen
			if($bNumberIsRequired) {

				if($oEntity instanceof Ext_TS_Inquiry) {
					$oInbox = $oEntity->getInbox();
					Ext_TS_NumberRange::setInbox($oInbox);
				}

				$oSchool = $oEntity->getSchool();
				$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($oEntity->getTypeForNumberrange($sDocumentType, $oTemplate->type), (bool)$iIsCredit, $oSchool->id);
			} else {
				// TODO Allocations hängen direkt am Dokumenttyp, Zusatzdokumente werden aber mit dem Templatetyp unterschieden
				// Das sollte irgendwann mal von global auf einstellbar geändert werden
				if($oTemplate->type === 'document_accommodation_communication') {
					// TODO Dieser Mist mit GUI-Abhängigkeiten wird immer schlimmer
					if(!empty($aSelectedIdsDecoded['allocation_id'])) {
						$oAllocation = Ext_Thebing_Accommodation_Allocation::getInstance($aSelectedIdsDecoded['allocation_id']);
						$oProvider = $oAllocation->getAccommodationProvider();
						if(
							$oProvider instanceof Ext_Thebing_Accommodation &&
							$oProvider->getNumber()
						) {
							$oNumberrange = \Ext_Thebing_Accommodation::getDocumentNumberrangeObject();
							if($oNumberrange !== null) {
								$oNumberrange->setDependencyEntity($oProvider);
							}
						}
					}
				} elseif($oTemplate->type === 'document_studentrecord_visum_pdf') {
					$oNumberrange = Ext_Thebing_Visum::getNumberrangeObject($oTemplate);
				}
			}

		}

		// Nummernkreis sperren, wenn vorhanden
		if(
			$oNumberrange instanceof Ext_TS_NumberRange &&
			!$oNumberrange->acquireLock()
		) {
			// Wenn Nummernkreis nicht gesperrt werden kann: Abbruch
			return $this->generateSaveDialogDataErrorMessage(self::getNumberLockedError(), $aInquiryIds);
		}

		// Transaktion starten
		$bTransactionBegin = DB::begin('save_inquiry_document');

		$oLogger->addInfo('Document transaction begin for inquiry '.join(', ', $aInquiryIds), array(
			'inquiries' => $aInquiryIds,
			'vars' => $_VARS,
			'user_id' => $user_data['id'],
			'transaction_start' => $bTransactionBegin
		));

		$oPurifier = new Ext_TC_Purifier();

		// Adress-Select
		$aSelectedAddress = Ext_Thebing_Document_Address::getValueOfAddressSelect($_VARS['save']['address_select']);

		$oPdfAll = new Ext_Thebing_Pdf_Basic($oTemplate->id);
		$oPdfAll->setAllowSave(false); 
		if($sLanguage) {
			$oPdfAll->setLanguage($sLanguage);
		}
		
		// Erste erstellte Version
		$oFirstVersion = null;

		// Keine Ahnung, was hier genau reinkommt, aber Mehrfachauswahl sollte (aktuell?) auch nicht möglich sein
		if ($sAction === 'offer' && count($aInquiryIds) > 1) {
			throw new LogicException('More than one id for offer type.');
		}

		/**
		 * Buchungen
		 * Nur relevant, bzw. mehrere vorhanden, wenn man ein Mehrfach-PDF für mehrere Buchungen auf einmal erstellt.
		 * Ansonsten ist hier immer nur eine Buchung
		 *
		 * Achtung! $iInquiryIndex ist wichtig für $aSelectedIdsData
		 */
		foreach((array)$aInquiryIds as $iInquiryIndex => $iInquiryId) {

//			/* @var $oInquiry Ext_TS_Inquiry_Abstract */
//			$oInquiry						= $oTemplate->getObjectFromType($iInquiryId);

			/* @var \Ts\Interfaces\Entity\DocumentRelation $oInquiry */
			$oInquiry = $oGuiData->getSelectedObject($iInquiryId);

			$oSchool = $oInquiry->getSchool();

			if (!$oInquiry->exist()) {
				throw new LogicException('Inquiry does not exist.');
			}

			$iSchoolId						= $oSchool->id;
			$this->oSchool					= $oSchool;

			if($iDocumentId > 0) {
				$oDocument = new Ext_Thebing_Inquiry_Document($iDocumentId);
				$iPartialInvoice = (int)$oDocument->partial_invoice; // Bei edit ist Typ brutto_diff und die Abfrage oben funktioniert nicht mehr
			} else {
				$oDocument = $oInquiry->newDocument($sDocumentType, false);
				if ($sAction === 'offer') {
					// Journey wird in getSelectedObject() gesetzt
					$oDocument->entity = Ext_TS_Inquiry_Journey::class;
					$oDocument->entity_id = $oInquiry->getJourney()->id;
				} else if ($sAction === 'job_opportunity') {
					$oDocument->entity = \TsCompany\Entity\JobOpportunity\StudentAllocation::class;
					$oDocument->entity_id = $iInquiryId; // parent_gui_id
				}
			}

//			// Dekodierte selektierte Originalwerte setzen #5772
//			// Dies ist wichtig für Unterkunfsplatzhalter, damit bei mehreren Einträgen oder Massendokumenten die richtige Unterkunft gefunden wird
//			if(isset($aSelectedIdsDecoded[$iInquiryIndex])) {
//				$oGui->setOption('document_selected_ids_decoded', $aSelectedIdsDecoded[$iInquiryIndex]);
//			}

			// Datum formatieren
			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();

			// Da die Variable z.B. bei Gruppen in der Schleife überschrieben wird
			$oOriginalDocument				= $oDocument;
			
			$sDisplayLanguage				= $sLanguage;
			if(empty($sLanguage)) {
				$sDisplayLanguage = $oInquiry->getDocumentLanguage();
			}

			// Rechnungspositionen aus Instanz auslesen
			// Wenn dies nicht bei entsprechenden Dokumenten passiert, gibt es keinen Preisindex und der Betrag steht auf 0!
			// Und auch Additional Documents können Positionen haben!
			// #5116, #5159, #5192
			$aItems = $oGui->getDocumentPositions();

			$bInvoiceType = in_array($oDocument->type, Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnote'));

			if (
				$oInquiry instanceof \Ext_TS_Inquiry &&
				$bInvoiceType &&
				$iParentDocumentId > 0
			) {
				$fOpenAmountOriginal = $oInquiry->getOpenPaymentAmount();
			}

			// Ticket #5017
			if(
				$bInvoiceType &&
				empty($aItems)
			) {
				$sMessage = L10N::t('Für diese Rechnung konnten keine Positionen erkannt werden. Bitte überprüfen Sie die Einstellungen.', self::$sL10NDescription);
				return $this->generateSaveDialogDataErrorMessage($sMessage, $aInquiryIds);
			}

			/**
			 * Rechnungspositionen auf Buchungen verteilen
			 */
			$aInquiryItems = array();
			foreach($aItems as $aSubItems) {

				foreach($aSubItems as $aSubItem) {

					// Offer hat nur ein Dokument und die Inquiry-IDs pro Position sind 0 (geht über Kontakte)
					if ($sAction === 'offer') {
						$aSubItem['inquiry_id'] = $oInquiry->id;
					}

					// @TODO Woher soll $iCount kommen?
					$aSubItem['count'] = $iCount;
					$aInquiryItems[$aSubItem['inquiry_id']][] = $aSubItem;
				}
			}

			/**
			 * @todo Gruppen und Normal gleich behandeln? Ich sehe hier Redundanzen
			 * 
			 * Keine Gruppenbuchungen außer bei additional_document
			 */
			if(
				($oInquiry instanceof \Ext_TS_Inquiry && !$oInquiry->hasGroup()) ||
				$sDocumentType == 'additional_document' ||
				$sAction == 'offer'
			) { // EINZELDOKUMENTE

				$aItems = $aInquiryItems[$oInquiry->id];

				// Wenn gutschrift, dann erstelle eine Negierung + eine neue Rechnung
				if(
					strpos($sDocumentType, 'credit') !== false &&
					strpos($sDocumentType, 'creditnote') === false &&
					$oDocument->type != 'credit_brutto' && // nicht beim editieren
					$oDocument->type != 'credit_netto' // nicht beim editieren
				) {
					throw new RuntimeException('Type credit has been removed');

//					$sTypeCredit = 'credit_brutto';
//					if(strpos($oDocument->type, 'netto') !== false){
//						$sTypeCredit = 'credit_netto';
//					}
//					$sTypeNewDoc = 'brutto';
//					if(strpos($sDocumentType, 'netto') !== false){
//						$sTypeNewDoc = 'netto';
//					}
//
//					$oDocument = $oDocument->cloneDocument($sTypeCredit, L10N::t('Create Credit and new Invoice', $oGui->gui_description));
//					$oDocument->log(Ext_Thebing_Log::DOCUMENT_CREATE_CREDIT_AND_INVOICE);
//					$oDocument = $oInquiry->newDocument($sTypeNewDoc);
//
//					$sDocumentType = $sTypeNewDoc;
//					$iDocumentId = 0;
				}

				// Document Data
				#$oDocument->inquiry_id			= $iInquiryId;
				#$oDocument->active				= 1;
				$oDocument->editor_id = $user_data['id'];
				#$oDocument->type				= $sDocumentType;
				$oDocument->is_credit			= $iIsCredit;
				$oDocument->partial_invoice = $iPartialInvoice;

				// GUI bestimmen für GUI2-Zuweisung
				if($bIsMultiple) {
					// Bei Mehrfachauswahl ist $oGui = $oParentGui, da die Dokument-GUI fehlt!
					$oTmpGui = $oGui;
				} elseif(
					isset($oParentGui) &&
					$oParentGui instanceof Ext_Gui2
				) {
					$oTmpGui = $oParentGui;
				}

				// GUI2-Zuweisung setzen
				if(
					isset($oTmpGui) &&
					$oTmpGui instanceof Ext_Gui2
				) {

					// Da nur die GUI-Configs standardmäßig einen Namen haben, muss das hier sukzessive korrigiert werden…
					$sTmpGuiName = $oTmpGui->name;
					if(empty($sTmpGuiName)) {
						throw new RuntimeException('Parent-Document-GUI has no name');
					}

					$aTmp = $oDocument->gui2;
					if(empty($aTmp)) {
						$oDocument->gui2 = array(array(
							'name' => $oTmpGui->name,
							'set' => $oTmpGui->set
						));
					}
				}

				$mDocumentCheck  = $oDocument->validate();

				if($mDocumentCheck === true) {

					// Dokumentverknüpfung setzen
					// Muss so früh wie möglich passieren, damit auch Platzhalter funktionieren
					if($oDocument->getRelationParentKey() !== null) {
						if(empty($iParentDocumentId)) {
							throw new LogicException('No $iParentDocumentId for needed document relation');
						}

						$oDocument->saveParentRelation($iParentDocumentId, false);
					}

					// Nummer generieren, wenn Nummernkreis vorhanden und Dokument (bisher) keine Nummer hat
					if(
						($oDocument->document_number === '' || $oDocument->document_number === null) &&
						$oNumberrange instanceof Ext_TS_NumberRange &&
						$oNumberrange->id > 0 &&
						// Keine Nummergenerierung bei Entwürfen
						!$oDocument->originalIsDraft()
					) {
						$oDocument->document_number = $oNumberrange->generateNumber();
						$oDocument->numberrange_id = $oNumberrange->id;
					}

					try {
						$oDocument->save();
					} catch (RuntimeException $e) {
						if($e->getMessage() === 'No numberrange!') {
							$sMessage = L10N::t('Es konnte kein Nummernkreis gefunden werden!', self::$sL10NDescription);
							return $this->generateSaveDialogDataErrorMessage($sMessage, $aInquiryIds);
						}
						throw $e;
					}

					// Kundennummer bei Rechnungstypen generieren, wenn noch keine Nummer vorhanden ist
					// Sollte das fehlschlagen, weil der Nummernkreis gesperrt ist, ist das nicht so wichtig…
					if(
						$bNumberIsRequired &&
						$oInquiry instanceof Ext_TS_Inquiry
					) {
						$oCustomerNumber = new Ext_Thebing_Customer_CustomerNumber($oInquiry);
						$oCustomerNumber->saveCustomerNumber();
					}

					// Wenn wir das hier nicht in die Instanz packen, dann klappt später in der createPdf() Funktion
					// die Verbindung zwischen $oDocument <> getLastVersion nicht (bei einem neuen Dokument)
					$oDocument = Ext_Thebing_Inquiry_Document::getInstance($oDocument->id);

					if($oDocument->id <= 0) {
						throw new RuntimeException('Document couldn\'t be created!');
					}
					
					if($bIsMultiple) {
						
//						if($oTemplate->type == 'document_student_requests') {
//							$oReplace = new Ext_TS_Enquiry_Placeholder($oInquiry);
//						} else {
							if ($oInquiry instanceof \Ext_Thebing_Teacher) {
								$oReplace = new Ext_Thebing_Teacher_Placeholder($oInquiry->id);
							} else {
								$oReplace = new Ext_Thebing_Inquiry_Placeholder($oInquiry->id, $oInquiry->getCustomer()->id);
							}


//						}

						// Das Placeholder-Objekt braucht die GUI um auf kodierte IDs zugreifen zu können
						if($oGui instanceof Ext_Gui2) {
							$oReplace->oGui = $oGui;
						}

						if(isset($aSelectedIdsDecoded[$iInquiryIndex])) {
							$oReplace->setOption('document_selected_ids_decoded', $aSelectedIdsDecoded[$iInquiryIndex]);
						}

						// Selektierte Adresse manuell einfügen in die Platzhalterklasse
						// #16002: Das war vorher unter den replace-Aufrufen, aber das kann ja dann nicht mit den document_-Platzhaltern funktionieren
						$oReplace->setAdditionalData('document_address', $aSelectedAddress);

						$oReplace->sTemplateLanguage = $sDisplayLanguage;
						$sDate = $oReplace->replace($_VARS['save']['date'], 0);
						$sAddress = $oReplace->replace($_VARS['save']['address'], 0);
						$sSubject = $oReplace->replace($_VARS['save']['subject'], 0);
						$sIntro = $oReplace->replace($_VARS['save']['intro'], 0);
						$sOutro = $oReplace->replace($_VARS['save']['outro'], 0);

					} else {
						$sDate = $_VARS['save']['date'];
						$sAddress = $_VARS['save']['address'];
						$sSubject = $_VARS['save']['subject'];
						$sIntro = $_VARS['save']['intro'];
						$sOutro = $_VARS['save']['outro'];
					}

					$oOldVersion	= $oDocument->getLastVersion();
					
					// Wenn das Rechnungsdatum nicht mitgeschickt wird, MUSS das der letzten Version genommen werden
					// da es nicht veränderbar sein darf (Steuern)
					//$sDate = $_VARS['save']['date'];
					if(
						empty($sDate) &&
						is_object($oOldVersion)
					){
						$sDate = $oOldVersion->date;
					} elseif(
						!empty($sDate)
					){
						$sDate = $oDateFormat->convert($sDate);
					}

					if (
						!\Core\Helper\DateTime::isDate($sDate, 'Y-m-d')
					) {
						$sDate = date('Y-m-d');
					}


					$oVersion						= $oDocument->newVersion();
					$oVersion->template_id			= $oTemplate->id;
					$oVersion->date					= $sDate;
					$oVersion->txt_address			= $sAddress;
					$oVersion->txt_subject			= $sSubject;
					$oVersion->txt_intro			= $oPurifier->purify($sIntro);
					$oVersion->txt_outro			= $oPurifier->purify($sOutro);
					$oVersion->txt_pdf				= $oTemplate->getOptionValue($sDisplayLanguage, $iSchoolId, 'first_page_pdf_template');
					$oVersion->signature_user_id	= $_VARS['save']['signature_user_id'];
					$oVersion->txt_signature		= $_VARS['save']['signature_txt'];
					$oVersion->signature			= $_VARS['save']['signature_img'];
					$oVersion->comment				= (string)$_VARS['save']['comment'];
					$oVersion->template_language	= $sDisplayLanguage;
					$oVersion->tax					= $oSchool->tax;
					$oVersion->payment_condition_id = $_VARS['save']['payment_condition_select'];
					$oVersion->addresses 			= $aSelectedAddress;
					$oVersion->company_id = $_VARS['save']['company_id'] ?? null;

					Ext_TS_Document_PaymentCondition::convertRequestPaymentTerms($this->oGui->getRequest(), $oVersion);

					if(
						$oTemplate->canShowInquiryPositions() &&
						!empty($_VARS['save']['invoice_select'])
					) {
						// Nur speichern, wenn das Template auch Positionen hat
						$oVersion->invoice_select_id = (int)$_VARS['save']['invoice_select'];
					}

					if($oVersion->document_id <= 0) {
						throw new RuntimeException('Version couldn\'t be created!');
					}

					$mVersionCheck = $oVersion->validate();

					if($mVersionCheck !== true){

						$bVersionSuccess = false;

						if(is_array($mVersionCheck)){
							$aErrors = array_merge($aErrors,$mVersionCheck);
						}
					}

					if($bVersionSuccess){
						// Nur weiter speichern wenn es keinen Fehler gibt!

						$oVersion->save();
						
						// Aktuelles Version-Objekt in den Instanz-Cache packen
						Ext_Thebing_Inquiry_Document_Version::setInstance($oVersion);

						// Inquiry Caching befüllen
//						if($oInquiry instanceof Ext_TS_Inquiry){
//							$oInquiry->savePrepayCache($oVersion);
//						}
				
						## Start Editierbare Layoutfelder speichern (wie immer redundant)
						foreach((array)$_VARS['save'] as $iKey => $sData){
							if(
								strpos($iKey, 'editable_html_field') !== false ||
								strpos($iKey, 'editable_date_field') !== false ||
								strpos($iKey, 'editable_text_field') !== false
							){
								// Block id herausfinden
								$aBlockData = explode('_', $iKey);

								$iBlockId = (int)$aBlockData[3];
								// Wenn $iBlockId == 0 dann ist es der "Fake-block" der geklont wird
								if($iBlockId > 0){
									$oField = $oVersion->getNewLayoutField();
									$oField->block_id = $iBlockId;
									$oField->content = $sData;
									$oField->save();
								}
							}
						}
						## ENDE

						if(!empty($aItems)){
							ksort($aItems);
						}

						$iPosition = 1;

						$aItemObjects = array();
						$bItemsSuccess = true;

						$iCanShowInquiryPositions = (int)$oVersion->canShowInquiryPositions();
						if($iCanShowInquiryPositions<=0){
							$aItems = array();
						}

						foreach((array)$aItems as $aItem) {

							if($aItem['position'] > 0) {
								$iPosition = $aItem['position'];
							}

							$oItem = $oVersion->newItem();

							if($aItem['description'] == '') {
								continue;
							}

							if($oVersion->id <= 0) {
								throw new RuntimeException('Version couldn\'t be created!');
							}

							$oItem->iOldItemId				= (int)$aItem['position_id'];
							$oItem->sOldItemStatus			= (string)$aItem['status'];

							$this->setItemValues($oItem, $aItem);
							
							$oItem->version_id				= (int)$oVersion->id;
							$oItem->calculate				= (int)$oItem->onPdf;
							$oItem->position				= (int)$iPosition;
							$oItem->active					= 1;
							$oItem->count 					= (int)$aItem['count'];
					
                            // Wenn index_from angeben ist dann bassierte diese Item auf einem anderem ITEM ( z.b bei diff)
                            // dann muss dieser wert genommen werden
                            // ['from'] enthalt wenn angeben immer das from der leistung wie sie jetzt aktuell ist
                            if(!empty($aItem['index_from'])) {
								$oItem->index_from = $aItem['index_from'];
							} else if(!empty($aItem['from'])) {
								$oItem->index_from = $aItem['from'];
							}
							
                            // Wenn index_until angeben ist dann bassierte diese Item auf einem anderem ITEM ( z.b bei diff)
                            // dann muss dieser wert genommen werden
                            // ['until'] enthalt wenn angeben immer das until der leistung wie sie jetzt aktuell ist
							if(!empty($aItem['index_until'])) {
								$oItem->index_until = $aItem['index_until'];
							} else if(!empty($aItem['until'])) {
								$oItem->index_until = $aItem['until'];
							}

							// Steuersatz ausrechnen
							$fTaxRate = 0;
							if($aItem['tax_category'] > 0) {
								$dVatDate = Ext_TS_Vat::getVATReferenceDateByDate($oSchool, new Carbon\Carbon($oItem->index_from), new Carbon\Carbon($oItem->index_until));
								$fTaxRate = Ext_TS_Vat::getTaxRate($aItem['tax_category'], $oSchool->id, $dVatDate->toDateString());
							}
							$oItem->tax = $fTaxRate;
		
							$aItemObjects[] = $oItem;

							$oItem->updateItemCache();
							$mItemCheck = $oItem->validate();
		
							if($mItemCheck !== true) {
				
								$bItemsSuccess = false;

								if(is_array($mItemCheck)){
									$aErrors = array_merge($aErrors,$mItemCheck);
								}

							}

							$iPosition++;
						}

						System::wd()->executeHook('ts_inquiry_document_modify_items', $aItemObjects, $oDocument);

						// Speichern wenn ALLES OK ist
						if($bItemsSuccess){

							$aAccountingErrorCache		= array();
							$aItemObjectsSaved			= array();
							$oPriceIndex				= new Ext_Thebing_Inquiry_Document_Version_Price();
							$aItemIdAllocations			= array();
							
							foreach((array)$aItemObjects as $oItem){

								$oItem->save();
								
								if($oItem->iOldItemId > 0) {
									$aItemIdAllocations[$oItem->iOldItemId] = $oItem->id;
								}
								
								// Wenn NEU und Nötig, dann Buchhaltung abspeichern!
								/*if(
									$oInquiry instanceof Ext_TS_Inquiry &&
									$oSchool->invoice_booking == 1 &&
									$oItem->onPdf == 1 &&
									(
										$oDocument->checkDocumentType('invoice_without_proforma') == true ||
										(
											$oDocument->type == 'creditnote' &&
											$oSchool->accounting_type == 0
										)
									)
								){

									// Wenn refresh und rechnungsfreigbae aktiv => löschen sonst umschreiben
									if($_VARS['is_refesh'] == 1 && $oSchool->invoice_release == 1){
										$oDocument->removeRelease();
									}

								}*/

								// Merken das das Item angefangen wurde
								// 
								// $aItemObjects wird in dem fall immer das gleiche sein wie $aItemObjectsSaved,
								// wie wie oft soll dieser scheiß noch in ein array rein :)
								$aItemObjectsSaved[] = $oItem;

								// hier werden die Payment daten angeglichen da wir nun neue Item IDs haben da wir versionen anlegen
								// auserdem müssen weggefallene Pos. ebenfallss als "Überbezahlung" gehandhabt werden
								// und NUR bei bestehenden Rechnungen!
								if(
									$oItem->iOldItemId > 0 &&
									$iDocumentId > 0
								) {
									$oItemOld = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($oItem->iOldItemId);
									$oItemOld->refreshPaymentData($oItem);
								}

								//Price Index aufbauen
								$oPriceIndex->addItem($oItem);

							}

							// Nachdem alle Items gespeichert worden sind, diese NOCHMALS durchgehen, da Items vom parent_typ = 'item_id'
							// Hier die passende ID bekommen müssen
							// TODO Hier wird jedes Special-Item doppelt gespeichert und das sollte man bereits in der oberen Schleife lösen können
							$oVersion->updateItemIds($aItemIdAllocations);

							// Nach updateItemIds() Items nochmal durchlaufen, da parent_id benötigt wird
							// TODO Kann man das nicht auf $aItemObjects umstellen?
							$aTmpItems = $oVersion->getItemObjects();
							foreach($aTmpItems as $oItem) {

								// Bei Specials entsprechende Verwendung markieren
								if($oItem->type == 'special') {

									if(empty($oItem->parent_id)) {
										throw new RuntimeException(sprintf('Special item does not have parent_id after updateItemIds! (%d, "%s")', $oItem->id, $oItem->description));
									}

									Ext_Thebing_Inquiry_Special_Position::markPosition($oInquiry, $oItem);
								}
							}
							
							// Special-Index
							// TODO Und ein dritter Speicher-Durchlauf: Index-Spalten für Specials füllen
							$oVersion->updateSpecialIndexFields();

							// Price Index speichern
							$mPriceIndexCheck = $oPriceIndex->savePrice($oVersion->id);

							if(is_array($mPriceIndexCheck)){
								$bPriceIndexSuccess = false;
								$aErrors[] = L10N::t('Preisindex konnte nicht erstellt werden.', 'Thebing » Errors');
							}

							$aValidatePrepayAmounts = $oVersion->validatePaymentTermsAmount();

							// Rundungsdifferenz versuchen zu korrigieren (kann bei Ratenzahlungen auftreten)
							if(!empty($aValidatePrepayAmounts)) {
								$oVersion->handlePaymentTermsRoundingDifference();
								$aValidatePrepayAmounts = $oVersion->validatePaymentTermsAmount();
							}

							if(!empty($aValidatePrepayAmounts)) {
								$aErrors = array_merge($aErrors, $aValidatePrepayAmounts);
							}

							if(
								$bItemsSuccess && 
								$bPriceIndexSuccess &&
								is_object($oOldVersion)
							){
								/*
								 * Alle Payments der alten Version, die nicht 
								 * neu zugewiesen werden konnten müssen entfernt werden
								 */
								$aLastItems = $oOldVersion->getItemObjects(true);

								// Rest durchgehen und payments zu Überbezahlung umändern!
								foreach($aLastItems as $oLastItem) {
									$oLastItem->deletePaymentData();
								}
							}

							if(
								$bItemsSuccess === true &&
								$bPriceIndexSuccess === true
							) {

								$bPDFSuccess = true;

								// Die Information muss schon hier vorhanden sein für die PDF-Platzhalter! R-#4323
								// Dies hier findet dazu eh in einer Transaktion statt
								// Das wurde durch saveParentRelation an der korrekten Stelle ersetzt
//								if(strpos($sDocumentType, 'creditnote') !== false){
//									// wenn credit note, dann fülle zwischentabelle
//									DB::updateJoinData(
//										'ts_documents_to_documents',
//										array(
//											'parent_document_id'	=> $_VARS['documentFromCreditNote_id'],
//											'type'					=> 'creditnote',
//										),
//										array($oDocument->id),
//										'child_document_id'
//									);
//								}

								// PDF erzeugen
								try {
									$oDocument->createPdf(true, $sDisplayLanguage, null, $oGui, $bIsMultiple);
								} catch (PDF_Exception $e) {
									#$aErrors[$iErrorCount]['message'] = L10N::t('PDF konnte nicht erstellt werden! Bitte überprüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors').' ('.$e->getMessage().')';
									$aErrors['pdf'][] = L10N::t('PDF konnte nicht erstellt werden! Bitte überprüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors').' ('.$e->getMessage().')';
									$iErrorCount++;
									$bPDFSuccess = false;
								} catch (Exception $e) {
									if(System::d('debugmode') == 2) {
										throw $e;
									}

									Ext_Thebing_Util::reportError($e->getMessage());
									#$aErrors[$iErrorCount]['message'] = $e->getMessage();
									$aErrors['pdf'][] = $e->getMessage();
									$iErrorCount++;
									$bPDFSuccess = false;
								}

								if($bPDFSuccess){

									if($oInquiry instanceof Ext_TS_Inquiry) {

										$bHadProforma = $oInquiry->has_proforma;
										$bHadInvoice = $oInquiry->has_invoice;

										if(
											$oDocument->type != 'storno' &&
											$oDocument->type != 'additional_document' &&
											!$oDocument->isDraft()
										) {
											$oInquiry->setInquiryStatus($oDocument->type, false);
										}

										// Für die Aktualisierung der Student-ID-Card-Spalte
										if(
											$oDocument->type == 'additional_document' &&
											$oTemplate->type == 'document_student_cards'
										) {
											// Ganzes Object weil einzelnes Feld aktualisieren nicht innerhalb einer Transaktion geht
											\Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);
										}
										
										if($oDocument->type != 'additional_document') {
											// Beträge neu schreiben
											$fAmount = $oInquiry->getAmount(false, true, null, false);
											$oInquiry->getAmount(true, true, null, false);
											//$oInquiry->getCreditAmount(true, false);
											$oInquiry->save(); // Fünffaches Speichern verhindern
										}

										$fVersionAmount = $oVersion->getAmount();

										if($oDocument->type == 'storno'){
											// TODO Hier wird noch save() aufgerufen, aber das ist ein seltener Fall
											$oInquiry->confirmCancellation($fAmount);
										}

										// Wenn Rechnungsbetrag negativ und kleiner/gleich ist als offener Betrag der Buchung: Dokument mit Parent-Doc verrechnen
										if (
											System::d('ts_auto_document_payment_clearing', 1) &&
											!$bEdit &&
											isset($fOpenAmountOriginal) &&
											$fVersionAmount < 0 &&
											abs($fVersionAmount) <= $fOpenAmountOriginal
										) {
											$this->handleDocumentPaymentClearing($oInquiry, $oDocument, $oVersion, $aItemObjects);
										}

										// Bei erster Proforma oder erster Rechnung: Mögliche Zahlungen umschreiben
										if (
											!$bEdit &&
											(
												!$bHadInvoice &&
												!$bHadProforma &&
												in_array($sDocumentType, ['proforma_brutto', 'proforma_netto'])
											) || (
												!$bHadInvoice &&
												in_array($sDocumentType, ['brutto', 'netto'])
											) &&
											!$oDocument->isDraft()
										) {
											$this->reallocatePaymentAmounts($oInquiry, $aItemObjects);
										}

										// Bezahlbelege aktualisieren
										if ($oInquiry->type & Ext_TS_Inquiry::TYPE_BOOKING) {
											self::refreshPaymentReceipts($oInquiry, $oDocument);
										}

									}

									// Wenn es keine Diff ist
									// oder die diff NEU gespeichert wird ( nich bei Änderungen )
									if(
										(
											$sDocumentType == 'netto' || // Bei Rechnungsdokumenten immer Resetten
											$sDocumentType == 'brutto' ||
											$sDocumentType == 'proforma_brutto' ||
											$sDocumentType == 'proforma_netto' ||
											(
												strpos($sDocumentType, 'diff') !== false && // Bei der 1. Diff Rechnung resetten
												$iDocumentId <= 0
											)
										) &&
										$oDocument->is_credit == 0 
									){
										// veränderungen reseten da die Rechnung nun gespeichert wurde
										Ext_Thebing_Inquiry_Document_Version::clearChanges($iInquiryId, $oDocument->id);
									}elseif(
										strpos($sDocumentType, 'diff') !== false &&
										$iDocumentId > 0
									){
										// Bei aufeinanderfolgenden Diff rechnungen wird der change flag niemals gecleaned
										// um trotzdem die Inbox anzeige farblich richtig zu markieren setzen wir visible = 0
										Ext_Thebing_Inquiry_Document_Version::clearChanges($iInquiryId, $oDocument->id, true);
									}

									// Muss sofort aktualisert werden, da ansonsten PDF-Icon nicht direkt in der Liste auftaucht
									// Vor der Verlagerung der Registry ins PP hat das auch ohne funktioniert
									if(strpos($sDocumentType, 'creditnote') !== false) {
										Ext_Gui2_Index_Stack::add('ts_document', $iParentDocumentId, 0);
									}

									if($bIsMultiple){
										$oDoc = new Ext_Thebing_Pdf_Document();
										$oDoc->inquiry_id = $oInquiry->id;
										$oDoc->oDocument = $oDocument;
										$oDoc->oDocumentVersion = $oVersion;
										$oDoc->sPath = $oVersion->getPath();
										$oDoc->setGenerated(true);
										$oPdfAll->addDocument($oDoc);
									}

									$this->prepareAttachedAdditionalDocumentsGenerating($oDocument, $_VARS);
								}

							}
						}
					}

					$oDocument->initUpdateTransactions();
					
				} else {

					// Fehler beim Dokument speichern
					$bDocumentSuccess = false;

					if(is_array($mDocumentCheck)){
						$aErrors = array_merge($aErrors,$mDocumentCheck);
					}

				}

				if(
					!$bItemsSuccess ||
					!$bVersionSuccess ||
					!$bPDFSuccess ||
					!$bDocumentSuccess ||
					!$bPriceIndexSuccess ||
					$this->bNegate
				){

					//change flags resetzen
				}

				// Bei Fehlern
				/**
				 * @todo Dieser Punkt muss überarbeitet werden weil eventuell das Speichern einer Version fehlgeschlagen ist
				 * und diese deswegen auch nicht gelöscht werden muss.
				 */
				if(
					(
						!$bItemsSuccess ||
						!$bVersionSuccess ||
						!$bPDFSuccess ||
						!$bDocumentSuccess ||
						!$bPriceIndexSuccess
					) &&
					$bEdit === false 
				){

					// Rechnung wieder löschen
					$oDocument->document_number = '';
					$oDocument->active = 0;
					$oDocument->save();
					
					DB::rollback('save_inquiry_document');

					$oLogger->addError('Document transaction rollback (individual block) for inquiry '.join(', ', $aInquiryIds), array(
						'inquiries' => $aInquiryIds,
						'version_success' => $bVersionSuccess,
						'document_success' => $bDocumentSuccess,
						'vars' => $_VARS,
						'user_id' => $user_data['id']
					));

				} else if(
					(
						!$bItemsSuccess ||
						!$bVersionSuccess || 
						!$bPDFSuccess ||
						!$bPriceIndexSuccess
					) &&
					$bEdit
				){

					if($oVersion instanceof Ext_Thebing_Inquiry_Document_Version) {
						$oLastVersion = $oVersion;
					} else {
						$oLastVersion = $oDocument->getLastVersion();
					}
					
					if(
						$oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version &&
						$oLastVersion->id > 0
					) {
						$oLastVersion->active = 0;
						$oLastVersion->save();
					}

				}

			} else { // GRUPPEN

				// bei gruppen speichern wir NUR EINZELRECHNUNGEN
				// Extrapositionen werden aufgeteilt!

				// schauen ob der typ passt wenn ja dann das document nehmen sonst neu anlegen
				$sInquiryDoctype = str_replace('group_', '', $sDocumentType);

				$aAllItems = $aInquiryItems;

				// Wenn man bei einzelnen Rechnungen ohne Positionen speichern kann, dann sollte das bei Gruppen genau so funktionieren
				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);
				$oGroup = $oInquiry->getGroup();
				$aAllInquiries = (array)$oGroup->getInquiries(false,true,false);
				foreach($aAllInquiries as $iInquiryTempId){
					if(!isset($aAllItems[$iInquiryTempId])){
						$aAllItems[$iInquiryTempId] = array();
					}
				}

				$aItems = array();

				// Hier kommen die Doc objecte rein
				$aTempSavedDocuments = array(); /** @var Ext_Thebing_Inquiry_Document[] $aTempSavedVersions */
				$aTempSavedVersions = array(); /** @var Ext_Thebing_Inquiry_Document_Version[] $aTempSavedVersions */

				$sTempDocNumber = $sDocumentNumber = '';
				$iTempDocumentNumberrangeId = $iDocumentNumberrangeId = 0;
				$iMaxVersion = 0;
				
				foreach((array)$aAllItems as $iGroupInquiryId => $aItems) {

					if($iGroupInquiryId <= 0){
						//TODO aufsplitten!! diese position muss auf alle rechnungen verteilt werden
						continue;
					}

					$oInquiry = Ext_TS_Inquiry::getInstance($iGroupInquiryId);

					$iSearchCredit = $iIsCredit;
					// #5664 - bei Gutschrift dürfen nur Dokumente gesucht werden, die is_credit = 0 haben,
					// ansonsten wird in der ts_documents_to_documents falsche Beziehungen abgespeichert
					if(
						$iIsCredit &&
						$iDocumentId == 0
					) {
						$iSearchCredit = 0;
					}

					// letztes einzeldokument suchen
					$oSearch = new Ext_Thebing_Inquiry_Document_Search((int)$oInquiry->id);
					$oSearch->setType($oOriginalDocument->type);
					$oSearch->setCredit($iSearchCredit);
					$iLastInquiryDoc = $oSearch->searchDocument(false, false);

					$sLastType = '';
					if($iLastInquiryDoc > 0) {
						$oLastDoc = new Ext_Thebing_Inquiry_Document($iLastInquiryDoc);
						$sLastType = $oLastDoc->type;
					}

					// Hier wird beim aktualisieren eines Dokuemnts das bereits vorhandene Dokument verwendet
					// mit einer neuen Version. Da es möglich ist Rechnung->Gutschrift->Rechnung->Gutschrift...
					// zu erstellen muss hier auch auf die original->id geprüft werden
					if(
						$sLastType == $sInquiryDoctype &&
						$oOriginalDocument->id > 0
					) {
						$oDocument = $oLastDoc;
					}
					// Dieser Teil wird seit #9431 nicht mehr ausgeführt, da früher immer fix invoice gesucht wurde
//					elseif(
//						$sLastType == 'storno' &&
//						$bEdit == true
//					) {
//						// Nach der Gruppenrechnung wurde storniert, wir suchen die erste nicht-storno Rechnung
//						$iLastInquiryDoc = Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, 'invoice_without_storno');
//
//						if($iLastInquiryDoc > 0) {
//							$oLastDoc = new Ext_Thebing_Inquiry_Document($iLastInquiryDoc);
//							$sLastType = $oLastDoc->type;
//
//							$oDocument = $oLastDoc;
//						} else {
//							// Dürfte !NIE! vorkommen. Tut es aber leider :(
//							continue;
//						}
//
//					}
					// Dieser Teil wird seit #9431 nicht mehr ausgeführt, da früher immer fix invoice gesucht wurde
					// Da jetzt immer derselbe Typ gesucht wird, kommt oben immer das letzte Dokument raus…
//					elseif(
//						(
//							$sLastType == 'brutto' ||
//							$sLastType == 'storno'
//						) &&
//						$sInquiryDoctype == 'creditnote' &&
//						$oOriginalDocument->id > 0
//					) {
//
//						$iLastInquiryDoc = Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, array('creditnote'));
//
//						if($iLastInquiryDoc > 0) {
//							$oLastDoc = Ext_Thebing_Inquiry_Document::getInstance($iLastInquiryDoc);
//
//							$oDocument = $oLastDoc;
//						}
//
//					}
					else {
						$oDocument = $oInquiry->newDocument($sInquiryDoctype);

						// Dieser Teil macht seit #9431 keinen Sinn mehr und bei Stornos wird ohnehin $iParentDocumentId überschrieben
//						if(
//							$sInquiryDoctype !== 'storno' || (
//								// #10722: Bei einer Gruppenstorno kann $iLastInquiryDoc = 0 sein und dann gäbe es keine Relation zwischen den Dokumenten?
//								$sInquiryDoctype === 'storno' &&
//								$iLastInquiryDoc > 0
//							)
//						) {
//							$iParentDocumentId = $iLastInquiryDoc;
//						}

					}

					$oDocument->setAutoGenerateNumber(false);

					// Document Data
					$oDocument->entity = get_class($oInquiry);
					$oDocument->entity_id = $iGroupInquiryId;
					//$oDocument->inquiry_id			= $iGroupInquiryId;
					$oDocument->active				= 1;
					$oDocument->editor_id	= $user_data['id'];
					$oDocument->type				= $sInquiryDoctype;
					if($oDocument->document_number == '') {
						$oDocument->document_number		= $sDocumentNumber;
						$oDocument->numberrange_id		= $iDocumentNumberrangeId;
					}

					$oDocument->is_credit			= $iIsCredit;

					// Parent-ID für ts_documents_to_documents benötigt, da $iParentDocumentId $iSelectedId ist
					if($oDocument->getRelationParentKey()) {
						$oSelectedDocument = Ext_Thebing_Inquiry_Document::getInstance($iParentDocumentId);
						$oParentDocument = Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, $oSelectedDocument->type, false, true);

//						if(!$oParentDocument instanceof Ext_Thebing_Inquiry_Document) {
//							throw new LogicException('No parent document found for group member');
//						}

						// Wenn Kunde an Items gepfuscht hat oder Schüler neu dazu kamen, kann es kein Parent-Doc geben
						if($oParentDocument instanceof Ext_Thebing_Inquiry_Document) {
							$oDocument->saveParentRelation($oParentDocument->id, false);
						}
					}

//					if(
//						$iParentDocumentId > 0 &&
//						$iParentDocumentId != $oDocument->id
//					) {
//						// Relationen setzen, aber NICHT speichern #5819
//						// Wir sind hier in einer Schleife und nach dem ersten Speichern des Dokumentes hätte das Dokument eine ID
//						// In der save ist eingebaut: Bei ID und leerer Dokumentnummer: generieren – das ist sehr schlecht bei einer Gruppenbuchung!
//						$oDocument->saveParentRelation($iParentDocumentId, false);
//					}

					$oDocument->save(true);

					// Dokument in die Instanz packen, da es ansonsten in der createPdf mit getLatestVersion() Probleme gibt (redundant wie oben)
					Ext_Thebing_Inquiry_Document::setInstance($oDocument);

					// Nach dem speichern das Document zwischenspeichern
					$aTempSavedDocuments[] = $oDocument;

					if($oDocument->document_number){
						$sTempDocNumber = $oDocument->document_number;
						$iTempDocumentNumberrangeId = $oDocument->numberrange_id;
					}

					if($oDocument->id <= 0){
						throw new RuntimeException('Document couldn\'t be created!');
					}

					$oOldVersion = $oDocument->getLastVersion();
					
					$sDate = $_VARS['save']['date'];
					if(
						empty($sDate) &&
						is_object($oOldVersion)
					){
						$sDate = $oOldVersion->date;
					}elseif(
						!empty($sDate)
					){
						$sDate = $oDateFormat->convert($sDate);
					}

					if (
						!\Core\Helper\DateTime::isDate($sDate, 'Y-m-d')
					) {
						$sDate = date('Y-m-d');
					}
					
					$oVersion						= $oDocument->newVersion();
					$oVersion->template_id			= $oTemplate->id;
					$oVersion->date					= $sDate;
					$oVersion->txt_address			= $_VARS['save']['address'];
					$oVersion->txt_subject			= $_VARS['save']['subject'];
					$oVersion->txt_intro			= $oPurifier->purify($_VARS['save']['intro']);
					$oVersion->txt_outro			= $oPurifier->purify($_VARS['save']['outro']);
					$oVersion->txt_pdf				= $oTemplate->getOptionValue($sDisplayLanguage, $iSchoolId, 'first_page_pdf_template');
					$oVersion->txt_signature		= $_VARS['save']['signature_txt'];
					$oVersion->signature			= $_VARS['save']['signature_img'];
					$oVersion->comment				= (string)$_VARS['save']['comment'];
					$oVersion->template_language	= $sDisplayLanguage;
					$oVersion->tax					= $oSchool->tax;
					$oVersion->payment_condition_id = $_VARS['save']['payment_condition_select'];
					$oVersion->addresses 			= $aSelectedAddress;
					$oVersion->company_id = $_VARS['save']['company_id'] ?? null;

					Ext_TS_Document_PaymentCondition::convertRequestPaymentTerms($this->oGui->getRequest(), $oVersion);

					if($oVersion->document_id <= 0){
						throw new RuntimeException('Version couldn\'t be created!');
					}

					$mVersionCheck = $oVersion->validate();

					if($mVersionCheck !== true) {

						$bVersionSuccess = false;

						if(is_array($mVersionCheck)){
							$aErrors = array_merge($aErrors,$mVersionCheck);
						}
					}

					$bItemsSuccess = true;

					if($bVersionSuccess) {

						$oVersion->save();
						Ext_Thebing_Inquiry_Document_Version::setInstance($oVersion);

						$aTempSavedVersions[] = $oVersion;

						// Editierbare Layoutfelder speichern (wie immer redundant)
						foreach((array)$_VARS['save'] as $iKey => $sData){
							if(
								strpos($iKey, 'editable_html_field') !== false ||
								strpos($iKey, 'editable_date_field') !== false ||
								strpos($iKey, 'editable_text_field') !== false
							){
								// Block-ID herausfinden
								$aBlockData = explode('_', $iKey);

								$iBlockId = (int)$aBlockData[3];
								// Wenn $iBlockId == 0 dann ist es der "Fake-block" der geklont wird
								if($iBlockId > 0) {
									$oField = $oVersion->getNewLayoutField();
									$oField->block_id = $iBlockId;
									$oField->content = $sData;
									$oField->save();
								}
							}
						}

						// Höchste Version ermitteln, da alle Versions eines Documentes dieselbe Versionsnummer haben müssen
						$iMaxVersion = max($iMaxVersion, $oVersion->version);

						$iPosition = 1;

						$aItemObjects = array();

						foreach((array)$aItems as $aItem) {

							// customer_id bei Gruppen setzen; bei einzelnen Schülern passiert das durch die Dummy-Position
							// Ext_Thebing_Document_Positions::updatePosition() -> $aOthers
							if(
								$aItem['status'] === 'new' && (
									$aItem['type'] === 'additional_general' ||
									$aItem['type'] === 'extraPosition'
								)
							) {
								$aItem['data']['customer_id'] = $oInquiry->getCustomer()->id;
							}

							if($aItem['position'] > 0) {
								$iPosition = $aItem['position'];
							}

							$oItem = $oVersion->newItem();

							if($aItem['description'] == '') {
								continue;
							}

							$oItem->iOldItemId = (int)$aItem['position_id'];
							$oItem->sOldItemStatus = $aItem['status'];

							// Werte in das Item setzen
							$this->setItemValues($oItem, $aItem);
						
							// Neue versions id!!
							$oItem->version_id = $oVersion->id;
							$oItem->calculate = $oItem->onPdf;
							if($oItem->version_id <= 0){
								throw new RuntimeException('Version item couldn\'t be created!');
							}
							$oItem->position = $iPosition;

							$oItem->count = (int)$aItem['count'];
							
							// Wenn index_from angeben ist dann bassierte diese Item auf einem anderem ITEM ( z.b bei diff)
                            // dann muss dieser wert genommen werden
                            // ['from'] enthalt wenn angeben immer das from der leistung wie sie jetzt aktuell ist
                            if(!empty($aItem['index_from'])) {
								$oItem->index_from = $aItem['index_from'];
							} else if(!empty($aItem['from'])) {
								$oItem->index_from = $aItem['from'];
							}
							
                            // Wenn index_until angeben ist dann bassierte diese Item auf einem anderem ITEM ( z.b bei diff)
                            // dann muss dieser wert genommen werden
                            // ['until'] enthalt wenn angeben immer das until der leistung wie sie jetzt aktuell ist
							if(!empty($aItem['index_until'])) {
								$oItem->index_until = $aItem['index_until'];
							} else if(!empty($aItem['until'])) {
								$oItem->index_until = $aItem['until'];
							}

							// Steuersatz ausrechnen
							$fTaxRate = 0;
							if($aItem['tax_category'] > 0) {
								$dVatDate = Ext_TS_Vat::getVATReferenceDateByDate($oSchool, new Carbon\Carbon($oItem->index_from), new Carbon\Carbon($oItem->index_until));
								$fTaxRate = Ext_TS_Vat::getTaxRate($aItem['tax_category'], $oSchool->id, $dVatDate->toDateString());
							}
							$oItem->tax = $fTaxRate;

							$aItemObjects[] = $oItem;

							$oItem->updateItemCache();
							$mItemCheck = $oItem->validate();
 
							if($mItemCheck !== true) {
								
								$bItemsSuccess = false;

								if(is_array($mItemCheck)){
									$aErrors = array_merge($aErrors,$mItemCheck);
								}

							}

							$iPosition++;
						}

						System::wd()->executeHook('ts_inquiry_document_modify_items', $aItemObjects, $oDocument);

					}

					// Speichern wenn ALLES OK ist
					if(
						$bItemsSuccess &&
						$bVersionSuccess
					) {

						// Price Index
						$oPriceIndex				= new Ext_Thebing_Inquiry_Document_Version_Price();
						
						foreach((array)$aItemObjects as $oItem) {

							$oItem->save();

							$oPriceIndex->addItem($oItem);

							// hier werden die Payment daten angeglichen da wir nun neue Item IDs haben da wir versionen anlegen
							// auserdem müssen weggefallene Pos. ebenfallss als "Überbezahlung" gehandhabt werden
							// und NUR bei bestehenden Rechnungen!
							if(
								$oItem->iOldItemId > 0 && 
								$iDocumentId > 0
							) {
								$oItemOld = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($oItem->iOldItemId);
								$oItemOld->refreshPaymentData($oItem);
							}

						}

						// Preis Index speichern
						$mPriceIndexCheck = $oPriceIndex->savePrice($oVersion->id);

						if(is_array($mPriceIndexCheck)) {
							$aErrors[] = L10N::t('Preisindex konnte nicht erstellt werden.', 'Thebing » Errors');
						}

						// TODO Funktioniert bei Gruppen nicht, da Beträge erst nach der Schleife verteilt werden (#8838, #9430)
//						$aValidatePrepayAmounts = $oVersion->validatePaymentTermsAmount();
//						if(!empty($aValidatePrepayAmounts)) {
//							$aErrors = array_merge($aErrors, $aValidatePrepayAmounts);
//						}

						$fVersionAmount = $oVersion->getAmount();

						if(
							$oDocument->type != 'storno' &&
							!$oDocument->isDraft()
						) {
							$oInquiry->setInquiryStatus($oDocument->type);
						}

						// Beträge neu schreiben
						// TODO savePrepayCache(), getAmount() & co. speichern die Inquiry fünf mal (siehe oben)
						$fAmount = $oInquiry->getAmount(false, true);

						$oInquiry->getAmount(true, true);

						//$oInquiry->getCreditAmount(true);

						if($oDocument->type == 'storno') {
							$oInquiry->confirmCancellation($fAmount);
						}

						// Wurde durch die Korrektur von saveParentRelation ersetzt
//						if(strpos($sDocumentType, 'creditnote') !== false) {
//
//							// wenn credit note, dann fülle zwischentabelle
//
//							// man darf nur eine creditnote für eine Bruttorechnung oder einen Storno erstellen...
//							$oLastDoc = $oInquiry->getLastDocument(array('brutto', 'storno'));
//
//							$oLastDoc->child_documents_creditnote = array($oDocument->id);
//
//							$oLastDoc->save();
//						}

						// Veränderungen reseten da die Rechnung nun gespeichert wurde
						Ext_Thebing_Inquiry_Document_Version::clearChanges($iGroupInquiryId, $oDocument->id);

					}
					
					if(is_null($oFirstVersion)){
						$oFirstVersion = $oVersion;
					}
					
				}

				// Document number schreiben (bei allen die gleiche)
				$sDocumentNumber = $sTempDocNumber;
				$iDocumentNumberrangeId = $iTempDocumentNumberrangeId;

				// Nummer generieren, wenn es noch keine gibt
				if($sDocumentNumber == '') {
					$sDocumentNumber = $oNumberrange->generateNumber();
					$iDocumentNumberrangeId = $oNumberrange->id;
				}

				$bPDFSuccess = true;
				$oPdfMainDocument = null; /** @var Ext_Thebing_Inquiry_Document $oPdfMainDocument */

				if(empty($aErrors)) {
					// Zwischengespeicherte Dokumente durchgehen und PDFs erzeugen
					// Erst hier da oben noch nich alle Infos gespeichert sind
					foreach((array)$aTempSavedDocuments as $oDocument) {
						/** @var Ext_Thebing_Inquiry_Document $oDocument */

						/*
						 * Die Instanzen der Child-Dokumente werden geladen bevor über die Parent-Dokumente die
						 * Relationen gesetzt werden.
						 * Da die WDBasic die Beziehung vom Child zum Parent nicht automatisch setzt
						 * bei Joined-Tables müssen die Relationen hier explizit neu geladen werden, ansonsten würden
						 * die Beziehnungen beim save() hier in der Schleife wieder gelöscht werden.
						 * Das Ganze natürlich nur bei Creditnotes, ansonsten gibt es diese Relation nicht. (#9824)
						 */
						if(strpos($sDocumentType, 'creditnote') !== false) {
							$oDocument->reloadJoinTable('parent_documents_creditnote');
							$oDocument->reloadJoinTable('child_documents_creditnote');
						}

						// GUI bestimmen für GUI2-Zuweisung
						if($bIsMultiple) {
							// Bei Mehrfachauswahl ist $oGui = $oParentGui, da die Dokument-GUI fehlt!
							$oTmpGui = $oGui;
						} elseif(
							isset($oParentGui) &&
							$oParentGui instanceof Ext_Gui2
						) {
							$oTmpGui = $oParentGui;
						}

						// GUI2-Zuweisung setzen
						if(
							isset($oTmpGui) &&
							$oTmpGui instanceof Ext_Gui2
						) {

							// Da nur die GUI-Configs standardmäßig einen Namen haben, muss das hier sukzessive korrigiert werden…
							$sTmpGuiName = $oTmpGui->name;
							if(empty($sTmpGuiName)) {
								throw new RuntimeException('Parent-Document-GUI has no name');
							}

							$aTmp = $oDocument->gui2;
							if(empty($aTmp)) {
								$oDocument->gui2 = array(array(
									'name' => $oTmpGui->name,
									'set' => $oTmpGui->set
								));
							}
						}

						$oDocument->document_number = $sDocumentNumber;
						$oDocument->numberrange_id = $iDocumentNumberrangeId;

						try {
							$oDocument->save();
						} catch (RuntimeException $e) {
							if($e->getMessage() === 'No numberrange!') {
								$sMessage = L10N::t('Es konnte kein Nummernkreis gefunden werden!', self::$sL10NDescription);
								return $this->generateSaveDialogDataErrorMessage($sMessage, $aInquiryIds);
							}
							throw $e;
						}

						if($oPdfMainDocument === null) {
								$oPdfMainDocument = $oDocument;

								$oVersion = $oDocument->getLastVersion(false);
								
								$oVersion->version = $iMaxVersion;

								$oVersion->save();
						}

					}

					// PDF erzeugen
					if(is_object($oPdfMainDocument)){
						try {
							// PDF nur vom Main document erstellen
							$oPdfMainDocument->createPdf(false, $sDisplayLanguage);
							$oVersion = $oPdfMainDocument->getLastVersion(false);
							$oVersion->version = $iMaxVersion;
							$oVersion->save();

						} catch (PDF_Exception $e) {
							#$aErrors[$iErrorCount]['message'] = L10N::t('PDF konnte nicht erstellt werden! Bitte überpüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors').' ('.$e->getMessage().')';
							$aErrors['pdf'][] = L10N::t('PDF konnte nicht erstellt werden! Bitte überprüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors').' ('.$e->getMessage().')';
							$iErrorCount++;
							$bPDFSuccess = false;
						}

						// Wenn PDF erstellt wurde dann alle Gruppenmitglieder mit
						// dem PDF der MainVersion updaten
						foreach((array)$aTempSavedDocuments as $oDocument) {

							$oMainVersion = $oPdfMainDocument->getLastVersion(false);

							$oVersion = $oDocument->getLastVersion(false);
							$oVersion->version = $iMaxVersion;
							$oVersion->path = $oMainVersion->path;
							$oVersion->save();

							$oDocument->initUpdateTransactions();
							
						}

						// Bezahlbelege aktualisieren (irgendeine Buchung und irgendein Dokument)
						self::refreshPaymentReceipts($oInquiry, $oPdfMainDocument);

					}

				}

				if(
					!$bPDFSuccess &&
					$bEdit === false
				){
					foreach((array)$aTempSavedDocuments as $oDocument){
						// Rechnung wieder löschen
						$oDocument->document_number = '';
						$oDocument->active = 0;
						$oDocument->save();
						if($oDocument->type == 'storno') {
							$oInquiryTmp = $oDocument->getInquiry();
							$oInquiryTmp->canceled = '';
							$oInquiryTmp->save();
							$oDocument->save();
						}
					}
				} else if(
					!$bPDFSuccess && 
					$bEdit
				) {
					
					if($oVersion instanceof Ext_Thebing_Inquiry_Document_Version) {
						$oLastVersion = $oVersion;
					} else {
						$oLastVersion = $oDocument->getLastVersion();
					}
					
					if(
						$oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version &&
						$oLastVersion->id > 0
					) {
						$oLastVersion->active = 0;
						$oLastVersion->save();
					}

				}

				// Fehler sollen nur einmal angezeigt werden und nicht pro Gruppenmitglied
				// Funktioniert mit neuen Payment-Terms nicht mehr, da dann die Keys weg sind
				// Der Dialog scheint mittlerweile aber auch doppelte Meldungen auszusortieren?
				//$aErrors = Ext_TC_Util::arrayUnique($aErrors);

				if($bPDFSuccess) {

					// Anzahlungsbetrag muss bei Gruppen anteilig aufgeteilt werden (#9430)
					foreach($aTempSavedVersions as $oVersion) {
						$oVersion->calculatePrepayAmount($aTempSavedVersions);
					}

					foreach((array)$aTempSavedDocuments as $oDocument) {
						$this->prepareAttachedAdditionalDocumentsGenerating($oDocument, $_VARS);
					}

					// Gruppe im Index aktualisieren, da sich die Rechnungsnummern ggf. geändert haben
					Ext_Gui2_Index_Stack::add('ts_inquiry_group', $oGroup->id, 0);
				}
				
			} // Gruppen Ende

			//nicht Fehler pro Schüler anzeigen, sondern für alle einen
			if(!empty($aErrors)){
				break;
			}

			// Nummernkreis-Sperre erneuern für langwierige Gruppen-Dokument-Generierungen
			if($oNumberrange instanceof Ext_TS_NumberRange) {
				$oNumberrange->renewLock();
			}
		}

		$sDialogId = $this->getSaveDialogDialogId($aInquiryIds);

		if(empty($aErrors)) {
			
			$aTransfer['action']				= 'saveDialogCallback';
			$aTransfer['task']					= 'closeDocument';
			
			if($bIsMultiple) {

				$oSchool	= Ext_Thebing_School::getSchoolFromSession();
				$sSchoolTmpDir = $oSchool->getSchoolFileDir().'/temp/';

				try {
					
					// Zu lange Namen verursachen Fehler
					$sTempPathHash = 'multiple_'.md5($sDialogId);

					// TODO Man müsste mal in den Depuration-Cronjob einbauen, dass das /temp-Verzeichnis auch mal geleert wird
					if(!Util::checkDir($sSchoolTmpDir)) {
						throw new RuntimeException('Could not create school temporary dir '.$sSchoolTmpDir.'!');
					}
					
					$sFileName = $oPdfAll->createPDF($sSchoolTmpDir, $sTempPathHash);
					if(file_exists($sFileName)) {
						$sFileName = str_replace(Util::getDocumentRoot(), '', $sFileName);
						$sFileName = str_replace('storage', '', $sFileName);

						$aTransfer['data']['options']['close_after_save'] = true;
						$aTransfer['action']	= 'saveDialogCallback';
						$aTransfer['success_message'] = array(
							L10N::t('Die Dokumente wurden erfolgreich angelegt.', $oGui->gui_description).'<br/><a download href="/storage/download'.$sFileName.'?no_cache">'.L10N::t('Bitte klicken Sie hier, um ein PDF mit allen Dokumenten anzuzeigen.', $oGui->gui_description).'</a>'
						);
					}

				} catch(Exception $e) {
					$aErrors[]['message'] = L10N::t('Massen PDF konnte nicht erstellt werden! Bitte versuchen Sie erneut!', 'Thebing » Errors');
				}

			}

			// Komplette Historie muss mit geschickt werden da sie sonst nicht neu geladen wurde
			if(
				isset($_SESSION['thebing']['document_type']) &&
				$sAction != 'offer'
			){

				// Hauptinquiry ermitteln
				if(
					$sDocumentType !== 'additional_document' &&
					$oInquiry->hasGroup()
				) {
					$oGroup = $oInquiry->getGroup();
					$oMainInquiry = $oGroup->getMainDocumentInquiry();
				} else {
					// Bei Zusatzdokumenten wird nur für den Ausgewählten das Dokument generiert, daher niemals MainInquiry
					$oMainInquiry = $oInquiry;
				}

				// Fallback für History-Tab
				if(
					isset($oParentGui) &&
					$oParentGui instanceof Ext_Gui2
				) {
					$oTmpParentGui = $oParentGui;
				} else {
					$oTmpParentGui = $oGui;
				}

				$sHistoryHtml = self::getHistoryHtml($oTmpParentGui, $oMainInquiry, $_SESSION['thebing']['document_type'], $oGui);
				$aTransfer['data']['history_html'] = $sHistoryHtml;

			}

			$bTransactionCommit = DB::commit('save_inquiry_document');

			$oLogger->addInfo('Document transaction commit for inquiry '.join(', ', $aInquiryIds), array(
				'inquiries' => $aInquiryIds,
				'version_success' => $bVersionSuccess,
				'document_success' => $bDocumentSuccess,
				'transaction_commit' => $bTransactionCommit
			));

		} else {

			$this->executeSaveDialogDataError($aTransfer, $aInquiryIds, array(
				'inquiries' => $aInquiryIds,
				'version_success' => $bVersionSuccess,
				'document_success' => $bDocumentSuccess,
				'vars' => $_VARS,
				'user_id' => $user_data['id']
			));

		}

		/*
		 * Nummernkreis hier (einfach) entsperren
		 *
		 * Das sollte kein Problem sein, da nur der Request, der den Nummernkreis gesperrt hat,
		 * bis hierhin kommen sollte, denn bei einer Blockade wird die Methode bereits dort
		 * mit einem return beendet.
		 */
		if($oNumberrange instanceof Ext_TS_NumberRange) {
			$oNumberrange->removeLock();
		}

		$aTransfer['error']					= $aErrors;
		$aTransfer['data']['id']			= $sDialogId;
		$aTransfer['data']['type']			= $sAction;
		$aTransfer['data']['document_id']	= $oDocument->id;
		$aTransfer['data']['document_type']	= $oDocument->type;
		$aTransfer['data']['save_id']		= $iSelectedId;
		$aTransfer['data']['selectedRows']	= array($oDocument->id);
		$aTransfer['data']['parent_gui']	= reset($oGui->parent_gui);
		$aTransfer['data']['selectedRows']	= $aSelectedIds;

		return $aTransfer;
	}

	/**
	 * Methode zum Beenden von saveDialogData im Fehlerfall
	 *
	 * @param array|null $aTransfer
	 * @param array $aInquiryIds
	 * @param array $aLogInfo
	 */
	protected function executeSaveDialogDataError(&$aTransfer, $aInquiryIds, $aLogInfo) {
		global $_VARS, $user_data;

		$oLogger = Log::getLogger();

		// ID muss gesetzt sein, ansonsten stirbt der Aufruf von GUI2.displayErrors()
		// Das passiert anscheinend nur bei Legacy
		$aTransfer['data']['id'] = $this->getSaveDialogDialogId($aInquiryIds);

		// Task, damit Fehler aufgerufen werden
		$aTransfer['action'] = 'saveDialogCallback';
		$aTransfer['task'] = 'saveDialogCallback';

		// Transaktion zurücksetzen, aber nicht Memcache-Sperre
		DB::rollback('save_inquiry_document');

		$aLogInfo['vars'] = $_VARS;
		$aLogInfo['user_id'] = $user_data['id'];
		$aLogInfo['backtrace'] = Util::getBacktrace();

		$oLogger->addError('Document transaction rollback for inquiry '.join(', ', $aInquiryIds), $aLogInfo);
	}

	/**
	 * @param string $sMessage
	 * @param array $aInquiryIds
	 * @return array
	 */
	protected function generateSaveDialogDataErrorMessage($sMessage, $aInquiryIds) {

		$aTransfer = array(
			'error' => array(
				'pdf' => array(
					$sMessage
				)
			)
		);

		$this->executeSaveDialogDataError($aTransfer, $aInquiryIds, array(
			'inquiries' => $aInquiryIds
		));

		return $aTransfer;

	}

	/**
	 * Dialog-ID für den Save-Dialog bauen
	 *
	 * @param array $aInquiryIds
	 * @return string
	 */
	protected function getSaveDialogDialogId($aInquiryIds) {

		// Wenn Dialog-ID übermittelt, dann wiederverwenden, da diese auch anders sein kann
		if ($this->oGui->getRequest()->has('dialog_id')) {
			return $this->oGui->getRequest()->input('dialog_id');
		}

		sort($aInquiryIds);
		$sSelectedInquiryIds = implode('_', $aInquiryIds);
		$sDialogId = 'DOCUMENT_'.$sSelectedInquiryIds;
		return $sDialogId;

	}

	/**
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @param array $aItem
	 */
	public function setItemValues(Ext_Thebing_Inquiry_Document_Version_Item &$oItem, array $aItem) {

		$aItem = (array)$aItem;
		
		foreach($aItem as $sField=>&$mValue) {

			// Beträge in Float umwandeln
			if(
				$sField == 'amount' ||
				$sField == 'amount_provision' ||
				$sField == 'amount_net' ||
				$sField == 'amount_discount'
			) {
				$mValue = Ext_Thebing_Format::convertFloat($mValue);
			}
			
		}
		
		//den Contact zu diesem Item speichern
		if($aItem['data']['customer_id'] > 0) {
			$oItem->contact_id = (int)$aItem['data']['customer_id'];
		} else {
			throw new RuntimeException('Missing customer_id for item!');
		}
		
		$oItem->active = 1;
		
		$aItem['tax'] = $fTaxRate;
		unset($mValue);

		foreach($aItem as $sField=>$mValue) {

			// Nicht numerische Werte durch "0" ersetzen, für "type" transfer
			if(
				(
					$sField == 'type_id' ||
					$sField == 'type_object_id' ||
					$sField == 'type_parent_object_id'
				) &&
				!is_numeric($mValue)
			) {
				$mValue = 0;
			}

			//@todo: anders lösen
			if(
				$sField == 'amount' ||
				$sField == 'amount_provision' ||
				$sField == 'amount_net' ||
				$sField == 'amount_discount' ||
				$sField == 'old_description' ||
				$sField == 'description' ||
				$sField == 'description_discount' ||
				$sField == 'type' ||
				$sField == 'type_id' ||
				$sField == 'initalcost' ||
				$sField == 'onPdf' ||
				$sField == 'calculate' ||
				$sField == 'parent_booking_id' ||
				$sField == 'parent_id' ||
				$sField == 'parent_type' ||
				$sField == 'tax_category' ||
				$sField == 'tax' ||
				$sField == 'count' ||
				$sField == 'nights' ||
				$sField == 'additional_info' ||
				$sField == 'type_object_id' ||
				$sField == 'type_parent_object_id'
			) {
				$oItem->$sField = $mValue;
			}

		}

	}

	/**
	 * Get the tamplate title
	 *
	 * @todo: Was soll denn das hier? Warum nicht über das entsprechende Model?
	 */
	public static function getTemplateTitle($iTemplateID) {
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($iTemplateID);
		return $oTemplate->name; 
	}

	public function setEntity(\Ts\Interfaces\Entity\DocumentRelation $oEntity) {
		$this->_oEntity = $oEntity;
	}

	/**
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 */
	public function setInquiry(Ext_TS_Inquiry_Abstract $oInquiry) {
		$this->_oInquiry = $oInquiry;
	}

	/**
	 * @return Ext_TS_Inquiry_Abstract
	 */
	public function getInquiry() {
		return $this->_oInquiry;
	}

	/**
	 * @param Ext_Thebing_Inquiry_Document $oInquiryDocument
	 * @param array $aData
	 * @return array
	 */
	public function getAdditionalDataForDialog(Ext_Thebing_Inquiry_Document $oInquiryDocument, array $aData) {
		global $_VARS;

		$oInquiry = $this->_oEntity;

		if($oInquiry) {

			$oTemplate = null;
			$oSchool = $oInquiry->getSchool();
            $oLastVersion = $oInquiryDocument->getLastVersion();

            // Editierbare Layoutfelder mitschicken (Daten)
            $aEditFieldsValues = array();
            // Editierbare Layoutfilder mitschicken (Felder)
            $aEditFields = array();

            if($oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version) {

                // für was das? bei anfragen verursacht das ein fehler!
                //$oInquiry = $oLastVersion->getInquiry();

                $iTemplateId = $oLastVersion->template_id;
                $oTemplate = new Ext_Thebing_Pdf_Template($iTemplateId);

                // Daten Editable Fields
                $aFields = $oLastVersion->getLayoutFields();
                foreach((array) $aFields as $oField){
                    $aEditFieldsValues[$oField->block_id] = $oField->content;
                }

                // Felder
                $aEditFields = $this->_prepareEditableLayoutFields($oTemplate->template_type_id);

            }

            $aData['data']['total_amount_column'] = $this->sTotalAmountColumn;

            $aData['data']['position_tooltips'] = $this->aPositionsTooltips;

            $aData['data']['template_field_data'] = $this->aTemplateData;
            $aData['data']['document_id'] = (int) $oInquiryDocument->id;

            // Ob es eine Gruppe ist
            $aData['data']['group'] = (int) $this->bGroup;

            $aData['data']['editable_field_data'] = $aEditFieldsValues;

            // Editierbare Layoutfilder mitschicken (Felder)
            $aData['data']['editable_fields'] = $aEditFields;

            // Steuerkategorien
			if($oInquiry instanceof Ext_TS_Inquiry) {
				$dVatDate = Ext_TS_Vat::getVATReferenceDateByDate($oSchool, new \Carbon\Carbon($oInquiry->service_from), new \Carbon\Carbon($oInquiry->service_until));
			} else {
				$dVatDate = new \Carbon\Carbon();
			}            
			$aData['data']['vat'] = Ext_TS_Vat::getTaxCategoryRates($oSchool->id, $dVatDate->toDateString());

            if(
                is_object($oLastVersion) &&
                $oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version &&
                strpos($this->oLastVersion->sAction, 'refresh') === false
            ) {
                //sollte man lieber mal anders nennen, unter tax stelle ich mir was anderes vor
                $iVatMode = (int) $oLastVersion->tax;
            } else {
                $iVatMode = (int) $oSchool->tax;
            }

            $aData['data']['vat_mode'] = $iVatMode;
            // Falls Ext. Steuern
            if($iVatMode == 2) {
                $aData['data']['ext_vat'] = 1;
            } else {
                $aData['data']['ext_vat'] = 0;
            }

			if($oInquiry instanceof Ext_TS_Inquiry) {
				// Array mit Saisons
				$iSaisonId = $oInquiry->getSaisonFromFirstService();

				// Zusatzkosten
				$aGeneral = $oSchool->getGeneralCosts(2, $oInquiry->getCurrency(), $iSaisonId);

				//Agenturprovision für die generellen Zusatzkosten

				if($oInquiry->hasAgency()) {

					$oAgency = $oInquiry->getAgency();

					foreach($aGeneral as $iTypeId=>$aCostData) {

						$aOptions = array();
						$aOptions['type'] = 'additional_general';
						$aOptions['type_id'] = $iTypeId;

						$fProvision = $oAgency->getNewProvisionAmountByType($oInquiry, $aCostData['price'], $aOptions);
						$aGeneral[$iTypeId]['provision'] = round($fProvision, 2);

						// Prüfen ob die Kosten vorortkosten sind oder nicht (in Agentur einstellbar)
						$iInitialcost = 0;
						if($oAgency->checkInitalCost($aCostData['id'])){
							$iInitialcost = 1;
						}
						$aGeneral[$iTypeId]['initalcost'] = $iInitialcost;

					}

				}

				foreach($aGeneral as $iTypeId=>$aCostData) {
					$dVatDate = new \Carbon\Carbon;
					$aGeneral[$iTypeId]['tax_category']	= Ext_TS_Vat::getDefaultCombination('Ext_Thebing_School_Cost', $aCostData['id'], $oSchool, $oInquiry, $dVatDate);
				}

				$aData['data']['additional_costs'] = $aGeneral;

				// Gruppengröße

				if($oInquiry->hasGroup()) {
					$oGroup = $oInquiry->getGroup();
					$aData['data']['count_others'] = $oGroup->countNonGuideMembers();
					$aData['data']['count_guides'] = $oGroup->countGuides();
				} else {
					$aData['data']['count_others'] = 1;
					$aData['data']['count_guides'] = 0;
				}
            }

        } else {
			$aData['data'] = [];
		}

		return $aData;
	}

	/**
	 * Bereitet die Editieraren Layoutfelder für den Dokument Dialog vor
	 *
	 * @param $iTemplateTypeId
	 * @return array
	 */
	protected function _prepareEditableLayoutFields($iTemplateTypeId) {
		
		$oTemplateType = new Ext_Thebing_Pdf_Template_Type($iTemplateTypeId);
		
		// Alle editierbaren Felder dieses Layouts bestimmen
		$aTemplateTypeEditableFields = $oTemplateType->getEditableElements();
		
		$aEditableFields = array();
		
		foreach((array)$aTemplateTypeEditableFields as $oField){
			if($oField->editable == 1) {
				$aFieldData = array();
				$aFieldData['id']		= $oField->id;
				$aFieldData['type']		= $oField->element_type;
				$aFieldData['name']		= $oField->name;
				$aFieldData['value']	= '';
				$aEditableFields[] = $aFieldData;
			}
		}
		
		return $aEditableFields;
	}

	/**
	 * Fehler wenn ein Dokument mit der selben Numberrange versucht wird anzulegen
	 *
	 * @return string
	 */
	public static function getNumberLockedError() {
		return L10N::t('Es wird gerade ein anderes Dokument generiert! Bitte versuchen Sie es gleich nochmal.', self::$sL10NDescription);
	}

	public static function getNumberrangeNotFoundError() {
		return L10N::t('Es wurde kein Nummernkreis gefunden. Bitte prüfen Sie Ihre Nummernkreis-Einstellungen!', self::$sL10NDescription);
	}

	/**
	 * Bezahlbelege bei Rechnungsänderung aktualisieren
	 *
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 */
	public static function refreshPaymentReceipts(Ext_TS_Inquiry $oInquiry, Ext_Thebing_Inquiry_Document $oDocument) {

		if(!$oDocument->checkDocumentType('invoice_without_proforma')) {
			return;
		}

		list($bCustomerReceipt, $bAgencyReceipt) = Ext_Thebing_Inquiry_Payment::getNeededPaymentReceiptTypes($oInquiry, Ext_Thebing_Inquiry_Payment::RECEIPT_OVERVIEW);

		// Hier müssen alle Zahlungen der Buchung/Gruppe geholt werden, da das Generieren des Belegs pro Buchung an einer Zahlung hängt
		// Zudem werden dadurch auch gleich alle Belege pro Buchungen für alle Gruppen-Mitglieder neu generiert
		$aPayments = $oInquiry->getPayments(true);
		foreach($aPayments as $oPayment) {
			if($bCustomerReceipt) {
				$oPayment->prepareInquiryPaymentOverviewPdfs();
			}
			if($bAgencyReceipt) {
				$oPayment->prepareInquiryPaymentOverviewPdfs(true);
			}
		}

		// Übliche Sonderbehandlung für Gruppen
		if($oInquiry->hasGroup()) {
			$aDocuments = $oDocument->getDocumentsOfSameNumber();
		} else {
			$aDocuments = [$oDocument];
		}

		[$bCustomerReceipt, $bAgencyReceipt] = Ext_Thebing_Inquiry_Payment::getNeededPaymentReceiptTypes($oInquiry, Ext_Thebing_Inquiry_Payment::RECEIPT_INVOICE);

		foreach($aDocuments as $oDocument) {
			if($bCustomerReceipt) {
				$oDocument->preparePaymentDocument();
			}
			if($bAgencyReceipt) {
				$oDocument->preparePaymentDocument(true);
			}
		}

	}

	/**
	 * Dialog für Dokument: Tab für weitere Dokumente
	 *
	 * @param string $sLanguage
	 * @param Ext_TS_Inquiry_Abstract $oObject
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @return string
	 */
	public function getAttachedAdditionalDocumentTabHtml(Ext_TS_Inquiry $oObject, Ext_Thebing_Inquiry_Document $oDocument = null) {

		$aItems = [];
//		$aTemplatesToInquiries = [];

		// Beim Umwandeln eines Angebots gibt es ein Dokument, ansonsten natürlich nicht (leere Objekte und so ganz ohne ID, schien alles früher völlig unbekannt zu sein)
		if ($oDocument !== null) {
			foreach($oDocument->getLastVersion()->getItemObjects() as $oItem) {
				$aItem = $oItem->getData();
				$aItem['additional_info'] = json_decode($aItem['additional_info'], true);
				$aItems[] = $aItem;
			}
		} else {
			// Das funktioniert nur bei gleichzeitigem Dokumentendialog, ansonsten steht hier einfach das letzte Dokument drin
			foreach($this->oGui->getDocumentPositions() as $aGroupedItem) {
				$aItems = array_merge($aItems, $aGroupedItem);
			}
		}

		$oService = new Ext_TS_Document_AdditionalServiceDocuments($oObject);
		$aTemplates = $oService->buildOptions($aItems);

		$oDialog = $this->oGui->createDialog();
		$oDiv = $oDialog->create('div');

		if (empty($aTemplates)) {
			$oDiv->setElement($oDialog->createNotification($this->oGui->t('Achtung'), $this->oGui->t('Es stehen keine Dokumente zur Verfügung, die über eine ausgewählte Leistung generiert werden.'), 'info'));
		}

		foreach ($aTemplates as $aTemplateData) {

			$oTemplate = $aTemplateData['template'];
			$bDisabled = $aTemplateData['disabled'];
			$bChecked = !$aTemplateData['created'] && !$bDisabled;

			$sKey = 'attached_additional_document['.$oTemplate->id.']';
			$oDiv->setElement($oDialog->createRow($oTemplate->name, 'checkbox', [
				'id' => $sKey,
				'name' => $sKey,
				'disabled' => $bDisabled,
				'default_value' => $bChecked
			]));

//			$oDiv->setElement($oDialog->createSaveField('hidden', [
//				'name' => 'attached_additional_document_inquiries['.$oTemplate->id.']',
//				'value' => join(',', array_keys($aTemplatesToInquiries[$oTemplate->id]))
//			]));

		}

		return $oDiv->generateHTML();

	}

	/**
	 * Dialog für Dokument: Tab für weitere Dokumente: Ins PP einfügen
	 *
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @param array $aVars
	 */
	public function prepareAttachedAdditionalDocumentsGenerating(Ext_Thebing_Inquiry_Document $oDocument, array $aVars) {

		foreach((array)$aVars['attached_additional_document'] as $iTemplateId => $iChecked) {

			if(!$iChecked) {
				continue;
			}

			// Gruppen: Welche Buchung erhält welches Dokument?
			if(!empty($aVars['attached_additional_document_inquiries'])) {
				$aInquiryIds = explode(',', $aVars['attached_additional_document_inquiries'][$iTemplateId]);
				if(!in_array($oDocument->getInquiry()->id, $aInquiryIds)) {
					continue;
				}
			}

			$aStackData = [
				'type' => 'attached_additional',
				'inquiry_id' => $oDocument->getInquiry()->id,
				'document_id' => $oDocument->id,
				'template_id' => $iTemplateId
			];

			$oStackRepository = Stack::getRepository();
			$oStackRepository->writeToStack('ts/document-generating', $aStackData, 5);

		}

	}

	private function handleDocumentPaymentClearing(Ext_TS_Inquiry $oInquiry, Ext_Thebing_Inquiry_Document $oDocument, Ext_Thebing_Inquiry_Document_Version $oVersion, array $aItemObjects) {

		// Gutschrift an Agentur nur mit gleichem Typ verrechnen (nicht implementiert wg. Aufwand)
		if($oDocument->type === 'creditnote') {

		} else {
			$oParentDocument = $oDocument->getParentDocument();
		}
		
		if(
			!$oParentDocument instanceof Ext_Thebing_Inquiry_Document ||
			!$oParentDocument->exist()
		) {
			return;
		}

		$oPaymentMethod = Ext_Thebing_Admin_Payment::findFirstWithType(Ext_Thebing_Admin_Payment::TYPE_CLEARING, [$oInquiry->getSchool()->id]);
		$aItemsForPayment = array_merge($aItemObjects, $oParentDocument->getLastVersion()->getItemObjects());

		$sComment = sprintf(L10N::t('Automatische Verrechnung von %s mit %s.', self::$sL10NDescription), $oDocument->document_number, $oParentDocument->document_number);

		$oPayment = new \Ext_Thebing_Inquiry_Payment();
		$oPayment->inquiry_id = $oInquiry->id;
		$oPayment->date = $oVersion->date;
		$oPayment->comment = $sComment;
		$oPayment->method_id = $oPaymentMethod->id;
		$oPayment->type_id = 3;
		$oPayment->sender = 'school';
		$oPayment->receiver = 'school';
		$oPayment->amount_inquiry = 0;
		$oPayment->amount_school = 0;
		$oPayment->currency_inquiry = $oInquiry->getCurrency();
		$oPayment->currency_school = $oInquiry->getSchool()->getCurrency();

		$oBuilder = new \Ts\Service\InquiryPaymentBuilder($oInquiry, $aItemsForPayment);
		$oBuilder->execute($oPayment);

	}

	/**
	 * Vorhandene Zahlungen auf übergebene Items umschreiben
	 *
	 * @param Ext_TS_Inquiry $inquiry
	 * @param array $items
	 * @return void
	 */
	public static function reallocatePaymentAmounts(Ext_TS_Inquiry $inquiry, array $items) {

		$payments = $inquiry->getPayments();

		foreach ($payments as $payment) {
			$payment->reallocateAmounts($items);
		}

	}

}
