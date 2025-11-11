<?php

class Ext_Thebing_Document_Positions {

	/**
	 * @var Ext_Thebing_Gui2
	*/	
	public $oGui;

	/**
	 * @var Ext_TS_Inquiry
	 */
	public $oInquiry;

	protected static $_oInstance;

	public $aErrors = array();

	public $sType;
	public $iInquiryDocumentId;
	public $iSourceDocumentId;
	
	public $iTemplateId;
	public $sLanguage;
	public $bNegate					= false;
	public $bRefresh				= false;
	public $sAction					= '';
	public $aCurrentPositionData	= array(); // Aktuelle Positionsdaten beim Sprachwächsel
	public $bRefreshCreditnote		= false;
	public $sDialogId				= null;
	public $oVersion				= null;
	public $bIsCredit				= false;
	public $iInvoiceSelectDocumentId;
	public $sSelectedAddress;
	public $iPartialInvoice = false;
	public $companyId;

	/** @var Ext_TS_Document_PaymentCondition */
	public $oPaymentConditionService;

	/**
	 * Gesamtbetrag Spalte
	 */
	public $sTotalAmountColumn;
	
	/**
	 * Array mit Tooltips für die Positionstabelle
	 * @var type
	 */
	public $aPositionsTooltips;
	
	/**
	 *
	 * @return Ext_Thebing_Document_Positions 
	 */
	public static function getInstance() {

		if(!self::$_oInstance instanceof self) {
			self::$_oInstance = new self;
		}

		return self::$_oInstance;
		
	}
	
	/**
	 * @param bool $bInstanceCach
	 * @return string
	 */
	public function getTable($bInstanceCach = true) {

		$oGui				= $this->oGui;
		$oInquiry			= $this->oInquiry;

		$sType				= $this->sType;
		$iInquiryDocumentId	= $this->iInquiryDocumentId;
		$oSchool			= $this->oInquiry->getSchool();
		$bNegate			= $this->bNegate;
		$bRefresh			= $this->bRefresh;
		$sAction			= $this->sAction;
		$iTemplateId		= $this->iTemplateId;
		$bRefreshCreditnote	= $this->bRefreshCreditnote;
		$sDialogId			= $this->sDialogId;
		
		/**
		 * Legt fest, ob eine Version nachträglich als "neu" markiert werden muss
		 * Ist relevant bei Creditnotes auf Bruttorechnungsgutschriften
		 */
		$bGetNewVersion = false;

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance((int)$iTemplateId);

		$sTemplateType = $oTemplate->type;
		
		$oSchoolForFormat	= Ext_Thebing_Client::getFirstSchool();

		// ------------------------------------------------------------------------

		if($this->iSourceDocumentId) {
			$sourceDocument = Ext_Thebing_Inquiry_Document::getInstance($this->iSourceDocumentId);
		}

		if($this->iInquiryDocumentId) {
			$document = Ext_Thebing_Inquiry_Document::getInstance($this->iInquiryDocumentId);
		} else {
			// TODO Was für ein Quatsch ist das! Einfach ein Objekt erstellen, damit man danach keine Abfragen mehr machen muss?
			if($sType == 'creditnote') {
				$document = $oInquiry->newDocument('creditnote', false);
			} else {
				$document = $oInquiry->newDocument('brutto', false);
			}
		}

		$oVersion = $document->getLastVersion();

		if(!($oVersion instanceof Ext_Thebing_Inquiry_Document_Version)) {
			 $oVersion = $document->newVersion();
		}

		// Hat sich die Firma verändert?
		$oldCompanyId = $this->oGui->getDocumentPositionsCompany();

		if($oldCompanyId != $this->companyId) {
			$this->oGui->resetDocumentPositions();
		}

		$oVersion->company_id = $this->companyId;

		$this->oGui->setDocumentPositionsCompany($oVersion->company_id);
			
		$oDialog = new Ext_Gui2_Dialog();
		$oDialog->bSmallLabels = true;

		/**
		 * Daten für Tabelle vorbereiten
		 */
		$sView						= 'net';
		$bGroup						= false;
		$sPositionHtml				= '';
		// TODO Diese Logik existiert teilweise redundant in openPositionDialog…
		$bPositionsEditable			= true;
		$bCreditnoteCellsEditable	= false; // Standardmäßig false, da von $bPositionsEditable überschrieben
		$bCheckForPositionChanges	= true;
		$bNoPacketPrices			= false;
		$bDiffEdit					= false;

		/**
		 * Settings
		 */
		$oCurrency				= Ext_Thebing_Currency::getInstance($oInquiry->getCurrency());
		$this->sCurrencySign	= $oCurrency->getSign();

		$oFormat = new Ext_Thebing_Gui2_Format_Date(false, $oSchoolForFormat->id);

		// Wenn Gutschrift || wenn EDITIERT + Template gewechselt wird und kein refresh ist
		if(
			$bNegate ||
			(
				$iInquiryDocumentId > 0 &&
				!$bRefresh
			)
		){
			$bCheckForPositionChanges = false;
		}

		## START Wenn additional_documents editiert werden sollen brauchen wir wieder
		// die Rechnungspos des LETZTEN Doc deswegen sTyp umschreiben damit keine Redundanz entsteht
		if(
			$sType == 'invoice_text' &&
			$document->type == 'additional_document'
		) {
			$sType = 'additional_document';
		}
		## ENDE

		switch($sType) {
			case 'proforma':

				if(
					$oInquiry->payment_method == 1 ||
					$oInquiry->payment_method == 3
				) {
					$sDocumentType		= 'proforma_brutto';
					$sView				= 'gross';
					$bGroup				= $oInquiry->hasGroup();
					
				} else {
					$sDocumentType	=	'proforma_netto';
					$sView = 'net';
					$bGroup				= $oInquiry->hasGroup();
				}

				break;
			case 'invoice':
			case 'brutto':
			case 'netto':
			case 'proforma_brutto':
			case 'proforma_netto':
			case 'offer':
			case 'offer_brutto':
			case 'offer_netto':
				if($document->id > 0) {
					if(strpos($document->type, 'brutto') !== false) {
						$sDocumentType		= $document->type;
						$sView				= 'gross';
						$bGroup				= $oInquiry->hasGroup();
						
					} else {
						$sDocumentType		= $document->type;
						$sView				= 'net';
						$bGroup				= $oInquiry->hasGroup();
						
					}
				}else{ 
					
					if(
						!$oInquiry->hasNettoPaymentMethod()
					) {

						$sDocumentType		= 'brutto';
						$sView				= 'gross';
						$bGroup				= $oInquiry->hasGroup();

					} else {
						$sDocumentType		= 'netto';
						$sView				= 'net';
						$bGroup				= $oInquiry->hasGroup();
					}

					/**
					 * Falls diese Zeile Fehler verursacht, bitte an Memed wenden
					 * siehe T-3985
					 */
					if(
						!$bNegate
					){
						$bCheckForPositionChanges = false;
					}
					
				}
				
				if($sType == 'offer')
				{
					$bCheckForPositionChanges = false;
				}
				
				/**
				 * Neues Suchen/speichern von Leistungszeitraum Specials 
				 * Wichtig, falls sich die Specials nach dem Bearbeiten der Buchung geändert haben
				 */
				$oInquiry->findSpecials(true);

				break;

			case 'creditnote':
			case 'proforma_creditnote':

				$sView			= 'creditnote';
				$bGroup			= $oInquiry->hasGroup();

				$sDocumentType = $sType;
	
				// wenn noch keine, dann rechne prov. aus da items von brutto rechnung kommen
				if(
					$bRefreshCreditnote === true || 
					!$document->exist()
				) {
					$oVersion->bCalculateProvisionNew = true;
				} else{
					$oVersion->bCalculateProvisionNew = false;
					//$bPositionsEditable = false;
				}

				$bCheckForPositionChanges = false;

				// CN darf niemals editierbar sein #6675
				$bPositionsEditable = false;

				// Provision ist aber immer editierbar (beim Editieren der CN nur mit Recht) #6675
				if(
					$sAction !== 'creditnote_edit' || // Kein strpos, da edit in Credit steckt
					Ext_Thebing_Access::hasRight('thebing_invoice_document_refresh_always')
				) {
					$bCreditnoteCellsEditable = true;
				}

				break;
			case 'creditnote_subagency':

				$sView			= 'creditnote';
				$bGroup			= $oInquiry->hasGroup();

				$sDocumentType = $sType;
	
				// wenn noch keine, dann rechne prov. aus da items von brutto rechnung kommen
				if(
					$bRefreshCreditnote === true || 
					!$document->exist()
				) {
					$oVersion->bCalculateProvisionNew = true;
				} else{
					$oVersion->bCalculateProvisionNew = false;
					//$bPositionsEditable = false;
				}

				$bCheckForPositionChanges = false;

				// CN darf niemals editierbar sein #6675
				$bPositionsEditable = false;

				// Provision ist aber immer editierbar (beim Editieren der CN nur mit Recht) #6675
				if(
					$sAction !== 'creditnote_subagency_edit' || // Kein strpos, da edit in Credit steckt
					Ext_Thebing_Access::hasRight('thebing_invoice_document_refresh_always')
				) {
					$bCreditnoteCellsEditable = true;
				}

				break;
			case 'invoice_text':
			case 'invoice_current':

				if($sType == 'invoice_text') {
					$bPositionsEditable = false;
					$bCheckForPositionChanges = false;
				} elseif($sType == 'invoice_current') {
					$bPositionsEditable = true;
					$bCheckForPositionChanges = false;
				}

				$sDocumentType = $document->type;

				if(strpos($sDocumentType, 'netto') === false){
					$sView				= 'gross';
					$bGroup				= $oInquiry->hasGroup();
				} else {
					$sView				= 'net';
					$bGroup				= $oInquiry->hasGroup();
				}

				if(strpos($sDocumentType, 'storno') !== false){
					if(
						$oInquiry->hasNettoPaymentMethod()
					) {
						$sView = 'net';
					}

				}
				if(strpos($sDocumentType, 'diff') !== false){
					$bDiffEdit = true;
				}

				break;

			case 'group_invoice':

				if(
					$oInquiry->payment_method == 1 ||
					$oInquiry->payment_method == 3
				) {
					$sDocumentType	=	'group_brutto';
					$sView = 'gross';
				} else {
					$sDocumentType	=	'group_netto';
					$sView = 'net';
				}

				break;

			case 'storno':

				$sDocumentType = 'storno';
				$sView = 'gross';

				if(strpos($sDocumentType, 'storno') !== false){
					if(
						$oInquiry->hasNettoPaymentMethod()
					) {
						$sView = 'net';
					}

				}

				$bGroup = $oInquiry->hasGroup();

				/*
				 * Sonderfall Stornierung
				 * Das hier muss ein leeres Objekt mit type=storno sein, damit die Items korrekt generiert werden.
				 */
				$sourceDocument = $oInquiry->newDocument('storno');

				break;

			case 'brutto_diff':
			case 'brutto_diff_special':
			case 'brutto_diff_partial':
			case 'proforma_brutto_diff':

				$bGroup = $oInquiry->hasGroup();
				
				$sDocumentType = $sType;

				// Typ ist nur fürs Icon
				if($sType === 'brutto_diff_partial') {
					$sDocumentType = 'brutto_diff';
				}

				$bNoPacketPrices = true;
				$sView = 'gross';
				$bDiffEdit = false;
				if($oVersion->id <= 0){
					//nur beim aktualisieren inaktive changes mitholen, nicht beim neu anlegen einer differenzrechnung
					$bDiffEdit = true;
				}

				break;

			case 'netto_diff':
			case 'proforma_netto_diff':

				$bGroup = $oInquiry->hasGroup();
				
				$sDocumentType = $sType;
				$sView = 'net';
				$bNoPacketPrices = true;
				$bDiffEdit = false;
				if($oVersion->id <= 0){
					//nur beim aktualisieren inaktive changes mitholen, nicht beim neu anlegen einer differenzrechnung
					$bDiffEdit = true;
				}

				break;

			case 'credit_brutto':

				$sView = 'gross';

				$bGroup				= $oInquiry->hasGroup();

				// Leres Doc erstellen damit die pos. neu geladen werden
				$document = $oInquiry->newDocument();
				$sDocumentType = 'credit_brutto';

				break;

			case 'credit_netto':

				$sView = 'net';

				$bGroup				= $oInquiry->hasGroup();

				// Leres Doc erstellen damit die pos. neu geladen werden
				$document			= $oInquiry->newDocument();
				$sDocumentType		= 'credit_netto';

				break;
			case 'additional_document':

				$sDocumentType		= 'additional_document';

				// Falls das Dokument editiert wird hole keine ID von bestehenden Doc.
				if($document->id > 0) {

					$iDocument = $document->id;

					// Ist nur bei reloadPositionsTable vorhanden
					if(!empty($this->iInvoiceSelectDocumentId)) {
						$iDocument = $this->iInvoiceSelectDocumentId;
					}

				} else {

					if($oTemplate->canShowInquiryPositions()) {

						// Wird durch reloadPositionsTable gesetzt (bei neuen Dokumenten ist ja auch noch kein Template gewählt)
						if(empty($this->iInvoiceSelectDocumentId)) {
							throw new RuntimeException('No iInvoiceSelectDocumentId for additional_document!');
						}

						$iDocument = $this->iInvoiceSelectDocumentId;

					} else {

						/**
						 * Letztes Dokument holen für z.B. LOA
						 * Hier wurde das letze additional_document gesucht, das war falsch!
						 * Für die Rechnungspositionen muss man doch das letzte Rechnungsdokumente suchen
						 */
						// TODO Das ist eigentlich nur noch als Fallback drin, da $oVersion unten immer verwendet wird
						$iDocument = (int)$oInquiry->getDocuments('invoice', false);

					}

				}

				$bGroup				= $oInquiry->hasGroup();
				
				// Netto darstellung
				if($oTemplate->inquirypositions_view == 3) {
					$sView = 'net';
				// Brutto darstellung
				} elseif($oTemplate->inquirypositions_view == 2) {
					$sView = 'gross';
				} else {
					// Wenn "nach zahlungsmethode"
					// Schauen ob netto
					if(
						$oInquiry->hasNettoPaymentMethod()
					){
						$sView = 'net';
					// Sonst brutto
					} else {
						$sView = 'gross';
					}
				}

				if($iDocument > 0) {

					$document	= new Ext_Thebing_Inquiry_Document($iDocument);
					$oVersion			= $document->getLastVersion();

				} else {
					// Kunde hat noch keine Rechnungsdoc also ein leeres für die Items auslesen
					if(!$document instanceof Ext_Thebing_Inquiry_Document) {
						$document = $oInquiry->newDocument($sDocumentType);
					}
					$oVersion = $document->newVersion();
				}

				// Nicht editierbar
				$bPositionsEditable = false;
				$bCheckForPositionChanges = false;

				break;
			default:
				throw new LogicException('Unknown type: '.$sType);
		}

		// »Richtiges« Dokument überprüfen
		$oDocumentReleaseCheck = $document;
		if(isset($oCreditNote)) {
			// Bei einer CN ist die CN mal $oInquiryDocument (edit), mal nicht (neu)
			$oDocumentReleaseCheck = $oCreditNote;
		}

		$taxCategoryEditable = false;

		if(
			$bNegate || (
				$oDocumentReleaseCheck instanceof Ext_Thebing_Inquiry_Document && (
					$oDocumentReleaseCheck->isReleased() ||
					$oDocumentReleaseCheck->is_credit == 1
				)
			)
		) {
			// Wenn Gutschrift oder Dokument freigegeben, dann dürfen keine Positionen bearbeitbar sein
			$bPositionsEditable = false;
			$bCreditnoteCellsEditable = false;
		} elseif(
			Ext_Thebing_Access::hasRight('thebing_invoice_document_refresh_always') &&
			$sDocumentType !== 'additional_document' &&
			$sDocumentType !== 'creditnote'
		) {
			$bPositionsEditable = true;
		}

		// Tax category is not editable if no ts_bookings_invoices-vat_selection right, or if it is edit and no thebing_invoice_document_refresh_always right.
		if (Ext_Thebing_Access::hasRight('ts_bookings_invoices-vat_selection')) {
			$taxCategoryEditable = true;
			if ($sAction == 'edit_invoice' && !Ext_Thebing_Access::hasRight('thebing_invoice_document_refresh_always')) {
				$taxCategoryEditable = false;
			}
		}

		$sPositionHtml = '';

		if(!($oVersion instanceof Ext_Thebing_Inquiry_Document_Version)) {

			$sError = L10N::t('Das Laden der Rechnungspositionen war fehlerhaft. Bitte wenden Sie sich an den Support.', $oGui->gui_description);

			$oErrorDialog = new Ext_Gui2_Dialog();
			$oError = $oErrorDialog->createNotification(L10N::t('Fehler', $oGui->gui_description), $sError, 'error');
			$sPositionHtml = $oError->generateHTML();

			error('Keine Version');

			return $sPositionHtml;

		}

		//Version festhalten um sie bei getColumns zu verwenden, wir müssen wissen welche Steuerneinstellungen bei 
		//der Version gespeichert wurden
		$this->oVersion		= $oVersion;

		// Bei Gutschriften die Positiontable auf readonly stellen
		if(
			$sType == 'netto' &&
			$bNegate == true
		) {
			$bPositionsEditable = false;
		}
		
		// Spalten holen
		$aPositionColumns	= $this->getColumns($sView, $bGroup, false, $bPositionsEditable, $bCreditnoteCellsEditable);
		$iPositionColumns	= count($aPositionColumns);

		// Steuerkategorien
		$aTaxCategories = array(0 => '');
		$aTaxCategories += Ext_TS_Vat::getCategories(true, $oSchool->id);

		$aTaxCategoryData = Ext_TS_Vat::getCategories(false, $oSchool->id, $oVersion->date);

		$iSchoolTaxSetting = $oSchool->getTaxStatus();

		$oSchool = $oInquiry->getSchool();
		$bUseGroupView = false;
		if($oSchool->split_group_positions == 1) {
			$bUseGroupView = true;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Differenzrechnung, Gutschrift und Storno

		$bDiff = $bCredit = $bStorno = false;

		if(
			(
				$sDocumentType == 'brutto_diff' ||
				$sDocumentType == 'proforma_brutto_diff' ||
				$sDocumentType == 'netto_diff' ||
				$sDocumentType == 'proforma_netto_diff' ||
				$sDocumentType == 'brutto_diff_special'
			) &&
			!$this->bNegate
		) {
			$bDiff = true;
		}

		if(
			(
				strpos($sDocumentType, 'credit') !== false &&
				strpos($sDocumentType, 'creditnote') === false
			) ||
			$this->bNegate ||
			(
				$document instanceof Ext_Thebing_Inquiry_Document &&
				$document->is_credit == 1
            ) ||
			(
				$sourceDocument instanceof Ext_Thebing_Inquiry_Document &&
				$sourceDocument->is_credit == 1
			)
		) {
			$bCredit = true;
		}

		if(strpos($sDocumentType, 'storno') !== false) {
			$bStorno = true;
		}

		$oVersion->bDiffEdit = $bDiffEdit;
		$oVersion->sLanguage = $this->sLanguage;
		$oVersion->sAction = $this->sAction;

		// Sprache der Items im Cache setzen
		$oGui->setDocumentPositionsLanguage($this->sLanguage);

		$oVersion->setGui($oGui);

		// Wenn Cache noch nicht initialisiert, Positionen holen
		$bInitialized = $this->oGui->getDocumentPositionsInitialized();
		
		if(
			$_REQUEST['debug'] ||
			// Da es nun ein Select für das Dokument gibt, darf es hier keinen Cache geben!
			$sType === 'additional_document'
		) {
			$bInitialized = false;
			$this->oGui->resetDocumentPositions();
		}

		if(!$bInitialized) {

			if($oInquiry->hasGroup()) {
				$oVersion->setInquiry($oInquiry);
				$oVersion->getGroupItemsForDialog($sourceDocument, $sType, $bDiff, $bCredit, $bCheckForPositionChanges);
			} else {
				$iPartialInvoice = $this->iPartialInvoice;
				if(
					!$iPartialInvoice &&
					$sType === 'brutto_diff_partial'
				) {
					// Checkbox nicht ausgewählt, aber Button Teilrechnung – alles auf einmal abrechnen
					$iPartialInvoice = 2;
				}

				// Bei Teilrechnung alte Diff-Implementierung nicht ausführen
				if($iPartialInvoice) {
					$bDiff = false;
				}

				$oVersion->setInquiry($oInquiry);
				$oVersion->getItemsForDialog($sourceDocument, $sType, $bDiff, $bCredit, $bCheckForPositionChanges, true, false, $iPartialInvoice, $this->oPaymentConditionService);
			}
			$this->oGui->setDocumentPositionsInitialized(true);
			
			$this->aPositionsTooltips = $oVersion->aItemTooltips;
			
		}

		// Negieren, falls nötig (Gutschriften)
		// Plus: Nur die Positionen von neuen Versionen negieren!
		if(
			$bNegate &&
			!$oGui->getOption('document_positions_negated') && (
				$oVersion->id < 1 ||
				!$this->bIsCredit
			)
		) {
			// Wird jetzt im Cache negiert
			$oGui->negateDocumentPositions();

			// Da die umgerechneten Werte in einen Cache geschrieben werden, würden sie bei jedem zweiten Mal
			//	wieder falsch konvertert werden. Dafür ist dieser Flag da! #4841
			$oGui->setOption('document_positions_negated', true);
		}

		//immer aus dem cache die items bilden, egal ob $bInstanceCache auf true oder false steht,
		$aItems = array();
		$aPositionsInstance = (array)$oGui->getDocumentPositions();

		if($oInquiry->hasGroup()) {
			$aItems = $oVersion->mergeItemsForGroup($aPositionsInstance, false, false, false);
		} else {
			foreach($aPositionsInstance as $aSubPositions){
				foreach($aSubPositions as $aSubPosition){
					$aItems[] = $aSubPosition;
				}
			}
		}

		## START Nur Text editierbar machen
		if(!$bPositionsEditable) {

			// additional_documents dürfen NIE editierbar sein
			// 2 Nicht editierbar aber werden mitgeschickt
			// 3 Nicht editierbar und werden NICHT mitgeschickt
			$iDisableTyp = 2;

			if(
				$sType == 'additional_document' &&
				$sTemplateType != 'document_loa'
			) {
				$iDisableTyp = 3;
			}

			// Bei CN readonly, aber Bearbeitung der Beschreibung zulassen
			if($sType == 'creditnote') {
				$iDisableTyp = 2;
			}

			foreach((array)$aItems as $iKey => $aItem){
				$aItems[$iKey]['editable'] = $iDisableTyp;

				foreach((array)$aItem['items'] as $iSubKey => $aSubItem){
					$aItems[$iKey]['items'][$iSubKey]['editable'] = $iDisableTyp;
				}

			}
			//Exta pos löschen
			// Weg, da hier korrekt gespeicherte Pos verloren gingen!
			//array_pop($aItems);
		}
		## ENDE

		// Saisonfehler anzeigen //////////////////////
		$aSaisonErrors = $oVersion->aSeasonErrors;

		if(!empty($aSaisonErrors)){

			$sErrorMessage = '<p>';
			$aSaisonErrorDuplicates = array();
			foreach((array)$aSaisonErrors as $aSaisonError){
				if(!isset($aSaisonErrorDuplicates[$aSaisonError['type']])) {
					$sErrorMessage .= L10N::t($aSaisonError['type'], $oGui->gui_description). ' ' . L10N::t('liegt außerhalb jeder Saison!', $oGui->gui_description) . '<br/>';
					$aSaisonErrorDuplicates[$aSaisonError['type']] = 1;
				}
			}
			$sErrorMessage .= '</p>';
			$oErrorDialog = new Ext_Gui2_Dialog();
			$oError = $oErrorDialog->createNotification(L10N::t('Es ist ein Fehler aufgetreten', $oGui->gui_description), $sErrorMessage);
			$sPositionHtml .= $oError->generateHTML();
		}
		////////////////////////////////////////////////

		// Special Hinweis /////////////////////////////
		if(!empty($oVersion->aSpecialPositions)){
			$aSpecials = array();
			// Specials bestimmen die greifen
			$oUl = new Ext_Gui2_Html_Ul();
			foreach((array)$oVersion->aSpecialPositions as $aData){
				foreach((array)$aData['block'] as $iBlockId){
					$oSpecialBlock = Ext_Thebing_Special_Block_Block::getInstance($iBlockId);
					$oSpecial = $oSpecialBlock->getSpecial();
					if(!in_array($oSpecial->id, $aSpecials)){
						$aSpecials[] = $oSpecial->id;

						$oLi = new Ext_Gui2_Html_Li();
						$oLi->setElement($oSpecial->name);
						$oUl->setElement($oLi);
					}

				}
			}
			$sError = $oUl->generateHTML();
			$oErrorDialog = new Ext_Gui2_Dialog();
			$oError = $oErrorDialog->createNotification(L10N::t('Es werden folgende Angebote berücksichtigt:', $oGui->gui_description), $sError, 'hint');
			$sPositionHtml .= $oError->generateHTML();

//			if(
//				$this->iPartialInvoice ||
//				$sType === 'brutto_diff_partial'
//			) {
//				$oError = $oErrorDialog->createNotification($oGui->t('Achtung'), $oGui->t('Specials werden bei Teilrechnungen nicht beachtet!'), 'error');
//				$sPositionHtml .= $oError->generateHTML();
//			}

		}
		////////////////////////////////////////////////

		// Generelle Fehler //////////////////////////////////

		$sPositionHtml .= $this->getVersionErrors($oVersion, $oSchool, $oGui, $aItems);
		/////////////////////////////////////////////////////

		$sPositionHtml .= '<div class="DocumentPositionDiv">';

		$sPositionHtml .= $this->writeTableHead($aPositionColumns);

		$iLastNewPositionId = 0;
		$aTotalAmounts = array();

		$iCount = 1;
		// Hinzufügen der Rechnungspositionszeilen
		$bExtraPositionButton = false;

		$oRow = new Ext_Thebing_Document_Positions_Row();
		if($oSchool) {
			$oRow->iSchoolId = $oSchool->id;
		}
		$oRow->iLastNewPositionId = $iLastNewPositionId;
		$oRow->iTemplateId = $iTemplateId;
		$oRow->bPositionsEditable = $bPositionsEditable;
		$oRow->bCommissionEditable = $bCreditnoteCellsEditable;
		$oRow->taxCategoryEditable  = $taxCategoryEditable;

		// Unsichtbare Leerzeile ergänzen: additional_general + extraPosition
		$aEmptyItem = array();
		$aEmptyItem['invisible']	= true;
		$aEmptyItem['editable']		= 1;
		$aEmptyItem['position_key'] = 'XXX';
		$aEmptyItem['status']		= 'new';
		$aEmptyItem['calculate']    = 1;
		// Leistungszeitraum muss immer vorhanden sein. Falls leer, setzt JS das Versionsdatum (wie früher updateItemCache).
		$aEmptyItem['index_from'] = $oInquiry->service_from;
		$aEmptyItem['index_until'] = $oInquiry->service_until;
		$aItems[] = $aEmptyItem;

		$iFirstCourseStart = 0;

		foreach((array)$aItems as $aItem) {

			// Diff Rechnungspos. dürfen nicht editierbar sein

			if(
				!isset($aItem['editable']) ||
				$aItem['editable'] == 1
			) {
				$iEditable = 1;
				$bExtraPositionButton = true;
			}else {
				$iEditable = (int)$aItem['editable'];
			}

			/*
			 * @todo Wofür ist das? Das passiert doch eigentlich schon in der buildItems().
			 */
			$iDefault = 0;
//			if(
//				!$this->bIsCredit &&
//				!$this->bNegate &&
//				isset($aItem['tax_category']) &&
//				$aItem['tax_category'] == 0
//			) {
//				$iDefault = $this->_getDefaultTaxCategory($aItem, $oSchool, $oInquiry);
//			}

			$oRow->aPosition = $aItem;
			$oRow->aPositionColumns = $aPositionColumns;
			$oRow->aTaxCategories = $aTaxCategories;
			$oRow->iDefaultTaxCategory = $iDefault;
			$oRow->iEditable = $iEditable;

			$sPositionHtml .= $oRow->generateHtml($aTotalAmounts, $aTaxAmounts);

			// Berechne frühesten Kursstart für Hiddenfeld wird für finalpay_due benötigt (js)
			if(
				$aItem['type'] == 'course'
			){
				if($iFirstCourseStart == 0){
					$iFirstCourseStart = (int)$aItem['from'];
				}elseif(
					$aItem['from'] != 0 &&
					$aItem['from'] < $iFirstCourseStart
				){
					$iFirstCourseStart = (int)$aItem['from'];
				}

			}

			$iCount++;
		}

		$sPositionHtml .= '</tbody>';

		/**
		 * Footer
		 * Wenn Steuern inklusive, Brutto, Steuern, Netto
		 * Wenn Steuern exklusive, Netto, Steuern, Brutto
		 * Anzahlung, Restzahlung
		 */
		$sPositionHtml .= '<tfoot>';

		/**
		 * Gesamtzeilen
		 * Vor-Ort
		 * Vor-Anreise
		 * Gesamt
		 */

		if($iSchoolTaxSetting === 1) {
			$sSumRowLabelSuffix = $oGui->t('brutto');
		} elseif($iSchoolTaxSetting === 2) {
			$sSumRowLabelSuffix = $oGui->t('netto');
		} else {
			$sSumRowLabelSuffix = '';
		}

		$aSumRows = array(
			'on_site'=>$oGui->t('Summe Vor-Ort'),
			'pre_arrival'=>$oGui->t('Summe Vor-Anreise'),
			'total'=>$oGui->t('Gesamtsumme')
		);


		foreach($aSumRows as $sSumRowKey=>$sSumRowLabel) {

			$sTag = 'td';
			if($sSumRowKey == 'total') {
				$sTag = 'th';
			}

			$sPositionHtml .= '<tr id="row_sum_'.$sSumRowKey.'">';
			$sPositionHtmlTotal = '';
			$iLabelColspan = 0;
			$bLabelColspan = true;
			$iSumLabelColspan = 0;
			$bSumLabelColspan = true;
			foreach($aPositionColumns as $sField => $aPositionColumn) {

				$sL10N = $aPositionColumn['label'];

				$mAmount = '&nbsp;';
				if(isset($aTotalAmounts[$sField])) {
					$bLabelColspan = false;
					$mAmount = $aTotalAmounts[$sField];
				}

				if($aPositionColumn['total_amount_column'] === true) {
					$bSumLabelColspan = false;
				}

				if($bSumLabelColspan === true) {
					$iSumLabelColspan++;
				}

				if($bLabelColspan === false) {
					$sPositionHtmlTotal .= '<'.$sTag.' class="total_amount_'.$sSumRowKey.'_'.$sField.' amount" id="total_amount_'.$sSumRowKey.'_'.$sField.'">';
					$sPositionHtmlTotal .= $mAmount;
					// Button zum Refresh der Gesamtzeile
					$sPositionHtmlTotal .= '</'.$sTag.'>';
				} else {
					$iLabelColspan++;
				}
			}

			$sPositionHtml .= '<'.$sTag.' colspan="'.(int)$iLabelColspan.'">'.$sSumRowLabel.' '.$sSumRowLabelSuffix.'</'.$sTag.'>';
			$sPositionHtml .= $sPositionHtmlTotal;
			$sPositionHtml .= '</tr>';

		}

		/**
		 * Zeilen für Steuern und Betrag anzeigen
		 */
		if($iSchoolTaxSetting !== 0) {

			foreach((array)$aTaxCategoryData as $aTaxCategory) {

				$sLabel = $sTax = '';

				if($iSchoolTaxSetting === 1) {
					$sLabel = $this->oGui->t('Steuersatz "{name}"');
				} else {
					$sLabel = $this->oGui->t('Steuersatz "{name}"');
				}

				$sLabel = str_replace('{name}', $aTaxCategory['name'], $sLabel);
				$sTax .= Ext_Thebing_Format::Number($aTaxCategory['rate'], null, $oSchool->id, false, 3) . ' %';

				$sPositionHtml .= '<tr id="tax_row_'.(int)$aTaxCategory['id'].'">';
				$sPositionHtml .= '<td colspan="'.(int)$iSumLabelColspan.'">';
				$sPositionHtml .= $sLabel;
				$sPositionHtml .= '</td>';

				$iPositionColumn = 0;

				foreach((array)$aPositionColumns as $sField => $aPositionColumn) {
					$iPositionColumn++;

					if($iPositionColumn <= $iSumLabelColspan) {
						continue;
					}

					if($aPositionColumn['total_amount_column'] === true) {
						$sPositionHtml .= '<td class="amount" id="tax_amount_'.(int)$aTaxCategory['id'].'">';
						$sPositionHtml .= '0,00';
						$sPositionHtml .= '</td>';
					} elseif($sField == 'tax_category') {
						$sPositionHtml .= '<td class="amount">';
						$sPositionHtml .= $sTax;
						$sPositionHtml .= '</td>';
					} else {
						$sPositionHtml .= '<td>';
						$sPositionHtml .= '</td>';
					}

				}

				$sPositionHtml .= '</tr>';

			}

			// Die Gesamtsumme mit / ohne die Steuern
			if($iSchoolTaxSetting === 1) {
				$sSumRowLabel = $oGui->t('Gesamtsumme netto');
				$sTaxRowPrefix = $oGui->t('');
			} elseif($iSchoolTaxSetting === 2) {
				$sSumRowLabel = $oGui->t('Gesamtsumme brutto');
			} else {
				$sSumRowLabel = $oGui->t('Gesamtsumme');
			}

			$sPositionHtml .= '<tr id="total_amount_row">';
			$sPositionHtml .= '<th colspan="'.(int)$iSumLabelColspan.'">'.$sSumRowLabel.'</th>';
			$iPositionColumn = 0;
			foreach($aPositionColumns as $sField => $aPositionColumn) {
				$iPositionColumn++;

				if($iPositionColumn <= $iSumLabelColspan) {
					continue;
				}

				if($aPositionColumn['total_amount_column'] === true) {
					$sPositionHtml .= '<th class="total_amount_ amount" id="total_amount">';
					$sPositionHtml .= '0,00';
					$sPositionHtml .= '</th>';
				} else {
					$sPositionHtml .= '<th>';
					$sPositionHtml .= '</th>';
				}

			}
			$sPositionHtml .= '</tr>';

		}

		if(
			$sDocumentType !== 'additional_document' &&
			$this->oPaymentConditionService
		) {

			if($oVersion->exist()) {
				$oVersion->getPaymentTerms();
			}

			$bDisableCells = false;
			if(
				(
					!$bPositionsEditable &&
					!$bCreditnoteCellsEditable && (
						// Bei einer Gutschrift darf das trotzdem editiert werden, solange nicht freigegeben #8990
						!$this->bIsCredit || (
							$oVersion->id > 0 &&
							$oDocumentReleaseCheck->isReleased()
						)

					)
				) ||
				!Ext_Thebing_Access::hasRight('thebing_invoice_payment_modalities')
			) {
				$bDisableCells = true;
			}

			$aPaymentTermRows = $this->oPaymentConditionService->generateRows($aItems);

			foreach($aPaymentTermRows as $oRow) {

				if($bDisableCells) {
					$oRow->bDisabled = true;
				}

				$sPositionHtml .= '<tr class="paymentterm_row" data-disable-payment-condition="'.((int)$bDisableCells).'">'; // data-disable = Select deaktivieren
				$sPositionHtml .= '<td colspan="'.(int)$iSumLabelColspan.'">';

				$aDateInputOptions = [
					'id' => '',
					'name' => 'paymentterm[date][]',
					'value' => $oFormat->formatByValue($oRow->dDate),
					'calendar_row_class' => 'input-group-sm'
				];

				$oDiv = new Ext_Gui2_Html_Div();
				$oDiv->class = 'pull-right';

				if($oRow->bDisabled) {
					$oDateHidden = new Ext_Gui2_Html_Input();
					$oDateHidden->type = 'hidden';
					$oDateHidden->name = 'paymentterm[date][]';
					$oDateHidden->value = $oFormat->formatByValue($oRow->dDate);
					$oDiv->setElement($oDateHidden);
					$aDateInputOptions['disabled'] = true;
				}

				$oDiv->setElement($oDialog->createSaveField('calendar', $aDateInputOptions));
				$sPositionHtml .= $oDiv->generateHTML();
				$sPositionHtml .= '<span>'.$oRow->sLabel.'</span>';

				$oHidden = new Ext_Gui2_Html_Input();
				$oHidden->type = 'hidden';
				$oHidden->name = 'paymentterm[type][]';
				$oHidden->value = $oRow->sType;
				$sPositionHtml .= $oHidden->generateHTML();

				$oHidden = new Ext_Gui2_Html_Input();
				$oHidden->type = 'hidden';
				$oHidden->name = 'paymentterm[setting_id][]';
				$oHidden->value = $oRow->iSettingId;
				$sPositionHtml .= $oHidden->generateHTML();

				$sPositionHtml .= '</td>';

				$iPositionColumn = 0;
				foreach($aPositionColumns as $sField => $aPositionColumn) {
					$iPositionColumn++;

					if($iPositionColumn <= $iSumLabelColspan) {
						continue;
					}

					// Dynamisch mal hier, mal da
					if($aPositionColumn['total_amount_column'] === true) {

						$sPositionHtml .= '<td>';

						$oAmountInput = new Ext_Gui2_Html_Input();
						$oAmountInput->bAllowReadOnly = false;
						$oAmountInput->name = 'paymentterm[amount][]';
						$oAmountInput->value = Ext_Thebing_Format::Number($oRow->fAmount, null, $oSchool->id, true, 2);
						$oAmountInput->class = 'keyup form-control input-sm amount pull-right';
						$oAmountInput->style = 'width: '.($aPositionColumn['width'] - 2).';';

						if($oRow->sType === 'final') {
							$oAmountInput->style .= 'cursor: not-allowed';
						}

						// Berechnungslogik für JS-Implementierung
						if(!empty($oRow->aSettingData)) {
							$oAmountInput->setDataAttribute('setting', htmlentities(json_encode($oRow->aSettingData)));
						}

						if($oRow->bDisabled) {
							$oAmountHidden = clone $oAmountInput;
							$oAmountHidden->type = 'hidden';
							$sPositionHtml .= $oAmountHidden->generateHTML();
							$oAmountInput->disabled = true;
						}

						$sPositionHtml .= $oAmountInput->generateHTML();
						$sPositionHtml .= '</td>';
					} else {
						$sPositionHtml .= '<td>';
						if(
							$aPositionColumn['type'] === 'actions' &&
							!$oRow->bDisabled
						) {
							$sPositionHtml .= '<i class="fa fa-minus-circle pointer"></i>&nbsp;';
							$sPositionHtml .= '<i class="fa fa-plus-circle pointer"></i>';
						}
						$sPositionHtml .= '</td>';
					}

				}

				$sPositionHtml .= '</tr>';

			}

		}

		$sPositionHtml .= '</tfoot>';

		$sPositionHtml .= '</table>';

		if(
			$bExtraPositionButton &&
			$bPositionsEditable &&
			$sType != 'additional_document' &&
			(
				!is_object($document) ||
				!$document->isReleased()
			) && (
				strpos($sAction, 'edit') === false ||
				Ext_Thebing_Access::hasRight('thebing_invoice_document_refresh_always')
			)
		) {

			$aGeneralCosts = $oSchool->getGeneralCosts(true, $oCurrency->id, null, $this->sLanguage);

			$aGeneralCosts = Ext_Thebing_Util::addEmptyItem($aGeneralCosts, $oGui->t('Leere Position'));

			$oAddIcon = new Ext_Gui2_Html_Button();
			$oAddIcon->title = $oGui->t('Position hinzufügen');
			$oAddIcon->id = 'add_position_button';
			$oAddIcon->class = 'btn btn-default btn-sm';
			$oAddIcon->setElement('<i class="fa '.Ext_Thebing_Util::getIcon("add").'"></i>');

			$aFieldOptions = array(
				'id' => 'add_position',
				'name' => 'add_position',
				'style'=>'',
				'select_options'=>$aGeneralCosts,
				'input_div_addon'=> $oAddIcon,
			);
			$oRow = $oDialog->createRow($oGui->t('Neue Rechnungsposition'), 'select', $aFieldOptions);

			// Button für Extrapositionszeile.
			$sPositionHtml .= $oRow->generateHTML();

		}

		$sPositionHtml .= '</div>';

		$sPositionHtml .= $this->getLegend();

		if($sDialogId !== null) {
			$sPositionHtml = str_replace('saveid[', 'save['.$oGui->hash.']['.$sDialogId.'][', $sPositionHtml);
		}
	 
		return $sPositionHtml;

	}

	public function writeTableHead($aPositionColumns, $sClass='tblMainDocumentPositions') {

		$sPositionHtml = "";
		$sPositionHtml .= '<table class="table table-bordered tblDocumentTable tblDocumentPositions '.$sClass.'" style="background-color:#FFF; table-layout:fixed;">';
		$sPositionHtml .= '<colgroup>';

		foreach($aPositionColumns as $sField=>$aPositionColumn) {
			$iWidth = str_replace('px', '', $aPositionColumn['width']);
			$sPositionHtml .= '<col class="'.$sField.'" '.((isset($aPositionColumn['remaining']))?'data-remaining="'.$aPositionColumn['remaining'].'"':'').' style="width:'.($iWidth+17).'px;" />';
		}
		$sPositionHtml .= '</colgroup>';
		
		$sPositionHtml .= '<thead><tr>';

		foreach($aPositionColumns as $sField=>$aPositionColumn) {

			$sL10N = $aPositionColumn['label'];
			$sTitle = $aPositionColumn['title'];

			$sClass = '';
			if($aPositionColumn['small']) {
				$sClass .= ' small';
			}
			$sClass = trim($sClass);

			$sPositionHtml .= '<th title="'.$sTitle.'" class="'.$sClass.'">';
			$sPositionHtml .= $sL10N;
			$sPositionHtml .= '</th>';
		}
		$sPositionHtml .= '</tr></thead>';

		$sPositionHtml .= '<tbody id="position_container">';

		return $sPositionHtml;

	}

	/**
	 * @return string
	 */
	public function getLegend() {

		$sLegend = '<div class="divLegend clearfix" style="">';

		$sLegend .= '<div><strong>'.$this->oGui->t('Legende').': </strong>&nbsp;</div>';

		$aStyles = Ext_Thebing_Document_Positions_Row::getStyles();
		foreach($aStyles as $aStyle) {
			$sLegend .= '<div style="float: left">'.$aStyle['label'].'</div> <div class="colorkey" style="background-color: '.$aStyle['color'].'" ></div>';
		}

		if($this->sCurrencySign) {
			$sLegend .= '<div style="float: left; font-weight: bold;">'.sprintf($this->oGui->t('Alle Beträge werden in "%s" dargestellt.'), $this->sCurrencySign).'</div>';
		}

		$sLegend .= '<div class="divCleaner"></div></div>';

		return $sLegend;

	}

	/**
	 * @param $sView
	 * @param $bGroup
	 * @param bool $bSubPositions
	 * @param bool $bEditable
	 * @param bool $bCreditnoteCellsEditable
	 * @return array
	 */
	public function getColumns($sView, $bGroup, $bSubPositions = false, $bEditable = true, $bCreditnoteCellsEditable = false) {

		$oSchool = $this->oInquiry->getSchool();
		$iSchoolTaxSetting = $oSchool->getTaxStatus();

		$oGui = $this->oGui;

		$sDescription = $oGui->gui_description;

		if(empty($sDescription)){
			$sDescription = $oSchool->fetchInterfaceLanguage();
		}

		$sTotalAmountColumn = 'amount_total';

		$sDisabled = '';
		if(!$bEditable) {
			$sDisabled = 'disabled="disabled"';
		}

		$aPositionColumns = array();

		if(
			!$bSubPositions &&
			$bEditable
		) {
			$aPositionColumns[] = 'sortable';
		}

		if(
			$sView == 'net' ||
			$sView == 'creditnote'
		) {

			if(
				$bGroup &&
				!$bSubPositions
			) {
				$aPositionColumns[] = 'count';
			}

			if(!$bSubPositions) {
				$aPositionColumns[] = 'initalcost';
			}

			$aPositionColumns[] = 'onPdf';

			if(
				$bGroup &&
				$bSubPositions
			) {
				$aPositionColumns[] = 'label';
			}

			$aPositionColumns[] = 'description';
			$aPositionColumns[] = 'amount';
			
			$aPositionColumns[] = 'amount_discount';
			$aPositionColumns[] = 'amount_discount_amount';

			$aPositionColumns[] = 'amount_after_discount';
			
			$aPositionColumns[] = 'amount_provision';
			
		} else {

			if(
				$bGroup &&
				!$bSubPositions
			) {
				$aPositionColumns[] = 'count';
			}

			if(!$bSubPositions) {
				$aPositionColumns[] = 'initalcost';
			}

			$aPositionColumns[] = 'onPdf';

			if(
				$bGroup &&
				$bSubPositions
			) {
				$aPositionColumns[] = 'label';
			}

			$aPositionColumns[] = 'description';
			$aPositionColumns[] = 'amount';

			$aPositionColumns[] = 'amount_discount';
			$aPositionColumns[] = 'amount_discount_amount';
			
		}

		if(
			is_object($this->oVersion) &&
			$this->oVersion instanceof Ext_Thebing_Inquiry_Document_Version &&
			$this->oVersion->id > 0 && //in der getTable wird die Version leer definiert falls keine Version gefunden wurde
			strpos($this->sAction, 'refresh') === false
		){
			$iVatMode = (int)$this->oVersion->tax;
		}else{
			//wenn neu oder aktualisieren, dann Schuleinstellung übernehmen
			$iVatMode = (int)$iSchoolTaxSetting;
		}

		// Exklusive Steuern
		if($iVatMode === 2) {
			$aPositionColumns[] = 'amount_total_net';
			$sTotalAmountColumn = 'amount_total_net';
			$sTaxLabel = $this->oGui->t('zzgl. Steuern');
		// Inklusive Steuern
		} elseif($iVatMode === 1) {
			$aPositionColumns[] = 'amount_total_gross';
			$sTotalAmountColumn = 'amount_total_gross';
			$sTaxLabel = $this->oGui->t('inkl. Steuern');
		} else {
			$aPositionColumns[] = 'amount_total';
			$sTotalAmountColumn = 'amount_total';
		}

		// In der Agenturgutschrift ist die Provisionsspalte die Summenspalte
		if($sView == 'creditnote') {
			$sTotalAmountColumn = 'amount_provision';
		}
		
		// Wenn keine Steuern, auch kein Select für Steuern anzeigen
		if(
			$iSchoolTaxSetting !== 0 &&
			!$bSubPositions
		) {
			$aPositionColumns[] = 'tax_category';
		}

		if(
			!$bSubPositions //&&
//			$bEditable // Muss für neue Zahlungsbedingungen vorhanden sein
		) {
			$aPositionColumns[] = 'actions';
		}

		$aAllColumns = array();
		$aAllColumns['sortable']				= array('label'=>'&nbsp;', 'sum'=>false, 'width'=>'11px', 'title'=>$this->oGui->t('Sortierung'), 'small'=>true);
		$aAllColumns['count']					= array('label'=>$this->oGui->t('Anzahl'), 'sum'=>false, 'width'=>'55px', 'title'=>'', 'small'=>true);
		$aAllColumns['initalcost']				= array('label'=>$this->oGui->t('VK'), 'sum'=>false, 'width'=>'20px', 'title'=>$this->oGui->t('Vor-Ort-Kosten'), 'small'=>true);
		$aAllColumns['onPdf']					= array('label'=>$this->oGui->t('Aktiv'), 'sum'=>false, 'width'=>'30px', 'title'=>$this->oGui->t('Auf PDF ausgeben'), 'small'=>true);
		$aAllColumns['label']					= array('label'=>$this->oGui->t('Schüler'), 'sum'=>false, 'width'=>'200px', 'title'=>'', 'small'=>true);
		$aAllColumns['description']				= array('label'=>$this->oGui->t('Beschreibung'), 'sum'=>false, 'width'=>'auto', 'title'=>'', 'small'=>true);
		$aAllColumns['amount']					= array('label'=>$this->oGui->t('Kundenbetrag'), 'format'=>'number', 'sum'=>true, 'width'=>'85px', 'title'=>'', 'small'=>true);
		$aAllColumns['amount_provision']		= array('label'=>$this->oGui->t('Provision'), 'format'=>'number', 'sum'=>true, 'width'=>'88px', 'title'=>'', 'small'=>true);
		$aAllColumns['amount_after_discount']	= array('label'=>$this->oGui->t('Betrag n. Rbt.'), 'title'=>$this->oGui->t('Kundenbetrag nach Rabatt'), 'format'=>'number', 'sum'=>true, 'width'=>'85px', 'small'=>true);
		$aAllColumns['amount_discount']			= array('label'=>$this->oGui->t('%'), 'format'=>'number', 'sum'=>false, 'width'=>'45px', 'title'=>$this->oGui->t('Rabatt in %'), 'small'=>true);
		$aAllColumns['amount_discount_amount']	= array('label'=>$this->oGui->t('Rabatt'), 'format'=>'number', 'sum'=>true, 'width'=>'70px', 'title'=>$this->oGui->t('Rabatt'), 'small'=>true);
		$aAllColumns['amount_total']			= array('label'=>$this->oGui->t('Gesamt'), 'format'=>'number', 'sum'=>true, 'width'=>'85px', 'title'=>$this->oGui->t('Gesamt'), 'readonly'=>true, 'small'=>true);
		$aAllColumns['amount_total_net']		= array('label'=>$this->oGui->t('Gesamt netto'), 'format'=>'number', 'sum'=>true, 'width'=>'85px', 'title'=>$this->oGui->t('Gesamt netto'), 'readonly'=>true, 'small'=>true);
		$aAllColumns['amount_total_gross']		= array('label'=>$this->oGui->t('Gesamt brutto'), 'format'=>'number', 'sum'=>true, 'width'=>'85px', 'title'=>$this->oGui->t('Gesamt brutto'), 'readonly'=>true, 'small'=>true);
		$aAllColumns['tax_category']			= array('label'=>$sTaxLabel, 'sum'=>false, 'width'=>'75px', 'title'=>'', 'small'=>true);
		$aAllColumns['actions']					= array('label'=>'&nbsp;', 'sum'=>false, 'width'=>'30px', 'title'=>'', 'small'=>true);

		// Aktionen über alle Rows (nur im normalen Dialog anzeigen, nicht im Positionsdialog (Anzahl))
		if(!$bSubPositions) {
			$aAllColumns['onPdf']['label'] .= '<br><div style="text-align: center;"><input id="onPdf_toggle_all" class="change" type="checkbox" '.$sDisabled.'></div>';
		}

		// Logik: Siehe $bCreditnoteCellsEditable in getTable (#6675-5)
		if(
			$bEditable ||
			$bCreditnoteCellsEditable
		) {
			$aAllColumns['amount_provision']['label'] .= '<button id="amount_commission_refresh_all" type="button" class="btn btn-default btn-xs pull-right click" title="'.$oGui->t('Alle Provisionen neu berechnen').'"><i class="fa fa-refresh"></i></button>';
		}

		// Diese Spalte wird für die Berechnung von An- und Restzahlung verwendet
		$aAllColumns[$sTotalAmountColumn]['total_amount_column'] = true;

		// Gesamtspalte in Objekt schreiben, damit man die später abfragen kann
		$this->sTotalAmountColumn = $sTotalAmountColumn;
		
		$aReturn = array();
		foreach((array)$aPositionColumns as $iKey=>$sPositionColumn) {
			$aReturn[$sPositionColumn] = $aAllColumns[$sPositionColumn];
			$aReturn[$sPositionColumn]['type'] = $sPositionColumn;
		}

		// Bei Creditnote/Gutschriften readonly setzen
		if($sView == 'creditnote') {
			$aReturn['amount']['readonly'] = true;
			$aReturn['amount_discount']['readonly'] = true;
		}
		
		$iTotalWidth = 1138;
		$iRemainingWidth = 0;
		foreach($aReturn as $sKey=>$aColumn) {

			$iWidth = (int)str_replace('px', '', $aColumn['width']);

			if(
				$aColumn['width'] != 'auto' &&
				$iWidth > 0
			) {
				$iTotalWidth -= $iWidth;
				$iTotalWidth -= 17;
				$iRemainingWidth += $iWidth+17;
			}

		}

		$aReturn['description']['width'] = ($iTotalWidth-17).'px';
		$aReturn['description']['remaining'] = $iRemainingWidth;

		return $aReturn;
	}

	/**
	 * Position Cache aktualisieren
	 * @param array $aPositions 
	 */
	public function updatePositions(array $aPositions) {
		global $_VARS;
		
		$oLanguage = new \Tc\Service\Language\Frontend($this->sLanguage);
		
		$sDocType = $_VARS['document_type'];

		// Positionen aus Cache laden
		$aPositionsInstance = (array)$this->oGui->getDocumentPositions();

		// Unterschiede zu übermittelten Positionen rausfinden
		$aDeletedPositions = array_diff_key($aPositionsInstance, $aPositions);
			
		// Prüfen, ob sich die Sprache geändert hat
		$bUpdatePositionDescriptions = false;
		if($this->oGui->getDocumentPositionsLanguage() != $this->sLanguage) {

			// Positionsbeschreibungen aktualisieren
			$bUpdatePositionDescriptions = true;

		}

		//Positionen aktualisieren
		foreach($aPositions as $mKey => $aPosition) {

			if($mKey === 'XXX'){
				continue;
			}

			$aPositionInstance = $aPositionsInstance[$mKey];
			if(
				!empty($aPositionInstance) &&
				is_array($aPositionInstance)
			) {
				$aFirstSubPositionInstance = reset($aPositionInstance);

				// Inquiryobjekt setzen
				if($aFirstSubPositionInstance['inquiry_id'] > 0) {
				
					// Sicherstellen, das auch Enquiries hiermit arbeiten können T2621
					$sClassname = 'Ext_TS_Inquiry';
					if($this->oInquiry instanceof Ext_TS_Enquiry){
						$sClassname = 'Ext_TS_Enquiry';
					}
					$this->oInquiry = call_user_func(array($sClassname, 'getInstance'), $aFirstSubPositionInstance['inquiry_id']);

				}
			}

			$aPosition['position_key'] = $mKey;

			// Sprache verändert? Description aktualisieren
			if(
				$bUpdatePositionDescriptions && (
					(
						$aPosition['status'] == 'edit' || // Hier steht neuerdings eine ID drin?
						$aPosition['status'] == 'new'
					) || (
						$sDocType !== 'credit' &&
						$sDocType !== 'creditnote'
					)
				)
			) {
				$aPosition['description'] = $this->getPositionDescription($aPosition, $aFirstSubPositionInstance);
			}

			// Prüfen ob sich Discount oder die Sprache verändert hat und die Beschreibung aktualisiert werden muss
			if(
				!empty($aPosition['amount_discount']) &&
				(
					$bUpdatePositionDescriptions ||
					$aFirstSubPositionInstance['amount_discount'] != $aPosition['amount_discount']
				)
			) {
				$aPosition['description_discount'] = Ext_Thebing_Document::getDiscountDescription($aPosition, $oLanguage);
			}

			$this->updatePosition($mKey, $aPosition);

		}

		/**
		 * Nicht mehr vorhandene Positionen löschen
		 */
		foreach($aDeletedPositions as $mDeletedPositionKey => $mData){
			$this->deletePosition($mDeletedPositionKey);
		}
	}

	/**
	 * Erzeugt für alle möglichen Positionen die Beschreibung neu
	 * Gibt Fehler zurück für alle nicht neu generierten Beschreibungen
	 * @param array $aPosition
	 * @param array $aPositionInstance
	 * @return string
	 */
	public function getPositionDescription($aPosition, $aPositionInstance) {

		$oLanguage = new \Tc\Service\Language\Frontend($this->sLanguage);
	    $oSchool = $this->oInquiry->getSchool();
		$sDescription = '';

		$aAdditional = json_decode($aPosition['additional'], true);
		if(!is_array($aAdditional)) {
			throw new InvalidArgumentException('$aPosition[additional] is not an array!');
		}
		
		// Stornoitems nicht übersetzen
		switch($aPosition['type']) {
			case 'course':

				if($aPosition['parent_type'] != 'cancellation') {
					$oInquiryCourse = $this->oInquiry->getServiceObject('course', $aPosition['type_id']);
					$sDescription = $oInquiryCourse->getLineItemDescription($oLanguage);
				}else{
					$sDescription = $this->_getCancellationDescriptionByPosition($aPosition, $aPositionInstance);
				}

				break;
			case 'accommodation':

				if($aPosition['parent_type'] != 'cancellation') {
					$oInquiryAccommodation = $this->oInquiry->getServiceObject('accommodation', $aPosition['type_id']);
					$this->setExtraNightsHelper($oInquiryAccommodation);
					$sDescription = $oInquiryAccommodation->getLineItemDescription($oLanguage);
				}else{
					$sDescription = $this->_getCancellationDescriptionByPosition($aPosition, $aPositionInstance);
				}

				break;
			case 'insurance':

				$oInquiryInsurance = $this->oInquiry->getServiceObject('insurance', $aPosition['type_id']);
				$sDescription = $oInquiryInsurance->getLineItemDescription($oLanguage);

				break;
			
			case 'activity':
				if($aPosition['parent_type'] != 'cancellation') {
					$inquiryActivity = $this->oInquiry->getServiceObject('activity', $aPosition['type_id']);
					$sDescription = $inquiryActivity->getLineItemDescription($oLanguage);
				} else {
					$sDescription = $this->_getCancellationDescriptionByPosition($aPosition, $aPositionInstance);
				}

				break;
			case 'additional_accommodation':
			
				if(
					$aPosition['parent_type'] != 'cancellation' &&
					!empty($aPositionInstance['additional_info']['weeks']) &&
					!empty($aPositionInstance['additional_info']['count_accommodations'])
				) {
					$oInquiryAccommodation = $this->oInquiry->getServiceObject('accommodation', $aPosition['parent_booking_id']);
					$sDescription = $oInquiryAccommodation->getAdditionalCostInfo($aPosition['type_id'], $aPositionInstance['additional_info']['weeks'], $aPositionInstance['additional_info']['count_accommodations'], $oLanguage);
				}else{
					$sDescription = $this->_getCancellationDescriptionByPosition($aPosition, $aPositionInstance);
				}
				
				break;
			case 'additional_course':
			
				if(
					$aPosition['parent_type'] != 'cancellation' &&
					!empty($aPositionInstance['additional_info']['weeks']) &&
					!empty($aPositionInstance['additional_info']['count_courses'])
				) {
					$oInquiryCourse = $this->oInquiry->getServiceObject('course', $aPosition['parent_booking_id']);
					$sDescription = $oInquiryCourse->getAdditionalCostInfo($aPosition['type_id'], $aPositionInstance['additional_info']['weeks'], $aPositionInstance['additional_info']['count_courses'], $oLanguage);
				}else{
					$sDescription = $this->_getCancellationDescriptionByPosition($aPosition, $aPositionInstance);
				}

				break;
			case 'additional_general':
			
				if($aPosition['parent_type'] != 'cancellation') {
					$oAdditionalCost = Ext_Thebing_School_Additionalcost::getInstance($aPosition['type_id']);
					$sDescription = $oAdditionalCost->getName($this->sLanguage);
				}else{
					$sDescription = $this->_getCancellationDescriptionByPosition($aPosition, $aPositionInstance);
				}

				break;
			case 'extra_nights':

				// Geht nur wenn Information vorhanden
				if($aPositionInstance['nights'] > 0) {
					$oInquiryAccommodation = $this->oInquiry->getServiceObject('accommodation', $aPosition['type_id']);
					$this->setExtraNightsHelper($oInquiryAccommodation);
					$sType = $aAdditional['nights_type'];
					$sDescription = $oInquiryAccommodation->getExtraNightInfo($aPositionInstance['nights'], $oLanguage, $sType);
				}

				break;
			case 'extra_weeks':

				// Geht nur wenn Information vorhanden
				if($aPositionInstance['additional_info']['extra_weeks'] > 0) {
					$oInquiryAccommodation = $this->oInquiry->getServiceObject('accommodation', $aPosition['type_id']);
					$this->setExtraNightsHelper($oInquiryAccommodation);
					$sType = $aAdditional['extra_weeks_type'];
					$sDescription = $oInquiryAccommodation->getExtraWeekInfo($aPositionInstance['additional_info']['extra_weeks'], $oLanguage, $sType);
				}
				
				break;
			case 'storno':
				// Geht nur wenn Information vorhanden
				if(
					isset($aPositionInstance['additional_info']['cancellation_type']) &&
					isset($aPositionInstance['additional_info']['fee_type']) &&
					isset($aPositionInstance['additional_info']['fee_value'])
				) {
					
					$sDescription = $this->_getCancellationDescriptionByPosition($aPosition, $aPositionInstance);
				}

				break;
			case 'transfer':
			
				if($aPosition['type_id'] > 0) {

					$oInquiryTransfer = $this->oInquiry->getServiceObject('transfer', $aPosition['type_id']);
					$sDescription = $oInquiryTransfer->getName(null, 1, $oLanguage);

				} elseif(
					isset($aPositionInstance['additional_info']['transfer_arrival_id']) &&
					isset($aPositionInstance['additional_info']['transfer_departure_id'])	
				) {

					$oInquiryTransferArrival = $this->oInquiry->getServiceObject('transfer', $aPositionInstance['additional_info']['transfer_arrival_id']);
					$oInquiryTransferDeparture = $this->oInquiry->getServiceObject('transfer', $aPositionInstance['additional_info']['transfer_departure_id']);

					$sDescription = $oLanguage->translate('Anreise und Abreise');
					$sDescription .= ' ('.Ext_Thebing_Format::LocalDate($oInquiryTransferArrival->transfer_date).', '.Ext_Thebing_Format::LocalDate($oInquiryTransferDeparture->transfer_date).')';

				}

				break;
			case 'special':

				$oObject = null;
				switch($aPositionInstance['additional_info']['type']) {
					case 'course':
						$oObject = $this->oInquiry->getServiceObject('course', $aPositionInstance['additional_info']['type_id']);
						break;
					case 'accommodation':
						$oObject = $this->oInquiry->getServiceObject('accommodation', $aPositionInstance['additional_info']['type_id']);
						break;
					case 'transfer':
						$oObject = $this->oInquiry->getServiceObject('transfer', $aPositionInstance['additional_info']['type_id']);
						break;
				}

				if(is_object($oObject)){
					$sDescription = $oObject->getSpecialInfo($oSchool->id, $this->sLanguage);
				}

				break;
			case 'extraPosition':
				
				break;
		}

		if(empty($sDescription)) {
			$this->aErrors['translate_position'][] = $aPosition['description'];
			$sDescription = $aPosition['description'];
		}

		return $sDescription;

	}

	/**
	 * @param Ext_TS_Service_Interface_Accommodation $oJourneyAccommodation
	 * @return Ext_TS_Service_Accommodation_Helper_Extranights
	 */
	protected function setExtraNightsHelper(Ext_TS_Service_Interface_Accommodation $oJourneyAccommodation) {

		$aExtraNightsCurrent = (array)$this->oInquiry->getExtraNights('forCalculate', $oJourneyAccommodation);
		$aExtraWeeks = (array)$this->oInquiry->getExtraWeeks('forCalculate', $oJourneyAccommodation);

		// Helper-Klasse für Extranächte
		$oHelper = new Ext_TS_Service_Accommodation_Helper_Extranights($oJourneyAccommodation);
		$oHelper->aExtraNights = $aExtraNightsCurrent;
		$oHelper->aExtraWeeks = $aExtraWeeks;

		$oJourneyAccommodation->setExtranightHelper($oHelper);

		return $oHelper;

	}

	protected function _getCancellationDescriptionByPosition($aPosition, $aPositionInstance)
	{
		//cachen damit nicht pro position immer das gleiche erneut aufgebaut wird im konstruktor
		$oCancellationAmount = Ext_Thebing_Cancellation_Amount::getInstance($this->oInquiry, null, $this->sLanguage, true);

		//@todo: CancellationDynamic WDBasic erstellten und nur noch in additional info die DynamicStornoId speichern
		//dann kann man sich die ersten 3 Parameter sparen...
		//getDescription dann per Objekt lösen, bzw die Methode auch getInfo() nennen...
		$sDescription = $oCancellationAmount->getDescription(
			$aPositionInstance['additional_info']['fee_value'],
			$aPositionInstance['additional_info']['fee_type'],
			$aPositionInstance['additional_info']['cancellation_type'],
			$aPosition['type'],
			$aPosition['parent_booking_id'],
			$aPosition['type_id']
		);

		return $sDescription;
	}

	/**
	 * Rechnungsposition in der Instanz aktualisieren
	 * @param type $mPositionKey
	 * @param type $aVariables
	 * @return type
	 */
	public function updatePosition($mPositionKey, $aVariables) {
		global $_VARS;

		$iResidual = 0;
		$iResidualCommission = 0;
		$iPositionCount = 0;

		$oGuiData = $this->oGui->getDataObject();

		$aPositions = $this->oGui->getDocumentPosition($mPositionKey);

		$oSchool = $this->oInquiry->getSchool();

		// Formatieren
		if(isset($aVariables['amount'])){
			$aVariables['amount'] = Ext_Thebing_Format::convertFloat($aVariables['amount'], $oSchool->id);
		}

		if(isset($aVariables['amount_discount'])){
			$aVariables['amount_discount'] = Ext_Thebing_Format::convertFloat($aVariables['amount_discount'], $oSchool->id);
		}

		if(isset($aVariables['amount_provision'])){
			$aVariables['amount_provision'] = Ext_Thebing_Format::convertFloat($aVariables['amount_provision'], $oSchool->id);
		}

		if(isset($aVariables['amount_net'])){
			$aVariables['amount_net'] = Ext_Thebing_Format::convertFloat($aVariables['amount_net'], $oSchool->id);
		}

		// Wenn es eine neue Position ist
		if(!is_array($aPositions)) {

			$aGuides = array();
			$aOthers = array();

			$oGroup = $this->oInquiry->getGroup();

//			if($oGroup instanceof Ext_Thebing_Inquiry_Group) {
//				$aGuides = $oGroup->getInquiries(true, true, 2);
//				$aOthers = $oGroup->getInquiries(2, true, 2);
//				$iCountGuides = count($aGuides);
//				$iCountOthers = count($aOthers);
//			}
			/*else*/if($oGroup instanceof Ext_TS_Enquiry_Group) {
				$aGuideList = $oGroup->getGuides();
				foreach($aGuideList as $oGuide) {
					$aGuides[] = array(
						'id' => $this->oInquiry->id,
						'lastname' => $oGuide->lastname,
						'firstname' => $oGuide->firstname,
						'customer_id' => $oGuide->id,
						'guide' => true
					);
				}
				$aMemberList = $oGroup->getNotGuideMembers();
				foreach($aMemberList as $oMember) {
					$aOthers[] = array(
						'id' => $this->oInquiry->id,
						'lastname' => $oMember->lastname,
						'firstname' => $oMember->firstname,
						'customer_id' => $oMember->id,
						'guide' => false
					);
				}
				$iCountGuides = count($aGuides);
				$iCountOthers = count($aOthers);
			}
			elseif ($oGroup instanceof Ext_Thebing_Inquiry_Group) {
				$aGuides = $oGroup->getInquiries(true, true, 2);
				$aOthers = $oGroup->getInquiries(2, true, 2);
				$iCountGuides = count($aGuides);
				$iCountOthers = count($aOthers);
			}
			else {
				$aTravellers = $this->oInquiry->getTravellers();
				if(!empty($aTravellers)) {
					// Spezial Gruppen-Enquiry behandlung
					$oContact = reset($aTravellers);
				} else{
					$oContact = $this->oInquiry->getCustomer();
				}
				$aOthers[] = array(
					'id'=> $this->oInquiry->id,
					'lastname' => $oContact->lastname,
					'firstname' => $oContact->firstname,
					'customer_id' => $oContact->id,
					'guide' => false
				);
				$iCountOthers = 1;
				$iCountGuides = 0;
			}

			if(
				$aVariables['type'] == 'additional_general' ||
				$aVariables['type'] == 'extraPosition'
			) {

				if($aVariables['type'] == 'additional_general') {
					// Erste Saison
					$iSaisonId = $this->oInquiry->getSaisonFromFirstService();

					// Zusatzkosten
					$aAdditionalCosts = $oSchool->getGeneralCosts(2, $this->oInquiry->getCurrency(), $iSaisonId);

					$aAdditionalCostsData = $aAdditionalCosts[(int)$aVariables['type_id']];

					if($aAdditionalCostsData['group_option'] == 1) {
						$iCount = $iCountGuides + $iCountOthers;
						$iFactor = $iCountGuides + $iCountOthers;
						$aLabels = array_merge($aGuides, $aOthers);
					} else if($aAdditionalCostsData['group_option'] == 2) {
						$iCount = $iCountOthers;
						$iFactor = $iCountOthers;
						$aLabels = $aOthers;
					} else {
						$iCount = $iCountGuides + $iCountOthers;
						$iFactor = 1;
						$aLabels = array_merge($aGuides, $aOthers);
					}
				} else {
					$iCount = $iCountGuides + $iCountOthers;
					$iFactor = 1;
					$aLabels = array_merge($aGuides, $aOthers);
				}

				foreach($aLabels as $aLabel) {

					$aPosition = array();
					$aPosition['type'] = $aVariables['type'];
					$aPosition['type_id'] = $aVariables['type_id'];
					$aPosition['onPdf'] = 1;
					$aPosition['calculate'] = 1;
					$aPosition['label'] = $aLabel['lastname'].', '.$aLabel['firstname'];
					if($aLabel['guide']) {
						$aPosition['label'] .= ' ('.$this->oGui->t('Guide').')';
					}
					$aPosition['status'] = 'new';
					$aPosition['inquiry_id'] = $aLabel['id'];
					$aPosition['data'] = $aLabel;
					$aPosition['index_from'] = $aVariables['index_from'];
					$aPosition['index_until'] = $aVariables['index_until'];

					//falls position_key dabei ist behalten, weil bei reloadPositionstable die Positionen
					//geupdated werden
					if(isset($aVariables['position_key'])){
						$aPosition['position_key'] = $aVariables['position_key'];
					}

					$aPositions[] = $aPosition;
				}

			}

		}
		
		if(!is_array($aPositions)) {
			$aPositions = (array)$aPositions;
		}

		// Übergebene Werte verarbeiten
		$iCount = 0;
		$iCountAll = 0;
		$fTotalAmount = 0;
		$fTotalAmountCommission = 0;

		foreach($aPositions as &$aPosition) {

			$aPosition['initalcost'] = $aVariables['initalcost'];
			if(!isset($aVariables['check_on_pdf'])){
				$aPosition['onPdf'] = $aVariables['onPdf'];
				// Nur erhöhen wenn auch auf PDF
				$iCount++;
			}else{
				if(
					$aPosition['onPdf'] == 1
				)
				{
					$iCount++;
				}
			}
			$iCountAll++;

			$aPosition['tax_category'] = $aVariables['tax_category'];

			if(isset($aVariables['position'])) {
				$aPosition['position'] = $aVariables['position'];
			}

			if(!empty($aVariables['description'])){
				$aPosition['description'] = $aVariables['description'];
			}

			if(isset($aVariables['amount_discount'])) {
				$aPosition['amount_discount'] = (float)$aVariables['amount_discount'];
			}
			
			// Rabattbeschreibung aktualisieren wenn Rabattbeschreibung übergeben wurde
			if(isset($aVariables['description_discount'])) {
				$aPosition['description_discount'] = $aVariables['description_discount'];
			}

			$fTotalAmount += (float)$aPosition['amount'];
			$fTotalAmountCommission += (float)$aPosition['amount_provision'];

		}

		$fNewCommission = false;
		if(
			$fTotalAmountCommission != (float)$aVariables['amount_provision']
		) {

			if($iCount > 0){
				$fNewCommission = round((float)$aVariables['amount_provision'] / $iCount, 2);
			}
			
			$iResidualCommission = (float)$aVariables['amount_provision'] - ($fNewCommission * $iCount);

		}
		
		$fNew = false;
		if(
			isset($aVariables['amount']) &&
			$fTotalAmount != (float)$aVariables['amount']
		) {
			if($iCount > 0) {
				$fNew = (float)$aVariables['amount'] / $iCount;
				$fNew = round($fNew, 2);
				$iResidual = (float)$aVariables['amount'] - ($fNew * $iCount);
			}
		}

		// Beträge anpassen
		foreach($aPositions as &$aItem) {

			if(!isset($aVariables['check_on_pdf'])){
				// Neue Provision verteilen
				if($fNewCommission !== false) {

					$aItem['amount_provision'] = $fNewCommission;

					if($iPositionCount >= ($iCount-1)) {
						$aItem['amount_provision'] += $iResidualCommission;
					}

				}

				// Neuen Betrag verteilen
				if($fNew !== false) {
					
					$aItem['amount'] = $fNew;

					if($iPositionCount >= ($iCount-1)) {
						$aItem['amount'] += $iResidual;
					}

				}

				// Provision bei Discount umrechnen
				if(
					$aItem['amount_provision'] != 0 &&
					$aItem['amount_discount'] != 0
				) {
					// 100 % Rabatt abfangen, da /0
					if(bccomp($aItem['amount_discount'], 100, 5) === 0) {
						$aItem['amount_provision'] = $aItem['amount_provision'];
					} else {
						$aItem['amount_provision'] = $aItem['amount_provision'] / (1 - ($aItem['amount_discount'] / 100));
					}
				}
				
				$aItem['amount_net'] = $aItem['amount'] - $aItem['amount_provision'];

			}

			$aItem['count'] = $iCount;
			$aItem['count_all'] = $iCountAll;

			$iPositionCount++;
		}

		$this->oGui->setDocumentPosition($mPositionKey, $aPositions);

		return $aPositions;

	}

	/**
	 * @param $mPositionKey
	 */
	public function deletePosition($mPositionKey){
		$this->oGui->deleteDocumentPosition($mPositionKey);
	}

	/**
	 * @param Ext_Thebing_Inquiry_Document_Version $oVersion
	 * @param Ext_Thebing_School $oSchool
	 * @param Ext_Gui2 $oGui
	 * @param array $aItems
	 * @return string
	 */
	public function getVersionErrors(Ext_Thebing_Inquiry_Document_Version $oVersion, Ext_Thebing_School $oSchool, Ext_Gui2 &$oGui, array $aItems) {

		$sHtml = '';

		// Infodaten (keine fehler)
		$aCourseSeasonFound			= (array)$oVersion->aErrors['course_season_found'];
		$aAccommodationSeasonFound	= (array)$oVersion->aErrors['accommodation_season_found'];
		unset($oVersion->aErrors['course_season_found']);
		unset($oVersion->aErrors['accommodation_season_found']);
		
		$bShowError = false;
		
		$oUl = new Ext_Gui2_Html_Ul();

		if(!empty($oVersion->aErrors)) {

			foreach((array)$oVersion->aErrors as $sErrorKey => $mValue) {

				// IDs dürfen nicht mehrfach drin stehen, damit Meldungen nicht doppelt rauskommen
				$mValue = array_unique($mValue);
				
				switch($sErrorKey) {
						
					case 'wrong_unit_number':
					case 'wrong_week_number':
					case 'extraunit_start_gt_position':
					case 'extraweek_start_gt_position':
						if(is_array($mValue)){
							
							foreach((array)$mValue as $iCourseId) {
								$oCourse = Ext_Thebing_Tuition_Course::getInstance((int)$iCourseId);
								$sInfo = '';
								if($sErrorKey == 'wrong_unit_number'){
									$sInfo = L10N::t('Keine Lektionsstruktur für Lektionskurs "%" gefunden.', $oGui->gui_description);
								}elseif($sErrorKey == 'wrong_week_number'){
									$sInfo = L10N::t('Keine Wochenstruktur für Kurs "%" gefunden.', $oGui->gui_description);
								}elseif($sErrorKey == 'extraunit_start_gt_position') {
									$sInfo = L10N::t('Es wurde keine gültige Zusatzlektion für den Kurs "%" gefunden. Die Preisberechnung ist eventuell nicht korrekt.', $oGui->gui_description);
								} elseif($sErrorKey == 'extraweek_start_gt_position') {
									$sInfo = L10N::t('Es wurde keine gültige Zusatzwoche für den Kurs "%" gefunden. Die Preisberechnung ist eventuell nicht korrekt.', $oGui->gui_description);
								}
								$sInfo = str_replace('%', $oCourse->getName($oSchool->getInterfaceLanguage()), $sInfo);
								$oLi = new Ext_Gui2_Html_Li();
								$oLi->setElement($sInfo);
								$oUl->setElement($oLi);
							}
						} else {
							$oLi = new Ext_Gui2_Html_Li();
							$oLi->setElement( L10N::t('Keine Lektionsstruktur für Lektionskurs gefunden.', $oGui->gui_description));
							$oUl->setElement($oLi);
						}
						break;
					case 'wrong_unit_season':
					case 'missing_unit_season':
						
						foreach((array)$mValue as $iCourseId) {
							$oCourse = Ext_Thebing_Tuition_Course::getInstance((int)$iCourseId);
							if($sErrorKey == 'wrong_unit_season') {
								$sInfo = L10N::t('Der Lektionskurs "%" liegt in mehreren Saisons. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);
							} elseif($sErrorKey == 'missing_unit_season') {
								$sInfo = L10N::t('Der Lektionskurs "%" liegt außerhalb von Saisons. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);
							}
							$sInfo = str_replace('%', $oCourse->getName($oSchool->getInterfaceLanguage()), $sInfo);
							$oLi = new Ext_Gui2_Html_Li();
							$oLi->setElement($sInfo);
							$oUl->setElement($oLi);
						}
						break;
					case 'course_season_not_found':
	
						foreach((array)$mValue as $iCourseId){
							$oInquiryCourse = $this->oInquiry->getServiceObject('course', $iCourseId);
							$oCourse = $oInquiryCourse->getCourse();
			
							// Prüfen ob Kurs teilweise in saisons liegt
							if(in_array($oInquiryCourse->id, $aCourseSeasonFound)){
								$sInfo = L10N::t('Der Kurs "%" liegt nur teilweise in Saisons. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);
							}else{
								$sInfo = L10N::t('Der Kurs "%" liegt außerhalb von Saisons. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);
							}
							$sInfo = str_replace('%', $oCourse->getName($oSchool->getInterfaceLanguage()), $sInfo);
							
							$oLi = new Ext_Gui2_Html_Li();
							$oLi->setElement($sInfo);
							$oUl->setElement($oLi);
						}
						break;
					case 'insurance_season_not_found':
						foreach((array)$mValue as $sInsuranceName){
							$sInfo = L10N::t('Die Versicherung "%" liegt außerhalb von Saisons. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);

							$sInfo = str_replace('%', $sInsuranceName, $sInfo);

							$oLi = new Ext_Gui2_Html_Li();
							$oLi->setElement($sInfo);
							$oUl->setElement($oLi);
						}
						break;
					case 'insurance_wrong_week_number':
						foreach((array)$mValue as $sInsuranceName){
							$sInfo = L10N::t('Keine Wochenstruktur für die Versicherung "%" gefunden.', $oGui->gui_description);

							$sInfo = str_replace('%', $sInsuranceName, $sInfo);

							$oLi = new Ext_Gui2_Html_Li();
							$oLi->setElement($sInfo);
							$oUl->setElement($oLi);
						}
						break;
					case 'accommodation_season_not_found':
						foreach((array)$mValue as $iAccommodationId){
							$oInquiryAccommodation = $this->oInquiry->getServiceObject('accommodation', $iAccommodationId);
	
							// Prüfen ob Kurs teilweise in saisons liegt
							if(
								in_array($oInquiryAccommodation->id, $aAccommodationSeasonFound)
							){
								$sInfo = L10N::t('Die Unterkunft "%" liegt nur teilweise in Saisons. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);
							}else{
								$sInfo = L10N::t('Die Unterkunft "%" liegt außerhalb von Saisons. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);
							}
							$sInfo = str_replace('%', $oInquiryAccommodation->getInfo($oSchool->id, $oSchool->getInterfaceLanguage(), true), $sInfo);
							
							$oLi = new Ext_Gui2_Html_Li();
							$oLi->setElement($sInfo);
							$oUl->setElement($oLi);
						}
						break;
					case 'accommodation_week_not_found':

						foreach((array)$mValue as $iAccommodationId){
							$oInquiryAccommodation = $this->oInquiry->getServiceObject('accommodation', $iAccommodationId);
	
							$sInfo = L10N::t('Es wurde eine oder mehrere Wochen für die Unterkunft "%" nicht gefunden und es existiert keine extra Woche. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);
                            $sInfo = str_replace('%', $oInquiryAccommodation->getInfo($oSchool->id, $oSchool->getInterfaceLanguage(), true), $sInfo);

                            $oLi = new Ext_Gui2_Html_Li();
                            $oLi->setElement($sInfo);
                            $oUl->setElement($oLi);
							
						}
						break;
					case 'activity_season_not_found':
						foreach((array)$mValue as $iJourneyActivityId) {
							$oJourneyActivity = $this->oInquiry->getServiceObject('activity', $iJourneyActivityId);

							$sInfo = L10N::t('Die Aktivität "%" liegt außerhalb von Saisons. Keine korrekte Preisberechnung möglich.', $oGui->gui_description);
							$sInfo = str_replace('%', $oJourneyActivity->getActivity()->getName(), $sInfo);

							$oLi = new Ext_Gui2_Html_Li();
							$oLi->setElement($sInfo);
							$oUl->setElement($oLi);
						}
						break;
				}
			}
			
			$bShowError = true;

		}
		
		if(!empty($this->aErrors)) {
			
			foreach($this->aErrors as $sErrorKey=>$mValue) {
				switch($sErrorKey) {
					case 'translate_position':
						foreach((array)$mValue as $sPositionDescription) {
							$sInfo = sprintf(L10N::t('Die Position "%s" konnte nicht übersetzt werden.', $oGui->gui_description), $sPositionDescription);
							$oLi = new Ext_Gui2_Html_Li();
							$oLi->setElement($sInfo);
							$oUl->setElement($oLi);
						}

						break;
				}
			}
			
			$bShowError = true;
			
		}

		// Sponsoring (SACM)
		if(
			$this->oInquiry instanceof Ext_TS_Inquiry &&
			$this->oInquiry->isSponsored() &&
			$this->oInquiry->sponsor_id != 0 &&
			strpos($this->sSelectedAddress, 'sponsor') !== false
		) {
			$aDates = []; /** @var Core\DTO\DateRange[] $aDates */
			$aGurantees = $this->oInquiry->getJoinedObjectChilds('sponsoring_guarantees', true);

			foreach($aGurantees as $oGurantee) {
				if(
					\Core\Helper\DateTime::isDate($oGurantee->from, 'Y-m-d') &&
					\Core\Helper\DateTime::isDate($oGurantee->until, 'Y-m-d')
				) {
					$aDates[] = new Core\DTO\DateRange(new DateTime($oGurantee->from), new DateTime($oGurantee->until));
				}
			}

			usort($aDates, function(Core\DTO\DateRange $oDateRange1, Core\DTO\DateRange $oDateRange2) {
				return $oDateRange1->from > $oDateRange2->from;
			});

			// Perioden mergen
			for($i = 0; $i < count($aDates); $i++) {

				if(
					!isset($aDates[$i]) ||
					!isset($aDates[$i + 1])
				) {
					continue;
				}

				$dTmpUntil = (clone $aDates[$i]->until)->add(new DateInterval('P1D'));

				if(
					// Ende = neuer Start oder das plus 1 Tag
					$aDates[$i]->until == $aDates[$i + 1]->from ||
					$dTmpUntil == $aDates[$i + 1]->from
				) {
					$aDates[$i]->until = $aDates[$i + 1]->until;
					unset($aDates[$i + 1]);
				}

			}

			$oSponsor = $this->oInquiry->getSponsor();

			// Prüfen, ob Zeitraum vom Item abgedeckt ist
			foreach($aItems as $aItem) {
				if(
					!empty($aItem['from']) &&
					!empty($aItem['until'])
				) {
					// Sponsor übernimmt nur Kurs oder alle Leistungen
					if(
						$oSponsor->sponsoring !== 'all' &&
						$aItem['type'] !== 'course'
					) {
						continue;
					}

					$dFrom = new DateTime($aItem['from']);
					$dUntil = new DateTime($aItem['until']);

					$bFound = false;
					foreach($aDates as $oDateRange) {
						if(
							$oDateRange->from <= $dFrom &&
							$oDateRange->until >= $dUntil
						) {
							$bFound = true;
						}
					}

					if(!$bFound) {
						$sInfo = L10N::t('Die Leistung "%" liegt außerhalb der Gültigkeit der Finanzgarantie. Die Kosten sind nicht abgedeckt.', $oGui->gui_description);
						$sInfo = str_replace('%', $aItem['description'], $sInfo);
						$oUl->setElement('<li>'.$sInfo.'</li>');
						$bShowError = true;
					}

				}
			}

		}

		if($bShowError === true) {
			$sError = $oUl->generateHTML();
			$oErrorDialog = new Ext_Gui2_Dialog();
			$oError = $oErrorDialog->createNotification(L10N::t('Achtung', $oGui->gui_description), $sError, 'hint');
			$sHtml .= $oError->generateHTML();
		}
		
		return $sHtml;

	}

	protected function _getDefaultTaxCategory($aItem, $oSchool, $oInquiry=null) {
		
		$iObjectId	= (int) $aItem['type_id'];
		$sClass	= '';
				
		switch($aItem['type']) {
			case 'course':
				$sClass = 'Ext_Thebing_Tuition_Course';				
				$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iObjectId); 
				$iObjectId = $oJourneyCourse->course_id;
				break;
			case 'accommodation':
				// Das ist die falsche Klasse, muss mal korrigiert werden. Die ID ist von der Kategorie.
				$sClass = 'Ext_Thebing_Accommodation';
				$oJourneyAccommodation = Ext_TS_Inquiry_Journey_Accommodation::getInstance($iObjectId); 
				$iObjectId = $oJourneyAccommodation->accommodation_id;
				break;
			case 'transfer':
				$sClass = 'TRANSFER';
				break;
			case 'additional_accommodation':
			case 'additional_course':				
				$sClass = 'Ext_Thebing_School_Cost';
				break;
			default:

				$sClass = '';
				break;
		}

		$iDefault = 0;
		if($sClass != '') {
			$iDefault = (int)Ext_TS_Vat::getDefaultCombination($sClass, $iObjectId, $oSchool, $oInquiry);
		}
		
		return $iDefault;
	}
	
}
