<?php

use Communication\Interfaces\Model\CommunicationSubObject;
use Core\Entity\ParallelProcessing\Stack;
use Tc\Traits\Filename;
use TsAccounting\Traits\Releaseable;

/**
 * @property $id
 * @property $created
 * @property $changed
 * @property $active
 * @property $status
 * @property $creator_id
 * @property $editor_id
 * @property $entity
 * @property $entity_id
 * @property $type
 * @property $latest_version
 * @property $numberrange_id
 * @property $document_number
 * @property $partial_invoice
 * @property $is_credit
 * @property $released
 * @property $released_student_login
 * @property $office_registered
 * @property $tax_registered
 * @property $draft
 */
class Ext_Thebing_Inquiry_Document extends Ext_Thebing_Basic implements \Communication\Interfaces\Model\HasCommunication {
	use Releaseable;
	use Filename;

	const PDF_VAT_LINES_SIMPLE = 2;
	const PDF_LINEITEM_NUMBERING = 3;
	const PDF_VAT_LINES_EXTENDED = 4;
	
	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'kolumbus_inquiries_documents';

	/**
	 * Tabellen Alias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'kid';

	/**
	 * @var string
	 */
	protected $_sPlaceholderClass = \Ext_TS_Inquiry_Document_Placeholder::class;

	/**
	 * Da hier kein JoinedObject genutzt wird, muss man die Buchung irgendwie anders zwischenspeichern können.
	 */
	protected $entityObject;

	/**
	 * @var array
	 */
	protected $_aFormat 	= array(
		'changed' => array(
			'format' => 'TIMESTAMP'
			),
		'created' => array(
			'format' => 'TIMESTAMP'
			),
		'type' => [
			'required' => true
		]
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'parent_documents' => array(
			'table' => 'ts_documents_to_documents',
			'foreign_key_field' => 'parent_document_id',
			'primary_key_field' => 'child_document_id',
			'autoload' => false,
			'on_delete' => 'no_action',
			'readonly' => true
		),
		'parent_documents_diff' => array(
			'table'				=> 'ts_documents_to_documents',
			'foreign_key_field' => 'parent_document_id',
			'primary_key_field' => 'child_document_id',
			'static_key_fields'	=> array('type' => 'diff'),
			'autoload'			=> false,
			'on_delete' => 'no_action'
		),
		'parent_documents_credit' => array(
			'table'				=> 'ts_documents_to_documents',
			'foreign_key_field' => 'parent_document_id',
			'primary_key_field' => 'child_document_id',
			'static_key_fields'	=> array('type' => 'credit'),
			'autoload'			=> false,
			'on_delete' => 'no_action'
		),
		'parent_documents_creditnote' => array(
			'table'				=> 'ts_documents_to_documents',
			'foreign_key_field' => 'parent_document_id',
			'primary_key_field' => 'child_document_id',
			'static_key_fields'	=> array('type' => 'creditnote'),
			'autoload'			=> false,
			'on_delete' => 'no_action'
		),
		'parent_documents_creditnote_subagency' => array(
			'table'				=> 'ts_documents_to_documents',
			'foreign_key_field' => 'parent_document_id',
			'primary_key_field' => 'child_document_id',
			'static_key_fields'	=> array('type' => 'creditnote_subagency'),
			'autoload'			=> false,
			'on_delete' => 'no_action'
		),
		'child_documents_creditnote' => array(
			'table' => 'ts_documents_to_documents',
			'class' => self::class,
			'foreign_key_field' => 'child_document_id',
			'primary_key_field' => 'parent_document_id',
			'static_key_fields' => array('type' => 'creditnote'),
			'autoload' => false,
			'on_delete' => 'no_action' // Eigentlich cascade, funktioniert aber nicht, weil nur in Zwischentabelle
		),
		'child_documents_creditnote_subagency' => array(
			'table' => 'ts_documents_to_documents',
			'class' => self::class,
			'foreign_key_field' => 'child_document_id',
			'primary_key_field' => 'parent_document_id',
			'static_key_fields' => array('type' => 'creditnote_subagency'),
			'autoload' => false,
			'on_delete' => 'no_action' // Eigentlich cascade, funktioniert aber nicht, weil nur in Zwischentabelle
		),
		'parent_documents_offer' => [
			'table' => 'ts_documents_to_documents',
			'foreign_key_field' => 'parent_document_id',
			'primary_key_field' => 'child_document_id',
			'static_key_fields' => ['type' => 'offer'],
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		'child_documents_offer' => [
			'table' => 'ts_documents_to_documents',
			'foreign_key_field' => 'child_document_id',
			'primary_key_field' => 'parent_document_id',
			'static_key_fields' => ['type' => 'offer'],
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		'parent_documents_cancellation' => array(
			'table'				=> 'ts_documents_to_documents',
			'foreign_key_field' => 'parent_document_id',
			'primary_key_field' => 'child_document_id',
			'static_key_fields'	=> array('type' => 'cancellation'),
			'autoload'			=> false,
			'on_delete' => 'no_action'
		),
		'parent_documents_attached_additional' => [
			'table' => 'ts_documents_to_documents',
			'foreign_key_field' => 'parent_document_id',
			'primary_key_field' => 'child_document_id',
			'static_key_fields' => array('type' => 'attached_additional'),
			'autoload' => false,
			'on_delete' => 'no_action'
		],
		'manual_creditnotes' => array(
			'table'				=> 'ts_manual_creditnotes_to_documents',
			'foreign_key_field' => 'manual_creditnote_id',
			'primary_key_field' => 'document_id',
			'autoload'			=> false,
			'class'				=> 'Ext_Thebing_Agency_Manual_Creditnote',
		),
		'release' => array(
			'table'				=> 'ts_documents_release',
			'primary_key_field' => 'document_id',
			'autoload'			=> false,
			'on_delete' => 'no_action'
		),
		'booking_stack_histories' => array(
			'table'				=> 'ts_documents_booking_stack_histories',
			'primary_key_field' => 'document_id',
			'autoload'			=> false,
			'on_delete' => 'no_action'
		),
		'gui2' => array(
			'table'				=> 'ts_documents_to_gui2',
			'primary_key_field' => 'document_id',
			'foreign_key_field' => array('name', 'set'),
			'autoload'			=> false,
			'on_delete' => 'no_action'
		),
		'payments' => array(
			'table' => 'kolumbus_inquiries_payments_documents',
			'primary_key_field' => 'document_id',
			'foreign_key_field' => 'payment_id',
			'autoload' => false,
			'on_delete' => 'no_action'
		)
	);

	protected $_aJoinedObjects = array(
		// bidirectional funktioniert mit static_key_fields nicht
//		'inquiry' => [
//			'class' => Ext_TS_Inquiry::class,
//			'key' => 'entity_id',
//			'static_key_fields'=> ['entity' => 'booking'],
//			'check_active' => true,
//			'type' => 'parent',
//			'on_delete' => 'cascade',
//			'bidirectional' => true
//		],
		'versions' => [
			'class' => 'Ext_Thebing_Inquiry_Document_Version',
			'key' => 'document_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade',
			'bidirectional' => true
		]
    );

	/**
	 * @var int
	 */
	public $iOldId = 0;

	/**
	 * @var int
	 */
	public $iSchoolId = 0;

	/**
	 * Bestimmt, ob der Nummernkreis gesperrt wird, sofern er benutzt wird
	 *
	 * @var bool
	 */
	public $bLockNumberrange = true;

	/**
	 * @var bool
	 */
	protected $bAutoGenerateNumber = true;

	public $instantiateBacktrace;

	public bool $overrideCreationAsDraft = false;

	public function __construct($iDataID = 0, $sTable = null) {
		
		parent::__construct($iDataID, $sTable);
		
		$this->instantiateBacktrace = \Util::getBacktrace();
		
	}
	
	public function __get($sField){

		Ext_Gui2_Index_Registry::set($this);
		
		if($sField == 'contact_name') {
			
			$oInquiry	= Ext_TS_Inquiry::getInstance($this->_aData['inquiry_id']);
			$oCustomer	= $oInquiry->getCustomer();
			$mValue		= $oCustomer->getName();
		
			
		} elseif($sField == 'editor_id') {
			
			// In der inquiries_documents gibt es kein editor_id Feld, weil das Dokument nie bearbeitet werden kann,
			// stattdessen kommen immer neue Versionen hinzu, darum holen wir uns den ersteller/bearbeiter aus der letzten Version
			
			$mValue	= null;
			
			$oVersion	= $this->getLastVersion();
			
			if($oVersion)
			{
				$mValue = $oVersion->user_id;
			}
			
		} else{
			$mValue	= parent::__get($sField);
		}

		return $mValue;
	}

	public function __set($name, $value) {
		
		// Dummy wegen Agenturzahlungsliste - Proforma wandeln
		if($name === 'agency_id') {
			return;
		}
		
		parent::__set($name, $value);
		
	}
	
	##########
	# Neu
	#########


	/**
	 * Create PDF for the Document
	 *
	 * @TODO Wird $oDocumentForItems noch gebraucht? Ich habe kein Vorkommen gefunden.
	 * @param bool $bFromDialog
	 * @param string $sLanguage
	 * @param mixed $oDocumentForItems
	 * @param Ext_Gui2 $oGui GUI, die _bedarfsweise_ übergeben wird
	 * @return bool|string False im Fehlerfall, ansonsten den Pfad zum PDF
	 */
	public function createPdf($bFromDialog = false, $sLanguage = null, $oDocumentForItems = null, $oGui = null, $bDirect=false) {

		$oInquiry = $this->getEntity();
		if(!($oInquiry instanceof \Ts\Interfaces\Entity\DocumentRelation)) {
			return false;
		}

		$oSchool = $oInquiry->getSchool();

		// true geht nicht, keine lust zu debuggen warum weil die version eig vorher gespeichert wird
		// TODO Das ist nicht objekt-relation, daher muss deswegen immer die Version gespeichert werden…
		$oVersion = $this->getLastVersion(false);

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($oVersion->template_id);
		$aData = [ [], [], [], ]; // wird ggf. unten neu gesetzt, wenn nicht ist das hier der Standardwert

		$iInquiryPositionsView = (int)$oVersion->canShowInquiryPositions(false, $this); 
		if($iInquiryPositionsView > 0) {

			$bGroup = false;
			if ($oInquiry instanceof \Ext_TS_Inquiry_Abstract) {
				$bGroup = $oInquiry->hasGroup();
			}

			if(is_null($oDocumentForItems)){
				$oDocumentForItems = $this;
			}

			// Items Holen ( falls das Doc. EIGENE POS. hat!! sehr wichtig!! )
			$aData = $oVersion->getItemsForPdf($oDocumentForItems, $bGroup, $oTemplate);

			if($this->type == 'additional_document') {

				// Wenn keine eigenen, hole die pos. der letzten rechnung damit diese benutzt werden können falls die vorlage diese einbindet
				if(empty($aData)) {

					// Für Additional Documents brauchen wir die Items aus der letzten Rechnung falls es Inquiry Pos gibt
					$oLastInvoiceDocument = $oInquiry->getLastDocument('invoice');

					if($oLastInvoiceDocument instanceof Ext_Thebing_Inquiry_Document) {
						$oLastInvoiceDocumentVersion = $oLastInvoiceDocument->getLastVersion();
						$aData = $oLastInvoiceDocumentVersion->getItemsForPdf($oLastInvoiceDocument, $bGroup, $oTemplate);
					}

				}

			}

		}

		$oPDF = new Ext_Thebing_Pdf_Basic($oVersion->template_id, $oSchool->id, $bFromDialog, $oGui);

		if($sLanguage !== null) {
			$oPDF->setLanguage($sLanguage);
		}

		$oPDF->createDocument($this, $oVersion, $aData);

		// Dateinamen + Pfad bauen
		$aTemp = self::buildFileNameAndPath($this, $oVersion, $oSchool);
		$sPath = $aTemp['path'];
		$sFileName = $aTemp['filename'];

		$sFilePath = $oPDF->createPDF($sPath, $sFileName, '', 'P', 'F', $bDirect);
		$this->log(Ext_Thebing_Log::DOCUMENT_PDF_CREATED, $this->_aData);

		return $sFilePath;

	}

	/**
	 * @return null|string
	 */
	public function getDocumentNumberForIndex(){
		$sNumber = $this->document_number;
		if($sNumber) {
			return $sNumber;
		}
		return null;
	}

	public static function buildFileNameAndPath(&$oDocument, &$oVersion, &$oSchool) { 

		if($oDocument->id < 1) {
			throw new RuntimeException('Es wird versucht ein PDF ohne Dokument zu speichern! ' . $oDocument->type);
		}

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($oVersion->template_id);
		$sDocNumber = $oDocument->document_number;

		$sFilenameTemplate = $oTemplate->getOptionValue($oVersion->template_language, $oSchool->id, 'filename');

		if(
			$oDocument->document_number !== '' &&
			$oTemplate && (
				$oDocument->type === 'receipt_customer' ||
				$oDocument->type === 'receipt_agency'
			)
		) {
			// Bei Bezahlbelegen besteht der Dateiname aus Template-Name + Dokument-Nr.
			$sDocNumber = Util::getCleanFilename($oTemplate->name) . '_' . Util::getCleanFilename($oDocument->document_number);
					
		} elseif(
			$sDocNumber === '' ||
			$oDocument->type === 'additional_document'
		) {

			// Template-Name + (Dokument-Nr. +) Dokument-ID
			$sDocNumber = '';

			if($oTemplate) {
				$sDocNumber .= Util::getCleanFilename($oTemplate->name).'_';
			}

			if($sDocNumber !== '') {
				$sDocNumber .= $oDocument->document_number.'_';
			}

			$sDocNumber .= $oDocument->id;

		}

		if(empty($sFilenameTemplate)) {

			$sFileName = Util::getCleanFilename($sDocNumber);
			
			if(
				strpos($oDocument->type, 'netto') !== false
			) {
				$sFileName .= 'net';
			}

			// version anhängen
			$sFileName .= '_v'.(int)$oVersion->version;

		} else {

			$entity = $oDocument->getEntity();
			
			if($entity instanceof Ext_TS_Inquiry) {
			
				$oTraveller = $entity->getTraveller();
				$aReplace = [
					$oTraveller->firstname,
					$oTraveller->lastname,
					$oDocument->document_number,
					$oDocument->id,
					$oVersion->version,
					(new DateTime($oVersion->date))->format('Ymd')
				];
			} elseif($entity instanceof Ext_Thebing_Teacher) {
				$aReplace = [
					$entity->firstname,
					$entity->lastname,
					$oDocument->document_number,
					$oDocument->id,
					$oVersion->version,
					(new DateTime($oVersion->date))->format('Ymd')
				];
			}
			
			$aPattern = [
				'{firstname}',
				'{surname}',
				'{document_number}',
				'{id}',
				'{version}',
				'{date}'
			];
			

			$sFileName = str_replace($aPattern, $aReplace, $sFilenameTemplate);
			if ($oDocument->draft) {
				$sFileName .= '_'.\L10N::t('entwurf');
			}
			$sFileName = Util::getCleanFilename($sFileName);

		}

		$sPath = $oSchool->getSchoolFileDir().'/inquirypdf/';

		// Pfad um Typ erweitern damit jeder Nummernkreis gekapselt ist
		$oDate = new WDDate();
		$iYear = $oDate->get(WDDate::YEAR);
		$iMonth = $oDate->get(WDDate::MONTH);

		$sPath .= $oDocument->type . '/' . $iYear . '/' . $iMonth . '/';

		$bCheck = Util::checkDir($sPath);
		
		if(!$bCheck){
			throw new RuntimeException('PDF Pfad konnte nicht erstellt werden! ' . $sPath);
		}

		$newName = $oDocument->addCounter($sFileName, $sPath);

		$aBack = array('path' => $sPath, 'filename' => $newName);

		return $aBack;
	}

	public function cloneDocument($sToType = 'brutto', $sComment = '', $iNumberRangeId = false, Ext_TS_Inquiry $oInquiry = null, $sDocNumber = '', $iTemplateId = 0, $createPdf=true, DateTimeInterface $date = null) {

		if(strpos($this->type, 'proforma') === false) {
			throw new LogicException('Invalid document type for cloneDocument (was '.$this->type.', '.$this->id.')');
		}

		$aAdditional = [
			'numberrange_id' => (int)$iNumberRangeId,
			'document_number' => $sDocNumber
		];

		$oNewDocument = $this->createCopy2($sToType, $sComment, $oInquiry, $iTemplateId, $aAdditional);
		if(is_array($oNewDocument)) {
			return $oNewDocument;
		}

		$oLastVersion = $oNewDocument->getLastVersion();

		if ($date instanceof DateTimeInterface && !\Ext_Thebing_Client::immutableInvoicesForced()) {
			$oLastVersion->date = $date->format('Y-m-d');
		} else {
			// Datum beim Konvertieren auf heute setzen
			$oLastVersion->date = date('Y-m-d');
		}

		$oLastVersion->save();

		if($createPdf) {
			try {
				// PDF In erstellen mit der ursprünglich eingestellten Sprache
				$oNewDocument->createPdf(true, $oLastVersion->template_language);
			} catch (PDF_Exception $e) {
				$oNewDocument->document_number = '';
				$oNewDocument->delete();
				return array(
					L10N::t('Proforma konnte nicht umgewandelt werden! Bitte überprüfen Sie die die Vorlageneinstellungen', 'Thebing » Errors').' ('.$e->getMessage().')'
				);
			}
		}

		return $oNewDocument;
	}

	public function createCopy2($sToType = 'brutto', $sComment = '', Ext_TS_Inquiry $oInquiry = null, $iTemplateId = 0, $aAdditonalValues = []) {

		// Komische Parameterreihenfolge
		if (!$oInquiry) {
			throw new InvalidArgumentException('Inquiry missing');
		}

		$oLastVersion = $this->getLastVersion();

		$oNewDocument = new self(0);
		$oNewDocument->entity = get_class($oInquiry);
		$oNewDocument->entity_id = $oInquiry->id;
		//$oNewDocument->inquiry_id = $oAllocation->id;
		$oNewDocument->type = $sToType;
		$oNewDocument->active = 1;
//		$oNewDocument->document_number = $sDocNumber;
		$oNewDocument->bLockNumberrange = $this->bLockNumberrange; // Muss beim Klonen mitkommen wegem Proforma » Invoice!
//		$oNewDocument->numberrange_id = (int)$iNumberRangeId;
		// Wenn das Proforma direkt als Nicht-Entwurf angelegt wurde, dann soll das auch die Rechnung
		$oNewDocument->overrideCreationAsDraft = $this->overrideCreationAsDraft;
		foreach ($aAdditonalValues as $sKey => $mValue) {
			$oNewDocument->{$sKey} = $mValue;
		}

		$mReturn = $oNewDocument->save(false, $aAdditonalValues['numberrange_id'] ?? 0);

		if(is_array($mReturn)) {
			// Wenn Fehler vorhanden wird ein array zurückgegeben
			return $mReturn;
		}

		$oLastVersion->cloneVersion(true, [
			'document' => $oNewDocument,
			'to_document_type' => $sToType,
			'version_comment' => $sComment,
			'contact' => $oInquiry->getCustomer(), // #16002: $oContact auf $oInquiry umgestellt, weil die Relation nur in eine Richtung 1:n ist
			'template_id' => $iTemplateId
		]);

		if ($oInquiry->exist()) {
			Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->id, 0);
		}

		/*
		 * Das Dokument explizit in den Instanz-Cache setzen, sonst wird im weiteren Verlauf des aktuellen Aufrufs
		 * nicht die korrekte Instanz geladen und dadurch Werte nicht korrekt angezeigt - im konkreten Fall
		 * die Rechnungsnummer (#9414).
		 */
		if($oNewDocument->id > 0) {
			self::setInstance($oNewDocument);
		}

		return $oNewDocument;

	}

	public function getCurrency(){
		$iCurrency = $this->getCurrencyId();
		$oCurrency = Ext_Thebing_Currency::getInstance($iCurrency);
		return $oCurrency;
	}

	/**
	 * get the Name for the Type of the Document
	 * @return string
	 */
	public function getLabel() {

//		$oInquiry = $this->getInquiry();

		$sLabel = $this->getTypeLabel();

		// Das kann doch seit einer Ewigkeit nicht mehr funktionieren, da es $sType schon ewig nicht mehr gibt
//		// Falls Gruppenmitglied, dann packe in die Beschreibung das Wort "Gruppe - " hinzu, aber nur wenn der
//		// Dokumenttyp nicht "group_..." ist, falls in der alten Struktur die Dokumente so abgespeichert sind...
//		if(
//			$oInquiry->hasGroup() &&
//			strpos($sType, 'group_') === false
//		) {
//			$sLabel = L10N::t('Gruppen') . ' - ' . $sLabel;
//		}

		if($this->is_credit == 1) {
			$sLabel .= ' - '.L10N::t('Gutschrift', 'Thebing » PDF');
		}

		return $sLabel;
	}

	/**
	 * @return self|null
	 */
	public function getCreditNote() {

		$aCreditNotes = $this->getJoinTableObjects('child_documents_creditnote');

		if(!empty($aCreditNotes)) {
			return reset($aCreditNotes);
		}

		return null;

	}

	public function getCreditNoteSubAgency() {

		$aCreditNotes = $this->getJoinTableObjects('child_documents_creditnote_subagency');

		if(!empty($aCreditNotes)) {
			return reset($aCreditNotes);
		}

		return null;
	}

	// gibt die summe des Payments aus
	// nur funktionstüchtig bei RECEIPT Documente!
	public function getFormatetPaymentAmount(){

		//Zwischentabell auslesen
		$aKeys = array('document_id' => $this->id);
		$aPaymentDocuments = DB::getJoinData('kolumbus_inquiries_payments_documents', $aKeys, 'payment_id');

		$iId = reset($aPaymentDocuments);

		if($iId <= 0){
			return '';
		}

		$oInquiry = $this->getInquiry();
		$oPayment = Ext_Thebing_Inquiry_Payment::getInstance($iId);
		$fAmount = $oPayment->amount_inquiry;

		$iCurrencyId = $oInquiry->getCurrency();
		
		$oSchool	= $oInquiry->getSchool();

		$sAmount = Ext_Thebing_Format::Number($fAmount, $iCurrencyId, $oSchool->id);

		return $sAmount;
	}

	public function getAmount($bBeforArrival = true, $bAtSchool = true, $sType = null, $bCurrentAmount = true, $bCalculateWithDiscount = true){

		if($this->type === 'manual_creditnote') {
			$mCreditnote = $this->getManualCreditnote();
			if($mCreditnote instanceof Ext_Thebing_Agency_Manual_Creditnote) {
				return $mCreditnote->getCommissionAmount();
			}
		}

		$oLastVersion = $this->getLastVersion();
		if(is_object($oLastVersion)){
			return $oLastVersion->getAmount($bBeforArrival, $bAtSchool, $sType, $bCurrentAmount, $bCalculateWithDiscount);
		} else {
			return 0;
		}
	}

	public function getGroupAmount(){
		$oLastVersion = $this->getLastVersion();
		return $oLastVersion->getGroupAmount();
	}

	/**
	 * Offener Betrag des Dokuments
	 *
	 * @param int $iTypePayed
	 * @return float
	 */
	public function getOpenAmount($iTypePayed = 0) {

		$bBeforArrival = true;
		$bAtSchool = true;
		if($iTypePayed == 1){
			$bBeforArrival = true;
			$bAtSchool = false;
		} if($iTypePayed == 2){
			$bBeforArrival = false;
			$bAtSchool = true;
		}

		$fAmount = $this->getAmount($bBeforArrival, $bAtSchool);

		if($this->type === 'creditnote') {
			// Bei CNs muss der Betrag umgedreht werden
			$fAmountPayed = $this->getAllocatedAccountingAmount();
		} elseif($this->type === 'manual_creditnote') {
			// Manuelle CNs sind der ewig währende Sonderfall
			$fAmountPayed = 0;
			$mCreditnote = $this->getManualCreditnote();
			if($mCreditnote instanceof Ext_Thebing_Agency_Manual_Creditnote) {
				$fAmountPayed = $mCreditnote->getAllocatedAccountingAmount();
			}
		} else {
			$fAmountPayed = $this->getPayedAmount(0, $iTypePayed);
		}

		// Analog zu Ext_TS_Inquiry::getOpenPaymentAmount() auf zwei Nachkommastellen runden
		return round($fAmount - $fAmountPayed, 2);

	}

	/**
	 * Provisionsbetrag
	 *
	 * @return float|int
	 */
	public function getCommissionAmount() {

		if($this->type === 'brutto') {
			return 0;
		}

		$fAmountGross = $this->getAmount(true, true, 'brutto');
		$fAmountNet = $this->getAmount(true, true, 'netto');

		return $fAmountGross - $fAmountNet;

	}

	/**
	 * @TODO Sollte sich diese Methode nicht eher in der Version befinden?
	 *
	 * Liefert den bezahlten Betrag eines Dokumentes zurückanhand des Types (vorort/voranreise/ refound)
	 */
	public function getPayedAmount($iCurrencyId = 0, $iTypePayed = 0, $bCalcualteAgencyBrutto = false, array $aPaymentIds = []) {

		if($iCurrencyId == 0) {
			$oInquiry = $this->getInquiry();
			if(!$oInquiry) {
				return 0;
			}
			$iCurrencyId = $oInquiry->getCurrency();
		}

		$aItems = $this->getLastVersion()?->getItemObjects(true) ?? [];

		$fAmount = 0;

		foreach ($aItems as $oItem) {

			$fAmountPayed = $oItem->getPayedAmount($iCurrencyId, $iTypePayed, $aPaymentIds);

			// Magische Bruttobeträge herzaubern, die es nicht gibt
			if ($bCalcualteAgencyBrutto) {

				$fFactor = 1;
				if (abs($oItem->amount_net) > 0) {
					$fFactor = $oItem->amount / $oItem->amount_net;
				}

				$fAmountPayed = $fAmountPayed * $fFactor;

				// Bei 100% Provision gibt es keinen Zahlbetrag; das Item ist auf magische Weise komplett bezahlt
				if (
					$fAmountPayed == 0 &&
					abs($oItem->amount) > 0 &&
					$oItem->amount_net == 0
				) {
					$fAmountPayed = $oItem->amount;
				}

			}

			$fAmount += $fAmountPayed;

		}

		return $fAmount;
	}

	/**
	 * Bezahlter Betrag für Index (mit Rücksicht auf negative Bezahlung bei CNs)
	 *
	 * @return float|int
	 */
	public function getPayedAmountForIndex() {

		if($this->type === 'creditnote') {
			// Beträge der CN müssen umgedreht werden
			return $this->getAllocatedAccountingAmount();
		} elseif($this->type === 'manual_creditnote') {
			// Manuelle CNs sind ja immer ein Sonderfall, wie auch hier
			$mCreditnote = $this->getManualCreditnote();
			if($mCreditnote instanceof Ext_Thebing_Agency_Manual_Creditnote) {
				return $mCreditnote->getAllocatedAccountingAmount();
			}
			return 0;
		} else {
			return $this->getPayedAmount();
		}

	}

	/**
	 * @return Ext_Thebing_Inquiry_Document_Version
	 */
	public function newVersion() {
		$iNewVersion = 1;
		$oLastVersion = $this->getLastVersion();
		if($oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version) {
			$iNewVersion = $oLastVersion->version + 1;
		}

		$oVersion = $this->getJoinedObjectChild('versions'); /** @var Ext_Thebing_Inquiry_Document_Version $oVersion */
		$oVersion->active = 1;
		$oVersion->version = $iNewVersion;

		// TODO: Wenn es eine Version schon gibt, dann dieses Datum, falls nicht, created vom Dokument
		$oVersion->date = date('Y-m-d');

		return $oVersion;

	}

	public function getLatestVersionOrNew() {
	
		$methods = [
			function() {
				return $this->getLastVersion();
			},
			function() {
				$versions = $this->getJoinedObjectChild('versions', true);
				if(!empty($versions)) {
					return end($versions);
				}
			},
			function() {
				return $this->newVersion();
			}
		];
	
		foreach($methods as $method) {
			$version = $method();
			if($version instanceof Ext_Thebing_Inquiry_Document_Version) {
				return $version;
			}
		}
			
	}

	protected $_aVersionNumberCache = array();
	
	/**
	 * Get the specific version of the document
	 * @return Ext_Thebing_Inquiry_Document_Version
	 */
	public function getVersion($iVersion) {

		$sCacheKey = 'thebing_inquiry_documents_versions';

		$oVersion = null;

		if(isset($this->_aVersionNumberCache[$iVersion])) {
			$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance((int)$this->_aVersionNumberCache[$iVersion]);
		} else {

			$aDocumentVersions = WDCache::get($sCacheKey);

			if(isset($aDocumentVersions[$this->id][$iVersion])) {
				$oVersion = Ext_Thebing_Inquiry_Document_Version::getInstance((int)$aDocumentVersions[$this->id][$iVersion]);
				$this->_aVersionNumberCache[$iVersion] = $oVersion->id;
			}
			
		}

		if($oVersion === null) {
		
			$aVersions = (array)$this->getAllVersions();

			foreach((array)$aVersions as $oThisVersion){
				if($oThisVersion->version == $iVersion){
					$oVersion = $oThisVersion;
					break;
				}
			}

			if($oVersion !== null) {
				$aDocumentVersions = WDCache::get($sCacheKey);
				$aDocumentVersions[$this->id][$iVersion] = $oVersion->id;
				WDCache::set($sCacheKey, (7*24*60*60), $aDocumentVersions);

				$this->_aVersionNumberCache[$iVersion] = $oVersion->id;
			}
			
		}

		if($oVersion !== null) {
			return $oVersion;
		}

		return false;
	}

	/**
	 * Get the last version of the Document
	 * @param bool $bFromIndex (Ob das Objekt über die Spalteninformation "latest_version" geladen werden soll)
	 * @return Ext_Thebing_Inquiry_Document_Version|null
	 */
	public function getLastVersion($bFromIndex = true): ?Ext_Thebing_Inquiry_Document_Version {

		if($this->id <= 0) {
			return null;
		}
		
		if($bFromIndex) {
			$iLatestVersion = (int)$this->latest_version;
			
			if($iLatestVersion > 0) {
				$mVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iLatestVersion);
			} else {
				$this->saveLatestVersion();
				$iLatestVersion = (int)$this->latest_version;
				$mVersion = Ext_Thebing_Inquiry_Document_Version::getInstance($iLatestVersion);
			}
		} else {
			$aVersions = $this->getAllVersions(true);

			$aVersions = (array)$aVersions;

			$mVersion = end($aVersions);
		}

		if(
			!is_object($mVersion) || (
				is_object($mVersion) &&
				$mVersion->id <= 0
			)
		) {
			return null;
		}

		return $mVersion;
	}

	/**
	 * Get All Version of the Document
	 * The First Array Entry is the first Version
	 * Wichtig niemals versuchen das zu cachen :P
	 * die methode wird an manchen stellen vor und nach erzeugen einer neuen version aufgerufen
	 *
	 * @param bool $bOnlyLast
	 * @param string $sOrderSequence
	 * @param bool $bOnlyActive
	 * @return Ext_Thebing_Inquiry_Document_Version[]
	 */
	public function getAllVersions($bOnlyLast = false, $sOrderSequence = 'ASC', $bOnlyActive = true) {

		if(!$this->exist()) {
			return [];
		}

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_inquiries_documents_versions`
			WHERE
				`document_id` = :document_id
		";

		if($bOnlyActive) {
			$sSql .= " AND `active` = 1 ";
		}

		if($bOnlyLast) {
			$sSql .= "
					ORDER BY
						`version` DESC
					LIMIT 1";
		} else {
			// Das Limit ist wichtig weil das ganze sonst viel zu langsam wird!
			$sSql .= "
					ORDER BY
						`version` ".$sOrderSequence."
					LIMIT 20
					";
		}
		$aSql = array('document_id' =>(int)$this->id);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();
		foreach($aResult as $aVersion) {
			$aBack[] = Ext_Thebing_Inquiry_Document_Version::getObjectFromArray($aVersion);
		}

		return $aBack;

	}

	###########
	#ALT
	###########				
	public function getDataArray(){
		return $this->getArray();
	}

	/**
	 * @TODO Generieren der Nummer entfernen, da das niemals in einer DB-Transaktion passieren darf
	 *
	 * @param bool $bNoNumber
	 * @param null $iNumberRangeId
	 * @return array|Ext_Thebing_Inquiry_Document
	 */
	public function save($bNoNumber = false, $iNumberRangeId=null) {
		if (
			$this->isNew() &&
			$this->shouldCreateAsDraft()
		) {
			$this->draft = 1;
			// Bei Entwürfen wird keine Nummer generiert
			$this->document_number = '';
			$this->numberrange_id = 0;
			$this->setAutoGenerateNumber(false);
		}

		// TODO Entfernen
		$bMakeNumber = false;
		if(
			$this->bAutoGenerateNumber === true &&
			$bNoNumber === false
		){
			if(
				$this->id <= 0 && 
				$this->document_number == ''
			) {
				$bMakeNumber = true;
			}
		}

		// TODO Entfernen
		// @TODO Warum ist hier $bNoNumber nicht eingebaut? Siehe #5819
		if(
			$this->bAutoGenerateNumber === true &&
			$this->id > 0 && 
			$this->document_number == ''
		) {
			$bMakeNumber = true;
		}

		// Fehlende numberrange_id bei vorhandener document_number endgültig abfangen
		if(
			$this->id == 0 &&
			$this->document_number !== '' &&
			$this->numberrange_id == 0 &&
			$this->type !== 'additional_document'
		) {
			return array('DOCUMENT_NUMBER_BUT_NO_NUMBERRANGE_ID');
		}

		$bSetPartialInvoice = false;
		if(
			$this->isNew() &&
			!$this->isProforma() &&
			$this->partial_invoice
		) {
			$bSetPartialInvoice = true;
		}
		
		if($this->isNew()) {
			\Core\Facade\SequentialProcessing::add('ts/document-save-check', $this);
		}

		parent::save();

		if($bSetPartialInvoice === true) {
			// Teilrechnung als umgewandelt markieren
			$nextPartialInvoice = Ts\Entity\Inquiry\PartialInvoice::getRepository()->getNext($this->getInquiry());
			if($nextPartialInvoice) {
				$nextPartialInvoice->converted = time();
				$nextPartialInvoice->document_id = $this->id;
				$nextPartialInvoice->save();
			}
		}
		
		$bIsNumberRequired = $this->isNumberRequired();

		// TODO Entfernen
		if(
			$bMakeNumber &&
			$bIsNumberRequired &&
			(
				empty($this->_oDb->getLastTransactionPoint()) ||
				!$this->bLockNumberrange
			) &&
			empty($this->document_number) // Ist leider notwendig, da es sonst passieren kann, dass die doppelt gespeichert wird
		) {

			$mSuccecss = $this->generateNumber($iNumberRangeId);
			if(is_array($mSuccecss)) {
				// Fehler vom Nummernkreis direkt zurückgeben
				return $mSuccecss;
			}
		}
		
		// Prüfen ob sich es um das aktuelle Dokument um eine Rechnung handelt.
		
		return $this;
	}

	public function initUpdateTransactions() {
		\Core\Entity\ParallelProcessing\Stack::getRepository()->writeToStack('ts/update-transactions', ['document_id'=>$this->id], 10);
	}
	
	public function updateTransactions($bWriteOffPrevious=true) {
		
		$aRelevantTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnote_and_manual_creditnote');
		
		if (
			in_array($this->type, $aRelevantTypes) !== true ||
			$this->isDraft()
		) {
			return;
		}

		if(!$this->isActive()) {
			\TsAccounting\Service\Accounts\Transactions::delete($this->isProforma() ? 'proforma' : 'invoice', $this->id, 'DELETED');
			return;
		}

		$oInquiry = $this->getInquiry();

		// Änderungen an einer Proforma nicht berücksichtigen, wenn es schon eine Rechnung gibt
		if(
			$this->isProforma() &&
			$oInquiry->has_invoice
		) {
			return;
		}
		
		/*
		 * Wenn eine Rechnung gespeichert wird, und es eine Proforma gibt, muss diese ausgebucht werden
		 * Man kann aktuell nicht ermitteln, ob das zum erste Mal, passiert, daher jedes Mal
		 */
		if(
			!$this->isProforma() &&
			$oInquiry->has_proforma
		) {
			$oProforma = $oInquiry->getLastDocument('proforma');
			// Proforma-Flag falsch?
			if(
				$oProforma &&
				$oProforma->exist()
			) {
				\TsAccounting\Service\Accounts\Transactions::delete('proforma', $oProforma->id, 'EXISTING_INVOICE');
			}
		}
		
		$oCurrentVersion = $this->getLastVersion();
		if (!$oCurrentVersion) {
			// TODO Keine Ahnung, ob das hier false sein darf, aber es tritt auf
			return;
		}
		
		// Alte Version ausbuchen
		if(
			$bWriteOffPrevious &&
			$oCurrentVersion->version > 1
		) {
			$oLatestVersion = $this->getVersion(($oCurrentVersion->version-1));
			if(
				$oLatestVersion && 
				$oLatestVersion->exist()
			) {
				$oLatestVersion->insertTransactions(true);
			}
		}
		
		// Neue Version buchen
		$oCurrentVersion->insertTransactions();
		
	}
	
	/**
	 * @inheritdoc
	 */
	public function delete() {

		if (!$this->isMutable()) {
			throw new Exception('Document is not mutable. Cannot delete.');
		}

		if ($this->isInvoice() && !empty($this->document_number)) {
			// Prefix hinzufügen damit Nummer ggf. wiederverwendet werden kann
			$this->document_number = 'XX_' . $this->document_number;
		}

		$bSuccess = parent::delete();

		if ($bSuccess === true) {

			$this->initUpdateTransactions();

			// Funktioniert nicht mit cascade (würde nur Zwischentabelle löschen)
			$aCreditnotes = $this->getJoinTableObjects('child_documents_creditnote'); /** @var self[] $aCreditnotes */
			foreach($aCreditnotes as $oCreditnote) {
				$oCreditnote->delete();
			}

			if (
				($oInquiry = $this->getInquiry()) !== null &&
				($this->isInvoice() || $this->isProforma())
			) {
				Ext_Thebing_Document::refreshPaymentReceipts( $oInquiry, $this);
			}

			// Alle Versuche, primary_key_field zu verändern oder ein detach einzubauen, haben nicht funktioniert
			DB::updateData('kolumbus_inquiries_payments_overpayment', ['inquiry_document_id' => null], ['inquiry_document_id' => $this->id]);

		}

		return $bSuccess;

	}

	/**
	 * @param int $iNumberRangeId
	 * @return array|Ext_Thebing_Inquiry_Document
	 */
	public function generateNumber($iNumberRangeId = null) {

		$oAllocationObject = $this->getInquiry();
		$oSchool = null;
		$oNumberrange = null;

		if($oAllocationObject instanceof Ext_TS_Inquiry_Abstract) {
			$oSchool = $oAllocationObject->getSchool();
		} else {
			// kann auch Ext_TC_Basic sein, z.B. Ext_Thebing_Agency_Manual_Creditnote
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}

		if(!($oSchool instanceof Ext_Thebing_School)) {
			throw new RuntimeException('No school!');
		}

		if($oAllocationObject instanceof Ext_TS_Inquiry) {
			Ext_TS_NumberRange::setInbox($oAllocationObject->getInbox());
		}

		if(
			!$iNumberRangeId && (
				$oAllocationObject instanceof Ext_TS_Inquiry_Abstract ||
				$oAllocationObject instanceof Ext_Thebing_Agency_Manual_Creditnote
			)
		) {

			/*
			 * Ext_TS_Inquiry_Abstract und Ext_Thebing_Agency_Manual_Creditnote haben beide eine Methode
			 * mit dem Namen getTypeForNumberrange(). Sind zwar nicht voneinander abgeleitet und haben auch eine
			 * unterschiedliche Signatur aber egal ...
			 */
			$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject(
				$oAllocationObject->getTypeForNumberrange($this->type, $this->getTemplateType()),
				(bool)$this->is_credit,
				$oSchool->id
			);

		}

		if(!($oNumberrange instanceof Ext_Thebing_Inquiry_Document_Numberrange)) {
			// Darf nicht bei false auf Default-Wert gesetzt werden, da das ansonsten bei jeder neuen Rechnung passieren würde!
			$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($iNumberRangeId);
		}

		if($oNumberrange->id < 1) {
			throw new RuntimeException('No numberrange!'); // Die Nachricht wird an anderer Stelle abgefragt, nicht ändern!
		}

		// @TODO Das darf hier eigentlich gar nicht passieren, sondern muss beim jeweiligen Prozessbeginn passieren
		if($this->bLockNumberrange) {
			if(!$oNumberrange->acquireLock()) {
				return array(Ext_Thebing_Document::getNumberLockedError());
			}
		}

		$sNumber = $oNumberrange->generateNumber();

		if(empty($sNumber)) {
			return array();
		}

		$this->document_number = $sNumber;
		$this->numberrange_id = $oNumberrange->id;

		// Release Datum immer setzen, es sein denn die Schuleinstellung sagt "individuell"
		if(
			$oSchool->invoice_release != 1 &&
			(
				$this->released == '0000-00-00 00:00:00' ||
				$this->released == '0'
			)
		) {
			$this->released = time();
		}

		$this->save();

		if($this->bLockNumberrange) {
			$oNumberrange->removeLock();
		}

		return $this;

	}

	/**
	 * @return boolean
	 */
	public function isNumberRequired() {

		// Inaktive Dokumente brauchen nie eine Nummer
		if($this->active == 0) {
			return false;
		}

		$sNumberrangeType = null;

		$oInquiry = $this->getInquiry();
		if($oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			$sNumberrangeType = $oInquiry->getTypeForNumberrange($this->type, $this->getTemplateType());
		}
		
		$bIsRequired = self::isNumberRequiredForType($this->type, $sNumberrangeType);
		
		return $bIsRequired;
	}

	/**
	 * @param string $sType
	 * @return boolean
	 */
	public static function isNumberRequiredForType($sType, $sNumberrangeType) {
				
		$aTypes = Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnote_and_manual_creditnote');
		
		return in_array($sType, $aTypes);
	}

	public function setAutoGenerateNumber($bAutoGenerateNumber) {
		$this->bAutoGenerateNumber = $bAutoGenerateNumber;
	}

	public function getEntity(): \Ts\Interfaces\Entity\DocumentRelation {

		$entity = match ($this->entity) {
			\Ext_Thebing_Teacher::class => \Ext_Thebing_Teacher::getInstance($this->entity_id),
			default => $this->getInquiry()
		};

		if (!($entity instanceof \Ts\Interfaces\Entity\DocumentRelation)) {
			throw new LogicException(
				sprintf('%s did not get an instance of %s [%s::%s]', __METHOD__, \Ts\Interfaces\Entity\DocumentRelation::class, $this->entity, $this->entity_id)
			);
		}

		return $entity;
	}

	/**
	 * Achtung muss nicht immer
	 *
	 * @see Ext_Thebing_Inquiry_Document::getInquiryAbstract()
	 * @return bool|Ext_TS_Inquiry Das Inquiry-Objekt oder false
	 */
	public function getInquiry() {

		switch ($this->entity) {
			case Ext_TS_Inquiry::class:
				
				// Sonderfall Online-Form
				if(!empty($this->entityObject)) {
					return $this->entityObject;
				}
				
				return Ext_TS_Inquiry::getInstance($this->entity_id);
			case Ext_TS_Inquiry_Journey::class:
				return Ext_TS_Inquiry_Journey::getInstance($this->entity_id)->getInquiry();
			case \TsCompany\Entity\JobOpportunity\StudentAllocation::class:
				return \TsCompany\Entity\JobOpportunity\StudentAllocation::getInstance($this->entity_id)->getInquiry();
			case Ext_Thebing_Tuition_Course::class:
			default:
				return null;
		}

//		return $this->getJoinedObject('inquiry');

//		if($this->inquiry_id <= 0) {
//			return false;
//		}
//
//		// hier musste mit getInstance anstelle von new gearbeitet werden!
//		return Ext_TS_Inquiry::getInstance($this->inquiry_id);

	}

	/**
	 * @see Ext_Thebing_Inquiry_Document::getInquiry()
	 * @see Ext_Thebing_Inquiry_Document::getEnquiry()
	 * @return bool|Ext_TS_Inquiry_Abstract Inquiry-Abstract-Objekt (aktuell Inquiry oder Enquiry) oder false
	 */
	public function getInquiryAbstract() {

		$oInquiryAbstract = $this->getInquiry();
		if(!($oInquiryAbstract instanceof Ext_TS_Inquiry_Abstract)) {
			throw new LogicException(__METHOD__.' did not get an inquiry.');
//			$oInquryAbstract = $this->getEnquiry();
		}

		return $oInquiryAbstract;

	}

	/**
	 * Returns label for document type
	 * @param string $sType
	 * @return string
	 */
	protected function getTypeLabel() {

		$sType = $this->type;

		/**
		 * brutto_diff & netto_diff hinzugefügt da noch nicht vorhanden 14.04.2010
		 */
		$sLabel = '';

		switch ($sType) {

			case 'groupbrutto': 
				$sLabel = 'Gruppenrechnung brutto';
				break;
				
			case 'groupnetto': 
				$sLabel = 'Gruppenrechnung netto';
				break;
				
			case 'groupstorno': 
				$sLabel = 'Gruppenrechnung Storno';
				break;
				
			case 'brutto': 
				$sLabel = 'Bruttorechnung';
				break;
				
			case 'netto': 
				$sLabel = 'Nettorechnung';
				break;
				
			case 'brutto_diff':
				$sLabel = 'Bruttodifferenzrechnung';
				break;

			case 'brutto_diff_special':
				$sLabel = 'Bruttodifferenzrechnung (interne Gutschrift)' ;
				break;
				
			case 'netto_diff': 
				$sLabel = 'Nettodifferenzrechnung';
				break;
				
			case 'storno': 
				$sLabel = 'Stornorechnung';
				break;
				
			case 'loa': 
				$sLabel = 'LoA';
				break;

			case 'credit_brutto':
			case 'credit_netto':
			case 'credit': 
				$sLabel = 'Gutschrift';
				break;
				
			case 'edu_confirmation': 
				$sLabel = 'Bildungsurlaub Bestätigung';
				break;
				
			case 'edu_participation': 
				$sLabel = 'Bildungsurlaub Teilnahme';
				break;

			case 'proforma':
				$sLabel = 'Proforma';
				break;
			case 'proforma_brutto':
				$sLabel = 'Brutto Proforma';
				break;
			case 'proforma_netto':
				$sLabel = 'Netto Proforma';
				break;
			case 'proforma_brutto_diff':
				$sLabel = 'Bruttodifferenzproforma';
				break;
			case 'proforma_netto_diff':
				$sLabel = 'Nettodifferenzproforma';
				break;
				
			case 'accommodation': 
				$sLabel = 'Unterkunft';
				break;
				
			case 'receipt': 
			case 'receipt_customer':
			case 'receipt_agency':
				$sLabel = 'Quittung';
				break;
				
			case 'receipt_local':
			case 'receipt_local_customer':
			case 'receipt_local_agency':
				$sLabel = 'Quittung Vorortkosten';
				break;
				
			case 'receipt_refund':
			case 'receipt_refund_customer':
			case 'receipt_refund_agency':
				$sLabel = 'Quittung Rückgabe'; 
				break;
				
			case 'receipt_overview':
			case 'receipt_overview_customer':
			case 'receipt_overview_agency':
				$sLabel = 'Quittung Übersicht';
				break;
				
			case 'receipt_overview_local':
			case 'receipt_overview_local_customer':
			case 'receipt_overview_local_agency':
				$sLabel = 'Quittung Übersicht Vorortkosten';
				break;
				
			case 'receipt_overview_refund':
			case 'receipt_overview_refund_customer':
			case 'receipt_overview_refund_agency':
				$sLabel = 'Quittung Übersicht Rückgabe';
				break;

			case 'document_payment_overview_customer':
				$sLabel = 'Quittungsübersicht Kunde';
				break;

			case 'document_payment_overview_agency':
				$sLabel = 'Quittungsübersicht Agentur';
				break;
			
			case 'programchange': 
				$sLabel = 'Programchange';
				break;	
				
			case 'communication_docs': 
				$sLabel = 'Schuldokument';
				break;

			case 'creditnote': 
				$sLabel = 'Agenturgutschrift';
				break;

			case 'creditnote_subagency':
				$sLabel = 'Unteragenturgutschrift';
				break;

			case 'proforma_creditnote':
				$sLabel = 'Agenturgutschrift (Proforma)';
				break;

			case 'additional_document':
				$sLabel = 'Sonstiges';
				break;

			case 'manual_creditnote':
				$sLabel = 'Manuelle Agenturgutschrift';
				break;

			case 'examination':
				$sLabel = 'Examen';
				break;

			case 'document_payment_customer':
				$sLabel = 'Kundenzahlungen je Rechnung';
				break;
			
			case 'document_payment_agency':
				$sLabel = 'Agenturzahlungen je Rechnung';
				break;
			
			case 'creditnote_cancellation': // Fake-Typ für Typ-Status
				$sLabel = 'Agenturgutschrift (Storno)';
				break;
			
			case 'insurance': 
				$sLabel = 'Versicherung';
				break;
			
//			case 'offer':
//				$sLabel = 'Angebot';
//				break;
						
			case 'offer_brutto':
				$sLabel = 'Bruttoangebot';
				break;

			case 'offer_netto':
				$sLabel = 'Nettoangebot';
				break;

			case 'offer_converted': // Fake-Typ für Typ-Status
				$sLabel = 'Angebot (umgewandelt)';
				break;

			case 'proforma_converted': // Fake-Typ für Typ-Status (glaube ich)
				$sLabel = 'Proforma (umgewandelt)';
				break;
			
			default:

				if(
					$this->id > 0 ||
					!empty($sType)
				) {
					throw new Exception('Unkown document type "'.$sType.'"!');
				}

				break;
		}
		
		$sLabel = L10N::t($sLabel);

		return $sLabel;
		
	}

	/*
	 * Funktion liefert frue bei NettoDokument sonst false
	 */
	public function isNetto() {

		// TODO Das macht keinen Sinn, da eine Bruttodiff auf eine Nettorechnung folgen kann
		// Eine Storno hat eigentlich keinen Typ, da beide Typen an Beträgen vorkommen
		if($this->type === 'storno') {
			$oParentDoc = $this->getParentDocument();
			$sType = $oParentDoc->type;
		} else {
			$sType = $this->type;
		}

		if(strpos($sType, 'netto') !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Bereitet ein Array mit Dokumenten für ein Select vor
	 * @param array|Ext_Thebing_Inquiry_Document[] $aDocuments
	 * @param string $sIdColumn
	 * @return array
	 */
	public static function prepareDocumentsForSelect($aDocuments, $sIdColumn='id') {
		$aReturn = array();

		foreach((array)$aDocuments as $mDocument) {

			if(is_array($mDocument)) {
				$oDocument = Ext_Thebing_Inquiry_Document::getInstance($mDocument['id']);
			} else {
				$oDocument = $mDocument;
			}

			$oLastVersion = $oDocument->getLastVersion();

			if($oLastVersion != false) {
				if($sIdColumn == 'version_id') {
					$iReturnId = $oLastVersion->id;
				} else {
					$iReturnId = $oDocument->id;
				}
				$aReturn[$iReturnId] = $oLastVersion->getLabel();
			}
		}

		return $aReturn;
	}

	/**
	 * Erstellung der Zahlungsübersicht dieses Dokuments vorbereiten (document_payment_overview):
	 * 	1. Template suchen
	 * 	2. Nummer erzeugen
	 * 	3. Dokument speichern
	 *
	 * @see Ext_Thebing_Inquiry_Document::createPaymentDocument()
	 *
	 * @param bool $bAgencyDocument
	 * @return bool
	 * @throws Exception
	 */
	public function preparePaymentDocument($bAgencyDocument=false) {

		$oInquiry = $this->getInquiry();
		$oSchool = $oInquiry->getSchool();
		$oInbox = $oInquiry->getInbox();
		$oCustomer	= $oInquiry->getCustomer();

		$sType = 'document_payment_customer';
		$sTemplateType = 'document_customer_document_payment';

		if($bAgencyDocument) {
			if($oInquiry->agency_id > 0) {
				$sType = 'document_payment_agency';
				if($this->isNetto()) {
					$sTemplateType = 'document_agency_document_payment';
				} else {
					$sTemplateType = 'document_customer_document_payment';
				}
			} else {
				return true;
			}
		}

		// Suchen, ob bereits Zahlungsübersicht für dieses Dokument existiert
		$oLastDoc = $this->searchDocumentsPaymentDocuments($sType);

		// Template suchen
		$aTemplate = Ext_Thebing_Pdf_Template_Search::s($sTemplateType, $oCustomer->getLanguage(), $oSchool->id, $oInbox->id);
		$oTemplate = reset($aTemplate);

		// Wenn kein Template gefunden wurde, muss auch kein Beleg erstellt werden
		if(empty($aTemplate)) {
			return true;
		}

		if($oLastDoc !== false) {
			$oDocument = $oLastDoc;
		} else {
			$oDocument = $oInquiry->newDocument($sType);
		}

		if($oDocument->document_number == '') {

			$iInvoiceNumberrangeId = null;
			if($this->numberrange_id) {
				$iInvoiceNumberrangeId = $this->numberrange_id;
			}

			if($oInquiry instanceof Ext_TS_Inquiry) {
				$oInbox = $oInquiry->getInbox();
				Ext_TS_NumberRange::setInbox($oInbox);
			}

			$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getObject($sType, false, $oSchool->id, $iInvoiceNumberrangeId);
			$oNumberrange->bAllowDuplicateNumbers = true; // Nicht so wichtig hier, außer der Kunde hat überall denselben Nummernkreis…

			// Nummernkreis darf kein leeres Objekt sein, sonst schlägt $oDocument->save() fehl
			if(
				!$oNumberrange instanceof Ext_TC_NumberRange ||
				$oNumberrange->id == 0
			) {
				// Nach #6134 müssen Bezahlbelege nun einen Nummernkreis haben
				return array('message' => 'NUMBERRANGE_REQUIRED');
			}

			if(!$oNumberrange->acquireLock()) {
				return array('message' => 'NUMBERRANGE_LOCKED');
			}

			$sNumber = $oNumberrange->generateNumber();

			$oDocument->document_number = $sNumber;
			$oDocument->numberrange_id = $oNumberrange->id;

		}

		// Dokument muss noch von createPaymentDocument() generiert werden
		$oDocument->status = 'pending';

		$oDocument->bUpdateIndexEntry = false;
		$mSave = $oDocument->save();

		if(is_array($mSave)) {
			// Normalerweise Fehler vom Nummernkreis
			return array('message' => reset($mSave));
		}

		// Relation zwischen Dokument und Payment-Dokument speichern
		$aKeys = array('document_id' => $this->id, 'payment_document_id' => $oDocument->id);
		$aJoinData = array($this->id);
		DB::updateJoinData('kolumbus_inquiries_documents_paymentdocuments', $aKeys, $aJoinData, 'document_id');

		$aStackData = array(
			'type' => 'document_payment_overview',
			'document_id' => $this->id,
			'payment_document_id' => $oDocument->id,
			'payment_document_template_id' => $oTemplate->id
		);

		$oStackRepository = Stack::getRepository();
		$oStackRepository->writeToStack('ts/document-generating', $aStackData, 8);

		// Nummernkreis kann hier einfach entsperrt werden, da im Fehlerfall die Methode wegen return endet
		if(isset($oNumberrange)) {
			$oNumberrange->removeLock();
		}

		return true;
	}

	/**
	 * Erzeugt ein PDF mit der Auflistung aller Zahlungen pro Item (document_payment_overview)
	 *
	 * @see Ext_Thebing_Inquiry_Document::preparePaymentDocument()
	 *
	 * @param array $aData
	 * @return bool
	 * @throws Exception
	 */
	public function createPaymentDocument(array $aData) {

		// $this ist weiterhin Parent-Document
		$oDocument = static::getInstance($aData['payment_document_id']);
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($aData['payment_document_template_id']);

		if(
			$oDocument->id == 0 ||
			$oTemplate->id == 0
		) {
			throw new InvalidArgumentException('Invalid document ('.$aData['payment_document_id'].') or template ('.$aData['payment_document_template_id'].')');
		}

		$oInquiry = $this->getInquiry();
		$oCustomer	= $oInquiry->getCustomer();
		$sLanguage = $oCustomer->getLanguage();
		$oSchool = $oInquiry->getSchool();

		// Version anlegen
		$oVersion = $oDocument->newVersion();

		$aSearch = array('{document_number}');
		$aReplace = array($this->document_number);

		$oVersion->txt_address = $oTemplate->getStaticElementValue($sLanguage, 'address');
		$oVersion->txt_subject = $oTemplate->getStaticElementValue($sLanguage, 'subject');
		$oVersion->txt_intro = $oTemplate->getStaticElementValue($sLanguage, 'text1');
		$oVersion->txt_outro = $oTemplate->getStaticElementValue($sLanguage, 'text2');
		$oVersion->txt_pdf = $oTemplate->getOptionValue($sLanguage, $oSchool->id, 'first_page_pdf_template');
		$oVersion->txt_signature = $oTemplate->getOptionValue($sLanguage, $oSchool->id, 'signatur_text');
		$oVersion->signature = $oTemplate->getOptionValue($sLanguage, $oSchool->id, 'signatur_img');
		$oVersion->template_id = $oTemplate->id;
		$oVersion->template_language = $sLanguage;

		$oVersion->txt_address = str_replace($aSearch, $aReplace, $oVersion->txt_address);
		$oVersion->txt_subject = str_replace($aSearch, $aReplace, $oVersion->txt_subject);
		$oVersion->txt_intro = str_replace($aSearch, $aReplace, $oVersion->txt_intro);
		$oVersion->txt_outro = str_replace($aSearch, $aReplace, $oVersion->txt_outro);

		$sVersionDate = $oTemplate->getStaticElementValue($sLanguage, 'date');
		$oVersion->date = Ext_Thebing_Format::ConvertDate($sVersionDate, $oSchool->id, true);

		$bAgencyReceipt = false;
		if(strpos($oTemplate->type, 'agency') !== false) {
			$bAgencyReceipt = true;
		}

		$aTable = $this->generatePaymentOverviewPositionTable($bAgencyReceipt);
		$oPDF = new Ext_Thebing_Pdf_Basic($oVersion->template_id, $oSchool->id);
		$oPDF->setAllowSave(false);
		$oPDF->createDocument($oDocument, $oVersion, [$aTable]);

		$aTemp = Ext_Thebing_Inquiry_Document::buildFileNameAndPath($oDocument, $oVersion, $oSchool);
		$sPath = $aTemp['path'];
		$sFileName = $aTemp['filename'];
		$sFilePath = $oPDF->createPdf($sPath, $sFileName);

		if(!is_file($sFilePath)) {
			throw new RuntimeException('File "'.$sFilePath.'" missing!');
		}

		$oVersion->path = $oVersion->prepareAbsolutePath($sFilePath);

		// Dokument als fertig generiert markieren
		$oDocument->status = 'ready';
		$oDocument->save();

		return true;
	}

	/**
	 * Tabelle generieren für PDF: Zahlungsstatus der einzelnen Items dieses Dokuments
	 *
	 * @param bool $bAgencyReceipt
	 * @param array $aPaymentIds Leer: Übersicht für Rechnung, gefüllt: Übersicht für Bezahlbeleg
	 * @return array
	 */
	public function generatePaymentOverviewPositionTable($bAgencyReceipt, array $aPaymentIds=[]) {

		$oLastVersion = $this->getLastVersion();
		$aItems = $oLastVersion->getItemObjects(true, false);

		$oInquiry = $this->getInquiry();
		$oCustomer	= $oInquiry->getCustomer();
		$sLanguage = $oCustomer->getLanguage();
		$oSchool = $oInquiry->getSchool();

		$aTable = array();
		$aHeader = array();
		$aBody = array();

		// Wenn Zahlungs-IDs übergeben: Zusätzliche Spalte und dort nur der Betrag des Payments
		if(empty($aPaymentIds)) {
			$bPaymentReceipt = false;
		} else {
			$bPaymentReceipt = true;
			$aTable['caption'] = '<strong>'.$this->document_number.'</strong>';
		}

		$i = 0;

		if(!$bPaymentReceipt) {
			$aHeader[$i]['width'] = '20';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('B.-Nr', $sLanguage);
			$aHeader[$i]['align'] = 'L';
			$i++;
		}

		$aHeader[$i]['width'] = 'auto';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Position', $sLanguage);
		$aHeader[$i]['align'] = 'L';
		$i++;

		if(
			!$bAgencyReceipt || (
				$bAgencyReceipt &&
				$oSchool->netto_column != 1 &&
				!$bPaymentReceipt // Bei Zahlungsbelegen (Zahlungen) ausblenden, da sonst zu viele Spalten
			)
		){
			$aHeader[$i]['width'] = '23';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Brutto', $sLanguage);
			$aHeader[$i]['align'] = 'R';
			$i++;
		}

		if(
			$bAgencyReceipt &&
			$oSchool->netto_column != 1 &&
			!$bPaymentReceipt // Bei Zahlungsbelegen (Zahlungen) ausblenden, da sonst zu viele Spalten
		) {
			$aHeader[$i]['width'] = '22';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Provision', $sLanguage);
			$aHeader[$i]['align'] = 'R';
			$i++;
		}

		if($bAgencyReceipt) {
			$aHeader[$i]['width'] = '23';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Netto', $sLanguage);
			$aHeader[$i]['align'] = 'R';
			$i++;
		}

		// Wenn Bezahlbeleg: Zusätzliche Spalte für Betrag, welcher in der Zahlung dem Item zugewiesen wurde
		$sLabelAddition = '';
		if($bPaymentReceipt) {
			$sLabelAddition = ' (gesamt)';

			$aHeader[$i]['width'] = '23';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Bezahlt', $sLanguage);
			$aHeader[$i]['align'] = 'R';
			$i++;
		}

		$aHeader[$i]['width'] = '23';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Bezahlt'.$sLabelAddition, $sLanguage);
		$aHeader[$i]['align'] = 'R';
		$i++;

		$aHeader[$i]['width'] = '23';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Offen'.$sLabelAddition, $sLanguage);
		$aHeader[$i]['align'] = 'R';
		$i++;

		$aTable['header'] = $aHeader;

		$iColspan = $i;

		$aTotal = [
			'brutto' => 0,
			'netto' => 0,
			'provision' => 0,
			'payed_in_payment' => 0,
			'payed' => 0,
			'balance' => 0
		];

		$i = 0;

		$sLastCustomer = '';

		foreach($aItems as $oItem) {

			$fPayedAmount = $oItem->getPayedAmount($oInquiry->getCurrency());
			$fPayedInPaymentAmount = 0;

			if($bPaymentReceipt) {
				$fPayedInPaymentAmount = $oItem->getPayedAmount($oInquiry->getCurrency(), 0, $aPaymentIds);
			}

			$fAmount = (float)$oItem->getTaxDiscountAmount($oSchool->id, 'brutto');
			$fAmountNet	= (float)$oItem->getTaxDiscountAmount($oSchool->id, 'netto');

			$fProvision = $fAmount - $fAmountNet;

			// Bei Zahlungsbelegen für Kunden muss aus dem bezahlten Nettobetrag ein bezahlter Bruttobetrag hergezaubert werden
			if(
				!$bAgencyReceipt &&
				$this->isNetto()
			) {
				// Wenn kein Nettobetrag (nur Provision), dann ist das Item auf magische Weise immer bezahlt
				if($fAmountNet == 0) {
					$fPayedAmount = $fAmount;
					$fPayedInPaymentAmount = $fAmount;
				} else {
					// Komische Umrechnung (hier passiert Magie)
					$fPayedAmount = ($fAmount / $fAmountNet) * $fPayedAmount;
					$fPayedInPaymentAmount = ($fAmount / $fAmountNet) * $fPayedInPaymentAmount;
				}
			}

			$fOpenAmount = $fAmount - $fPayedAmount;

			if($bAgencyReceipt) {
				$fOpenAmount = $fAmountNet - $fPayedAmount;
			}

			$aTotal['brutto'] += $fAmount;
			$aTotal['provision'] += $fProvision;
			$aTotal['netto'] += $fAmountNet;
			$aTotal['payed'] += $fPayedAmount;
			$aTotal['payed_in_payment'] += $fPayedInPaymentAmount;
			$aTotal['balance'] += $fOpenAmount;

//			if($sLastCustomer != $oCustomer->name) {
//				$aBody[$i][0]['text'] = '<b>'.$oCustomer->name.'</b>';
//				$aBody[$i][0]['align'] = 'L';
//				$aBody[$i][0]['colspan'] = $iColspan;
//				$i++;
//			}

			$ii = 0;

			if(!$bPaymentReceipt) {
				$aBody[$i][$ii]['text'] = join(' ', $oItem->getAllocatedPaymentReceiptNumbers());
				$aBody[$i][$ii]['align'] = 'L';
				$ii++;
			}


			$aBody[$i][$ii]['text'] = $oItem->description;
			$aBody[$i][$ii]['align'] = 'L';
			$ii++;

			// Brutto
			if(
				!$bAgencyReceipt || (
					$bAgencyReceipt &&
					$oSchool->netto_column != 1 &&
					!$bPaymentReceipt // Bei Zahlungsbelegen (Zahlungen) ausblenden, da sonst zu viele Spalten
				)
			) {
				$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fAmount, $oInquiry->getCurrency(), $oSchool->id);
				$aBody[$i][$ii]['align'] = 'R';
				$ii++;
			}

			// Provision
			if(
				$bAgencyReceipt &&
				$oSchool->netto_column != 1 &&
				!$bPaymentReceipt // Bei Zahlungsbelegen (Zahlungen) ausblenden, da sonst zu viele Spalten
			) {
				$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fProvision, $oInquiry->getCurrency(), $oSchool->id);
				$aBody[$i][$ii]['align'] = 'R';
				$ii++;
			}

			// Netto
			if($bAgencyReceipt) {
				$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fAmountNet, $oInquiry->getCurrency(), $oSchool->id);
				$aBody[$i][$ii]['align'] = 'R';
				$ii++;
			}

			// Bezahlt (Zahlung)
			if($bPaymentReceipt) {
				$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fPayedInPaymentAmount, $oInquiry->getCurrency(), $oSchool->id);
				$aBody[$i][$ii]['align'] = 'R';
				$ii++;
			}

			// Bezahlt (gesamt)
			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fPayedAmount, $oInquiry->getCurrency(), $oSchool->id);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;

			// Offen (gesamt)
			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($fOpenAmount, $oInquiry->getCurrency(), $oSchool->id);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;

			$sLastCustomer = $oCustomer->name;

			$i++;
		}


		## START Summenzeile
		$aBody[$i] = 'line';
		$i++;

		$ii = 0;

		$aBody[$i][$ii]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Summe', $sLanguage);
		$aBody[$i][$ii]['align'] = 'L';
		$ii++;

		// Durch hinzufügen der "Belegnummer"-Spalte (hat nichts mit der Summe zutun)
		if(!$bPaymentReceipt) {
			$aBody[$i][$ii]['text'] = '';
			$aBody[$i][$ii]['align'] = 'L';
			$ii++;
		}


		if(
			!$bAgencyReceipt || (
				$bAgencyReceipt &&
				$oSchool->netto_column != 1 &&
				!$bPaymentReceipt
			)
		) {
			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($aTotal['brutto'], $oInquiry->getCurrency(), $oSchool->id);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;
		}

		if(
			$bAgencyReceipt &&
			$oSchool->netto_column != 1 &&
			!$bPaymentReceipt
		) {
			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($aTotal['provision'], $oInquiry->getCurrency(), $oSchool->id);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;
		}

		if($bAgencyReceipt) {
			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($aTotal['netto'], $oInquiry->getCurrency(), $oSchool->id);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;
		}

		// Bezahlt (Zahlung)
		if($bPaymentReceipt) {
			$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($aTotal['payed_in_payment'], $oInquiry->getCurrency(), $oSchool->id);
			$aBody[$i][$ii]['align'] = 'R';
			$ii++;
		}

		$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($aTotal['payed'], $oInquiry->getCurrency(), $oSchool->id);
		$aBody[$i][$ii]['align'] = 'R';
		$ii++;

		$aBody[$i][$ii]['text'] = Ext_Thebing_Format::Number($aTotal['balance'], $oInquiry->getCurrency(), $oSchool->id);
		$aBody[$i][$ii]['align'] = 'R';
		$ii++;
		$i++;
		## ENDE

		$aTable['body'] = $aBody;

		return $aTable;

	}

	// Funktion sucht in der zwischentabelle nach bereits vorhandenen Documenten und falls gefunden returnt es dieses
	public function searchDocumentsPaymentDocuments($sType){
		$oInquiry = $this->getInquiry();
		
		$aAllDoc = $oInquiry->getDocuments($sType, true, true);

		//Zwischentabell auslesen
		$aKeys = array('document_id' => $this->id);
		$aPaymentDocuments = DB::getJoinData('kolumbus_inquiries_documents_paymentdocuments', $aKeys, 'payment_document_id');


		foreach((array)$aAllDoc as $oDoc){
			if(in_array($oDoc->id, $aPaymentDocuments)){
				return $oDoc;
			}
		}

		return false;
	}

	public function checkDocumentType($sType){
		
		$mType = Ext_Thebing_Inquiry_Document_Search::getTypeData($sType);

		if(
			is_array($mType) &&
			in_array($this->type, $mType)
		){
			return true;
		} else if(
			!is_array($mType) &&
			$this->type == $mType
		){
			return true;
		}

		return false;
	}

	/**
	 * Creditnote-Verrechnung speichern (relevant für Agenturzahlungen)
	 */
	public function saveCreditnotePayment($fAmount, $fCurrencyFactor, Ext_Thebing_Inquiry_Payment $oPayment, Ext_Thebing_Agency_Payment $oAgencyPayment = null) {

		if($this->type !== 'creditnote') {
			throw new BadMethodCallException('Creditnote payment is only possible for creditnotes');
		}

		$oInquiry = Ext_TS_Inquiry::getInstance($oPayment->inquiry_id);
		$iInquiryCurrency = $oInquiry->getCurrency();
		$iSchoolCurrency = $oInquiry->getSchool()->getCurrency();

		$oCNPayment = Ext_Thebing_Inquiry_Payment::getInstance();
		$oCNPayment->creator_id = $oPayment->creator_id;
		$oCNPayment->inquiry_id = $this->getInquiry()->id;
		$oCNPayment->method_id = $oPayment->method_id;
		$oCNPayment->sender = $oPayment->sender;
		$oCNPayment->receiver = $oPayment->receiver;
		$oCNPayment->grouping_id = $oPayment->grouping_id;
		$oCNPayment->date = $oPayment->date;
		$oCNPayment->type_id = 4; // Immer Typ CN Auszahlung; wird bei Agenturzahlungen auch für Kalkulation verwendet!
		$oCNPayment->amount_inquiry = $fAmount * -1;
		$oCNPayment->amount_school = ($fAmount * -1) * $fCurrencyFactor;
		$oCNPayment->currency_inquiry = $iInquiryCurrency;
		$oCNPayment->currency_school = $iSchoolCurrency;

		// Kommentar ergänzen
		$sComment = $oPayment->comment;
		if(!empty($sComment)) {
			$sComment .= ' - ';
		}
		$sComment .= L10N::t('Benutzt für die Bezahlung von Schüler {student_number} (Rechnung {invoice_number})', 'Thebing » Inquiry » Documents');
		$aReplaceVars = [$oInquiry->getCustomer()->getCustomerNumber(), $this->getParentDocument()->document_number];
		$sComment = str_replace(['{student_number}', '{invoice_number}'], $aReplaceVars, $sComment);

		$oCNPayment->comment = $sComment;

		$mValidate = $oCNPayment->validate();
		if($mValidate !== true) {
			return $mValidate;
		}

		$oCNPayment->save();

		// Relation zur Agenturzahlung speichern, damit Auszahlung in der Übersichts-GUI angezeigt wird
		if ($oAgencyPayment) {
			$oCNPayment->saveAgencyPaymentId($oAgencyPayment->id);
		}

		$oCNPayment->updateDocumentRelation($this->id);

		// Beträge verteilen
		$aItems = $this->getLastVersion()->getItemObjects(true);
		$oAllocationService = new Ext_TS_Payment_Item_AllocateAmount($aItems, $fAmount);
		$aAllocatedAmounts = $oAllocationService->allocateAmounts();

		// Overpayment darf es nicht geben, hier ist etwas schief gelaufen
		if($oAllocationService->hasOverPayment()) {
			throw new RuntimeException('Creditnote '.$this->id.' has impossible overpayment');
		}

		// Payment-Items speichern
		foreach($aAllocatedAmounts as $iItemId => $fItemAmount) {

			$oCNPaymentItem = Ext_Thebing_Inquiry_Payment_Item::getInstance();
			$oCNPaymentItem->payment_id = $oCNPayment->getId();
			$oCNPaymentItem->item_id = $iItemId;
			$oCNPaymentItem->amount_inquiry = $fItemAmount * -1;
			$oCNPaymentItem->amount_school = ($fItemAmount * -1) * $fCurrencyFactor;
			$oCNPaymentItem->currency_inquiry = $iInquiryCurrency;
			$oCNPaymentItem->currency_school = $iSchoolCurrency;

			$mValidate = $oCNPaymentItem->validate();
			if($mValidate !== true) {
				return $mValidate;
			}

			$oCNPaymentItem->save();
		}

		// CN muss wegen Bezahlspalten aktualisiert werden
		Ext_Gui2_Index_Stack::add('ts_document', $this->id, 0);

		return $oCNPayment;

	}

	/**
	 * Gibt den zugewiesenen bezahlten Betrag zurück
	 *
	 * Beim Verrechnen oder Ausbezahlen einer Creditnote werden negative Beträge gespeichert.
	 * Da die Methode den zugewiesenen bezahlten Betrag zurückgibt, muss dieser Betrag positiv sein.
	 * Das entspricht ebenfalls der Vorgehensweise der alten Methode.
	 *
	 * @return float|int
	 */
	public function getAllocatedAccountingAmount() {

		$fAmount = $this->getPayedAmount() * -1;

		return $fAmount;
	}

	/**
	 * @TODO Diese Methode sollte dringend so refaktorisiert werden, damit diese auch außerhalb eines GUI-Kontextes (Useraktion) funktioniert
	 *
	 * Wandelt eine Proforma in ein Rechnungsdokument um
	 *
	 * @param string $sComment
	 * @param int|bool $iNumberRangeId
	 * @param string $sDocNumber
	 * @return array|Ext_Thebing_Inquiry_Document|Ext_Thebing_Inquiry_Document_Numberrange_List
	 */
	public function convertProformat2InquiryDocument($sComment = '', $iNumberRangeId = false, $sDocNumber = '', $bGuiContext = true, $createPdf=true, DateTimeInterface $date = null) {

		$oInquiry = $this->getInquiry();
		$oSchool = $oInquiry->getSchool();

		if($this->isConvertedProforma()) {
			return [L10N::t('Die Proforma wurde bereits umgewandelt.', 'Thebing » Errors')];
		} elseif ($bGuiContext && !$oInquiry->isConfirmed()) {
			return [L10N::t('Nicht bestätigte Buchungen können nicht umgewandelt werden.', 'Thebing » Errors')];
		} elseif (
			$oInquiry->hasDraft([], null, false)
		) {
			return [L10N::t('Es existiert bereits ein Entwurf.', 'Thebing » Errors')];
		} else if (
			($oSchool->invoice_amount_null_forbidden ?? 0) == 1 &&
			(float)$this->getAmount() == 0
		) {
			return [L10N::t('Der Rechnungsbetrag darf nicht 0 sein.', 'Thebing » Errors')];
		}

		$sToType = $this->type === 'proforma_netto' ? 'netto' : 'brutto';

		if(!$iNumberRangeId) {

			$oNumberRangeList = new Ext_Thebing_Inquiry_Document_Numberrange_List();
			$oNumberRangeList->sDocumentType = $sToType;
			$oNumberRangeList->is_credit = $this->is_credit;
			$oNumberRangeList->iOldId = (int)$this->id;

			$oNumberRangeList->iSchoolId = (int)$oSchool->id;
			$oNumberRangeList->setInquiry($oInquiry);

			// Dialog IMMER anzeigen, da es ohne den Dialog beim Umwandeln von Gruppen-Proformas diverse Probleme gibt
			//if($oNumberRangeList->canShowListDialog()) {
				return $oNumberRangeList;
			//}

		}

		$cloneErrors = function (array $return) {
			if(empty($return)) {
				// Hab mal irgendwo in der WDBasic gesehen, das auch mal "false" zurückgegeben wird, wir geben zur Not
				// hier einen befüllten array zurück, ein leeres array würde uns nichts bringen
				$return = ['CONVERT_ERROR'];
			}
			// Fehlermeldungen konvertieren, da das nirgends passiert
			$documentGui = new Ext_Thebing_Document_Gui2(new Ext_Gui2());
			foreach($return as &$sError) {
				$sError = $documentGui->getErrorMessage($sError, '');
			}
			return $return;
		};

		$mClone = $this->cloneDocument($sToType, $sComment, $iNumberRangeId, $oInquiry, $sDocNumber, 0, $createPdf, $date);

		if(is_array($mClone)) {
			return $cloneErrors($mClone);
		}

		// Wenn die Proforma eine Provisionsgutschrift hat, dann diese auch umwandeln
		$oCreditNote = $this->getCreditNote();
		if($oCreditNote !== null) {

			$aCreditnoteNumberranges = \Ext_Thebing_Inquiry_Document_Numberrange::getNumberrangesByType('creditnote', false, false, $oSchool->id, true);
			$oCreditnoteNumberrange = reset($aCreditnoteNumberranges);
			$sCreditnoteNumber = $oCreditnoteNumberrange->generateNumber();

			$mCloneCreditNote = $oCreditNote->cloneDocument('creditnote', $sComment, $oCreditnoteNumberrange->id, $oInquiry, $sCreditnoteNumber, 0, $createPdf);

			if (is_array($mCloneCreditNote)) {
				$mClone->document_number = '';
				$mClone->delete();
				return $cloneErrors($mCloneCreditNote);
			}

			$mClone->child_documents_creditnote = [$mCloneCreditNote->id];
			$mClone->save();

		}

		$this->log(Ext_Thebing_Log::DOCUMENT_CONVERT_PROFORMA_TO_INVOICE);

		$bHadInvoice = $oInquiry->has_invoice;
		if (!$mClone->isDraft()) {
			$oInquiry->setInquiryStatus($sToType, false);
		}
//		$oInquiry->confirm();
		$oInquiry->save();

		$oLastVersion = $mClone->getLastVersion();

		if(
			is_object($oLastVersion) &&
			$oLastVersion instanceof Ext_Thebing_Inquiry_Document_Version
		) {

			// Special-Index-Spalten in den Items auch aktualisieren
			$oLastVersion->updateSpecialIndexFields();

			// Beträge aktualisieren damit diese korrekt angezeigt werden (#9358)
			// Dies sorgt für enorm viele Indexaktualisierungen!
			$oInquiry->getAmount(false, true);
			$oInquiry->getAmount(true, true);
			//$oInquiry->getCreditAmount(true);

			if (
				!$bHadInvoice &&
				!$mClone->isDraft()
			) {
				Ext_Thebing_Document::reallocatePaymentAmounts($oInquiry, $oLastVersion->getItemObjects(true));
			}

		}

		$mClone->initUpdateTransactions();
		
		/*
		 * Die Proforma mit Prio 0 (sofort) in den Stack packen damit der Eintrag sofort aktualisiert wird,
		 * ansonsten landet der Eintrag mit Prio 1 im Stack und beim Aktualisieren des Dialogs sind dann die
		 * Proforma und die generierte Rechnung als aktuelles Dokument markiert (#9414).
		 */
		Ext_Gui2_Index_Stack::add('ts_document', $this->id, 0);

		return $mClone;
	}
	
	/*
	 * Prüft ob ein Dokument dieses Types erstellt werden darf
	 */
	public function checkDocument(){
		
//		$iInquiry = (int)$this->inquiry_id;
		$iInquiry = $this->getInquiry()->id;
		$mErrors = array();


		if($iInquiry > 0){ 
			switch($this->type){
			case 'proforma_brutto':
			case 'proforma_netto':
				// Es darf nur EINE Proforma geben
				$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($iInquiry, $this->type, true, true);
				$aInvoice	= Ext_Thebing_Inquiry_Document_Search::search($iInquiry, array('brutto','netto'), true, true);

				if(
					count($aDocuments) > 0 &&
					$this->id == 0
				){
					$aError = array();
					$aError[] = 'INVALID_PROFORMA';
					$mErrors[] = $aError;
				}
				elseif(
					count($aInvoice) > 0 &&
					$this->id == 0
				){
					$aError = array();
					$aError[] = 'INVOICE_EXISTS';
					$mErrors[] = $aError;
				}
				break;
			case 'brutto':
			case 'netto':
				
				// Es darf NIE 2 Dokumente des selben Typs geben die aufeinander folgen
				if($this->type == 'brutto' || $this->type == 'netto'){
					$mType = array(
						$this->type,
						'credit_' . $this->type
					);
				}else{
					$mType = $this->type;
				}
				
				// Es darf NIE 2 Dokumente des selben Typs geben die aufeinander folgen
//				$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($iInquiry, $mType, true, true);
				$oSearch = new Ext_Thebing_Inquiry_Document_Search($this->entity_id);
				$oSearch->setType($mType);
				$oSearch->setObjectType($this->entity);
				$aDocuments = $oSearch->searchDocument();
				
				if(
					count($aDocuments) > 0 &&
					$this->id == 0
				){
					$oLastDocument = reset($aDocuments);

					if(
						$oLastDocument->type == $this->type &&
						$oLastDocument->is_credit == $this->is_credit
					) {
						$aError = array();
						$aError[] = 'INVALID_INVOICE';
						$mErrors[] = $aError;
					}
				}
			}
		}
		
		if(empty($mErrors)){
			$mErrors = true;
		}
		
		return $mErrors;
	}
	
	
	/*
	 * Validate Funktion
	 */
	public function validate($bThrowExceptions = false) {
		
		$aErrors = parent::validate($bThrowExceptions);

		// Hier muss geprüft werden ob ein Dokument von diesem Typ überhaupt erstellt werden darf
		$bDocumentTypeCheck = $this->checkDocumentType($this->type);

		if($bDocumentTypeCheck == true){
			// Dokument Typ korrekt
			// prüfen ob Document dieses Types erstellt werden darf
			$mDocumentTypeCheck2 = $this->checkDocument();

			if($mDocumentTypeCheck2 !== true){
				if(is_array($aErrors)){ 
					$aErrors = array_merge((array)$aErrors, (array)$mDocumentTypeCheck2);
				}else{
					$aErrors = $mDocumentTypeCheck2;
				}
				
			}

		}else{
			// Kein korrekter Dokument Typ vorhanden
			$aError = array();
			$aError[] = 'INVALID_DOCUMENT_TYPE';
			if(is_array($aErrors)){
				$aErrors = array_merge((array)$aErrors, (array)$aError);
			}else{
				$aErrors = $aError;
			}
			
		}

		return $aErrors;
	}

	public function getPositionColumnsType($iPositionsView) {

		$oInquiry = $this->getInquiryAbstract();
		
		$sType = 'gross';
		if(strpos($this->type, 'netto') !== false) {
			$sType = 'net';
		} elseif($this->type == 'storno') {
			
			// Bezahlmethode muss über das Inquiry bestimmt werden und NICHT über den Doc typ #1854
			if($oInquiry->hasNettoPaymentMethod()) {
				$sType = 'net';
			}

		} elseif(strpos($this->type, 'creditnote') !== false) {
			$sType = 'creditnote';
		} elseif($this->type == 'additional_document') {
			
			if(
				(
					$iPositionsView == 1 ||
					$iPositionsView == 3
				)&&(
					$oInquiry->hasNettoPaymentMethod()	
				)
			) {
				$sType = 'net';
			}
			
		}
		
		return $sType;
		
	}
	
	/**
	 * NOCH NICHT FERTIG!
	 */
	public function getPositionColumns($sLanguage, $iPositionsView=1) {
		
		/**
		 * Daten vorbereiten
		 */
		$oInquiry = $this->getInquiry();
		$oSchool = $oInquiry->getSchool();
		
		$iTax = $oSchool->getTaxStatus();
		$aTaxSettings = $oSchool->getTaxExclusive();

		$bCommissionColumn = $oSchool->commission_column;
		$bOnlyNetColumn = $oSchool->netto_column;

		$bGroup = false;
		$bGroup = $oInquiry->hasGroup();

		$sType = 'gross';
		if(strpos($oDocument->type, 'netto') !== false) {
			$sType = 'net';
		} elseif($oDocument->type == 'storno') {
			
			// Typ der letzten Rechnung ermitteln, um Brutto/Netto zu ermitteln
			$oInvoice = $oInquiry->getDocuments('invoice_without_storno', false, true);
			if(strpos($oInvoice->type, 'netto') !== false) {
				$sType = 'net';
			}

		} elseif(strpos($oDocument->type, 'creditnote') !== false) {
			$sType = 'net_creditnote';
		} elseif($oDocument->type == 'additional_document') {
			
			if(
				$iPositionsView == 1 && 
				(
					$oInquiry->hasNettoPaymentMethod()	
				)
			) {
				$sType = 'net';
			}
			
		}
		
		/**
		 * Spalten festlegen
		 */
		$aPositionColumns = array();
		if($bGroup) {
			$aPositionColumns[] = 'quantity';
		}
		
		$aPositionColumns[] = 'description';
		
		if(
			!(
				(
					strpos($sType, 'netto') !== false ||
					strpos($sType, 'creditnote') !== false ||
					$iPositionsView == 3
				) &&
				$bOnlyNetColumn == 1
			) &&
			!(
				strpos($sType, 'creditnote') !== false &&
				$oSchool->commission_column == 1
			)
		){
			$aPositionColumns[] = 'amount';
		}

		
		
		Ext_Thebing_L10N::t('Anzahl', $sLanguage, 'Thebing » PDF');
		Ext_Thebing_L10N::t('Position', $sLanguage, 'Thebing » PDF');
		Ext_Thebing_L10N::t('Betrag', $sLanguage, 'Thebing » PDF');
		

	}

	/**
	 * Diese Methode beachtet Stornos nicht!
	 *
	 * @use Ext_Thebing_Inquiry_Document::isNetto()
	 * @deprecated
	 * @return bool
	 */
	public function isNettoDocument()
	{
		$sType = $this->type;

		if(
				strpos($sType, 'netto') !== false
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Speicher die ID der letzten Version 
	 */
	public function saveLatestVersion() {

		// Speichern der Version im Document
		$latestVersionId = $this->getLastVersion(false)?->id;

		// Wenn keine Version mehr ermittelt werden konnte (eventuell gelöscht) wird der Wert nicht überschrieben!
		if(empty($latestVersionId)) {
			return;
		}
		
		$this->latest_version = $latestVersionId;
		
		$sSql = "
			UPDATE
				#table
			SET
				`latest_version` = :latest_version
			WHERE
				`id` = :id
			";
		$aSql = array(
			'table' => $this->_sTable,
			'latest_version' => $this->latest_version,
			'id' => $this->id
		);
		DB::executePreparedQuery($sSql, $aSql);

		$this->log('SET_LATEST_VERSION');

	}

	/**
	 * Parent Dokument Verbindung abspeichern
	 * 
	 * @param int $iParentId
	 * @param bool
	 * @return bool
	 */
	public function saveParentRelation($iParentId, $bSave=true)
	{
		$oInquiry	= $this->getInquiry();
		
		if(!$oInquiry instanceof Ext_TS_Inquiry)
		{
			// Verbindungen zwischen Dokumenten gibt es nur bei Buchungsbezogenen Dokumenten
			return false;
		}
		
		$sRelationKey = $this->getRelationParentKey();

		if(!$sRelationKey)
		{
			return false;
		}

		// Jede weitere Version würde dafür sorgen, dass das Dokument mit sich selbst verknüpft wird und die eigentliche Relation unwiderbringlich überschrieben wird
		if ($this->id == $iParentId) {
			return false;
		}
		
		// Bei storno gibt es nicht nur ein abhängiges Dokument, sondern mehrere
		// Wenn hier mal $iParentId verwendet werden sollte, wird das nicht funktionieren!
		if($this->type == 'storno')
		{	
			$aSearchTypes	= self::getRelationSearchTypesForType($this->type);
			
			$aDocs			= (array)Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, 'invoice_without_proforma', true);
			
			$aParentDocs	= self::searchByTypes($aDocs, $aSearchTypes, true, true, array());
			
			$aParentIds		= array();
			
			foreach($aParentDocs as $aParentDoc)
			{
				$aParentIds[] = $aParentDoc['id'];
			}
		}
		else
		{
			$aParentIds = array($iParentId);
		}
		
		$sJoinKey	= 'parent_documents_' . $sRelationKey;

		$this->$sJoinKey = $aParentIds;

		if($bSave) {
			// @TODO Das ist Dreck! #5819
			$this->save(true);
		}

		return true;
	}
	
	/**
	 * Nach Verbindungen andhand verschiedenen Dok-Typen Dokumente suchen
	 * 
	 * @param array $aOtherDocuments
	 * @param array $aSearchTypes
	 * @param bool $bFilterCredit
	 * @param bool $bMany
	 * @param array $aOptions
	 * @return array 
	 */
	public static function searchByTypes(array $aOtherDocuments, array $aSearchTypes, $bFilterCredit, $bMany, $aOptions) {

		$aFoundDocs	= array();
		$aTemp = $aOtherDocuments;
		$iIgnoreId = 0;
		
		foreach($aOtherDocuments as $iKey => $aOtherDocument) {
			unset($aTemp[$iKey]);
			
			$iOtherId	= (int)$aOtherDocument['id'];
			$sTypeOther = $aOtherDocument['type'];
			$iIsCredit	= (int)$aOtherDocument['is_credit'];
			
			if(
				isset($aSearchTypes[$sTypeOther]) &&
				$iOtherId != $iIgnoreId
			) {
				// Gutschrift und entsprechendes Parent exkludieren (wenn $bFilterCredit), da diese sich aufheben
				if(
					$bFilterCredit &&
					$iIsCredit == 1
				) {
					// Suche nach der Verbindung der Gutschrift, um diese auch auszufiltern
					$aSearch = self::searchByTypes($aTemp, array(
						$sTypeOther => 1
					), true, false, array(
						'relation_key'	=> 'credit'
					));

					if($aSearch) {
						// Übernehme im späteren Durchlauf diese ID nicht mehr
						$aSearch = reset($aSearch);
						$iIgnoreId	= $aSearch['id'];
					}
				} else {
					// Zusammenhang zwischen Dokumenten merken (Gutschrift, Differenz, Storno)
					$aOtherDocument = array_merge($aOtherDocument, $aOptions);
					
					// Zusammenhang gefunden
					$aFoundDocs[] = $aOtherDocument;
					
					if(!$bMany) {
						// Manche Verbindungen können nur 1-zu-1 sein
						break;
					}
				}
			}
		}
		
		return $aFoundDocs;
	}
	
	/**
	 * Rausfinden für welches Dokumenttyp andere Dokumenttypen gesucht werden muss(für die Verbindung)
	 * 
	 * @param string $sType
	 * @return array 
	 */
	public static function getRelationSearchTypesForType($sType)
	{
		switch($sType)
		{
			case 'brutto_diff':
			case 'netto_diff':
			case 'brutto_diff_special':
				
				$aSearchTypes = array(
					// Auf Differenzrechnungen kann man Differenzrechnungen anlegen
					'brutto_diff' => 1,
					'netto_diff' => 1,
					'brutto_diff_special' => 1,
					// Auf Brutto/Netto Rechnungen kann man Differenzrechnungen anlegen
					'brutto' => 1,
					'netto' => 1,
				);
				
				break;
			
			case 'storno':
				
				$aSearchTypes = array(
					// Differenzrechnungen kann man stornieren
					'brutto_diff' => 1,
					'netto_diff' => 1,
					'brutto_diff_special' => 1,
					// Brutto/Netto Rechnungen kann man stornieren
					'brutto' => 1,
					'netto' => 1,
				);
				
				break;
			
			default:
				
				$aSearchTypes = array();
				
				break;
		}
		
		return $aSearchTypes;
	}
	
	public function getRelationParentKey()
	{
		$sRelationKey = null;

		if(
			// Bei der Gutschrift einer CN wurde diese noch nie direkt mit der CN verknüpft
			// $iParentId in saveParentRelation kann auch niemals eine CN sein
			$this->is_credit &&
			$this->type !== 'creditnote'
		) {
			$sRelationKey = 'credit';
		} else {
			switch($this->type) {
				case 'brutto_diff':
				case 'netto_diff':
				case 'brutto_diff_special':
					$sRelationKey = 'diff';
					break;
				case 'storno':
					$sRelationKey = 'cancellation';
					break;
				case 'manual_creditnote':
				case 'creditnote':
				case 'proforma_creditnote':
					$sRelationKey = 'creditnote';
					break;
				case 'creditnote_subagency':
					$sRelationKey = 'creditnote_subagency';
					break;
				default:
					break;
			}
		}
		
		return $sRelationKey;
	}

	public function getMainParentDocument() {
		return $this->getParentDocument(true);
	}
	
	/**
	 * @return self|bool
	 */
	public function getParentDocument($returnFirstCancellationParent=false) {
		
		$oParentDocument = false;
		
		$sRelationKey = $this->getRelationParentKey();

		if($sRelationKey) {

			$sJoinKey = 'parent_documents_' . $sRelationKey;
			$aParentIds = $this->$sJoinKey;

			sort($aParentIds);

			if(
				$this->type == 'storno' &&
				$returnFirstCancellationParent === false
			) {

				// Immer jüngstes Dokument nehmen wegen brutto/netto-Mix (und nicht auf DB-Reihenfolge verlassen)
				foreach($aParentIds as $iTempDocId) {
					$oDoc = self::getInstance($iTempDocId);

					if(
						(
							strpos($oDoc->type, 'brutto') !== false ||
							strpos($oDoc->type, 'netto') !== false
						) &&
						$oDoc->is_credit != 1
					) {
						$iParentId = $iTempDocId;
					}
				}

			} else {
				$iParentId = (int)reset($aParentIds);
			}
			
			if($iParentId > 0) {
				$oParentDocument = self::getInstance($iParentId);
			}
			
		}
		
		return $oParentDocument;
	}
	
	/**
	 *
	 * @return Ext_Thebing_Agency_Manual_Creditnote
	 */
	public function getManualCreditnote()
	{
		$oManualCreditnote = false;
		
		if($this->type == 'manual_creditnote')
		{
			$aManualCreditnotes		= $this->getJoinTableObjects('manual_creditnotes');
		
			if(!empty($aManualCreditnotes))
			{
				$oManualCreditnote		= reset($aManualCreditnotes);
			}
		}
		
		return $oManualCreditnote;
	}

	/**
	 * Beim Speichern eines Dokumentes muss der Stack immer befüllt werden, da immer eine neue
	 * Version generiert wird und die Verbindung neu gebildet werden muss
	 *
	 * @param bool $bInsert
	 * @param bool $bChanged
	 * @return bool|void
	 * @throws ErrorException
	 */
	public function updateIndexStack($bInsert = false, $bChanged = false) {

		if($this->bUpdateIndexEntry) {
			Ext_Gui2_Index_Stack::add('ts_document', $this->id, 0);

			// Parent-Documents auch aktualisieren (z.B. Hauptdokument einer CN)
			// Das passierte zuvor über die Registry, aber da steht die Prio nun auf 1
			$aParentDocuments = (array)$this->parent_documents;
			foreach($aParentDocuments as $iParentId) {
				Ext_Gui2_Index_Stack::add('ts_document', $iParentId, 0);
			}
		}

	}

	public function getListQueryDataForIndex($oGui = null) {

		$aQueryData = [];

		$aQueryData['sql'] = "
			SELECT
				`kid`.`id`,
				`kid`.`created`
			FROM
				`kolumbus_inquiries_documents` `kid` INNER JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kidv`.`id` = `kid`.`latest_version` AND
					`kidv`.`active` = 1 LEFT JOIN
				`ts_inquiries` `ts_i` ON
				    `kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
					`ts_i`.`id` = `kid`.`entity_id` AND
					`ts_i`.`active` = 1 LEFT JOIN
				(
					`ts_inquiries_journeys` `ts_ij` INNER JOIN
					`ts_inquiries` `ts_i2`
				) ON
					 `kid`.`entity` = '".Ext_TS_Inquiry_Journey::class."' AND
					 `ts_ij`.`id` = `kid`.`entity_id` AND
					 `ts_ij`.`active` = 1 AND
					 `ts_i2`.`id` = `ts_ij`.`inquiry_id` AND
					 `ts_i2`.`active` = 1
			WHERE
				`kid`.`active` = 1 AND
				`kid`.`type` IN (:types) AND (
					`ts_i`.`id` IS NOT NULL OR
					`ts_i2`.`id` IS NOT NULL OR
					`kid`.`type` = 'manual_creditnote'
				)
			ORDER BY
				`kid`.`created` DESC
		";

		$aQueryData['data'] = [
			// Bezahlbelege, Transcripts usw. sind im Index unnötig
			'types' => \Ext_Thebing_Inquiry_Document_Search::getTypeData(['invoice_with_creditnote_and_manual_creditnote', 'offer', 'additional_document'])
		];

		return $aQueryData;

	}

	/**
	 * Falls das Dokument einer Buchung gebunden ist, Schul-ID zurück liefern
	 * 
	 * @return int 
	 */
	public function getSchoolId()
	{
		$iSchoolId	= null;
		
		if($this->type != 'manual_creditnote')
		{
			$oInquiry	= $this->getEntity();

			if($oInquiry instanceof \Ts\Interfaces\Entity\DocumentRelation)
			{
				$oSchool	= $oInquiry->getSchool();
				$iSchoolId	= (int)$oSchool->id;	
			}
			else
			{
				$oEnquiry	= $this->getEnquiry();
				
				if($oEnquiry)
				{
					$oSchool	= $oEnquiry->getSchool();
					$iSchoolId	= (int)$oSchool->id;	
				}
			}
			
		} else {
			
			$oCreditNote    = $this->getManualCreditnote();
			
			if($oCreditNote)
			{
				$iSchoolId      = $oCreditNote->getSchoolId();
			}

		}
		
		return $iSchoolId;
	}
	
	/**
	 * Falls Rechnung dann die Agentur der Buchung, falls m.Creditnote die Agentur selber
	 * 
	 * @return Ext_Thebing_Agency | null 
	 */
	public function getAgency()
	{
		$oAgency = null;
		
		$oManualCreditnote = $this->getManualCreditnote();
		
		if($oManualCreditnote)
		{
			$oAgency				= $oManualCreditnote->getAgency();
		}
		else
		{
			$oAllocationObject		= $this->getInquiry();
			
			if($oAllocationObject)
			{
				$oAgency	= $oAllocationObject->getAgency();
			}
			
		}
		
		return $oAgency;
	}
	
	/**
	 * Rechnungsbetrag oder manuelle Creditnote Betrag
	 *
	 * @param bool $bGroup Gruppenbetrag berücksichtigen
	 * @return float 
	 */
	public function getAmountForIndex($bGroup=false)
	{
		$fAmount			= null;
		
		$oManualCreditnote	= $this->getManualCreditnote();
		
		if($oManualCreditnote)
		{
			$fAmount		= $oManualCreditnote->amount;
		}
		else
		{
			$oLastVersion = $this->getLastVersion();
			
			if($oLastVersion)
			{
				$oInquiry = $this->getInquiry();

				if(
					$bGroup &&
					$oInquiry instanceof Ext_TS_Inquiry &&
					$oInquiry->hasGroup()
				) {
					$fAmount = $oLastVersion->getGroupAmount();
				} else {
					$fAmount = $oLastVersion->getAmount();
				}
			}
		}
		
		return $fAmount;
	}
	
	/**
	 * Datumsfeld einer letzten Version zurück geben
	 * 
	 * @return string 
	 */
	public function getLastVersionDateField($sColumn, $sDefaultType = false)
	{
		$sDate			= null;
		$oLastVersion	= $this->getLastVersion();
		
		if($oLastVersion)
		{
			if($sDefaultType && $this->type == 'manual_creditnote')
			{
				$sDate = $this->_getDefaultDate($sDefaultType);
			}
			else
			{
				if($oLastVersion->$sColumn != '0000-00-00')
				{
					$sDate = $oLastVersion->$sColumn;
				}	
			}
		}

		return $sDate;
	}
	
	/**
	 * Leistunsdatum einer Buchung
	 * 
	 * @return string 
	 */
	public function getServiceDate($sColumn)
	{
		$sKey			= 'service_' . $sColumn;
		
		$sServiceDate	= null;
		
		if($this->type == 'manual_creditnote')
		{			
			$sServiceDate = $this->_getDefaultDate($sColumn);
		}
		else
		{
			$oInquiry		= $this->getInquiry();

			if($oInquiry && $oInquiry->$sKey != '0000-00-00')
			{
				$sServiceDate = $oInquiry->$sKey;
			}	
		}
		
		return $sServiceDate;
	}
	
	/**
	 * Standardwert wenn kein Datum generiert werden kann
	 *
	 * @param string $sType
	 * @return string 
	 */
	protected function _getDefaultDate($sType)
	{
		$oDate = new WDDate();

		if($sType == 'from')
		{
			$oDate->sub(10, WDDate::YEAR);
		}
		else
		{
			$oDate->add(10, WDDate::YEAR);
		}

		$sDate = $oDate->get(WDDate::DB_DATE);
		
		return $sDate;
	}
	
	/**
	 * Bei manuellen Creditnotes das bis-Datum(10 Jahre später) übernehmen, damit der Datensatz im Filter
	 * mit Typ "contact" immer gefunden wird & bei allen anderen wo ein Service-Zeitraum existiert erneut das
	 * von-Datum übernehmen, denn wenn from==until dann kann man den "between" filter auch als "contact" filter benutzen,
	 * so können beide Datensätze über ein Filter perfekt gefunden werden
	 * 
	 * @return string 
	 */
	public function getServiceFromClone()
	{
		if($this->type == 'manual_creditnote')
		{
			$mValue = $this->getServiceDate('until');
		}
		else
		{
			$mValue = $this->getServiceDate('from');
		}
		
		return $mValue;
	}
	
	/**
	 * Damit man im Filter danach filtern kann, wandeln wir den Wert von Bool auf Int um
	 * 
	 * @return int 
	 */
	public function isReleasedForIndex()
	{
		if($this->isReleased())
		{
			return 'released';
		}
		else
		{
			return 'not_released';
		}
	}
	
	/**
	 * Dokumenten-Typ manipulieren für den Index (type_status)
	 *  
	 * @return string[]
	 */
	public function getTypeForIndex() {

		if ($this->is_credit) {
			return ['credit'];
		}

		if ($this->type === 'creditnote') {
			$oParentDocument = $this->getParentDocument();
			if (
				$oParentDocument &&
				$oParentDocument->type == 'storno'
			) {
				// Typ überschreiben (gibt es irgendwie schon ewig?)
				return ['creditnote_cancellation'];
			}
		}

		// Bei umgewandelten Angebot zusätzlichen Typ ergänzen
		if (
			$this->isOffer() &&
			!empty($this->child_documents_offer)
		) {
			return [$this->type, 'offer_converted'];

		}

		if ($this->isConvertedProforma()) {
			return [$this->type, 'proforma_converted'];
		}


		return [$this->type];

	}

	/**
	 * Liefert den Typ des Templates dieses Dokuments
	 * @return string
	 */
	public function getTemplateType() {
		$oVersion = $this->getLastVersion(true);
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($oVersion->template_id);
		return $oTemplate->type;
	}

	/**
	 * Liefert eine Liste der Inboxen des Templates dieses Dokuments
	 */
	public function getTemplateInboxes() {
		$oVersion = $this->getLastVersion(true);
		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($oVersion->template_id);
		return (array)$oTemplate->inboxes;
	}

	/**
	 * Währung der Creditnote oder der Buchung
	 * 
	 * @return int 
	 */
	public function getCurrencyId()
	{
		$iCurrencyId			= null;
			
		$oManualCreditnote		= $this->getManualCreditnote();
		
		if($oManualCreditnote)
		{
			$iCurrencyId		= $oManualCreditnote->currency_id;
		}
		else
		{
			$oInquiry			= $this->getInquiry();
			
			if($oInquiry)
			{
				$iCurrencyId	= $oInquiry->getCurrency();
			}
		}
		
		return $iCurrencyId;
	}
	
	/**
	 * Überprüfen ob eine Buchung vorhanden und ob Buchung Direkt/Agenturbuchung
	 * 
	 * @return string 
	 */
	public function getBookingType()
	{
		$sBookingType	= null;
		
		$oInquiry		= $this->getInquiry();
		
		if($oInquiry)
		{
			if($oInquiry->hasAgency())
			{
				$sBookingType	= 'agency';
			}
			else
			{
				$sBookingType	= 'customer';
			}
		}
		
		return $sBookingType;
	}

	/**
	 * @param array $aIgnoreErrors
	 * @param array $aDocumentsForReleaseIds Dokumente (IDs), die auch freigegeben werden
	 * @param int $iCreatorId
	 * @return bool
	 * @throws ErrorException
	 * @throws Exception
	 * @throws Ext_TS_Accounting_Bookingstack_Generator_Exception
	 */
	public function releaseDocument(array $aIgnoreErrors, $aDocumentsForReleaseIds, $iCreatorId = null) {

		if($this->id > 0 && !$this->isReleased()) {

			$oParentDoc = $this->getParentDocument();

			if(
				empty(array_intersect(['parent_not_released', '*'], $aIgnoreErrors)) &&
				$oParentDoc &&
				!in_array($oParentDoc->id, $aDocumentsForReleaseIds) &&
				!$oParentDoc->isReleased()
			) {
				$this->_sHint = 'PARENT_NOT_RELEASED';
				$this->_sHintCode = 'parent_not_released';
				return false;
			}
			else {

				$oGenerator = new Ext_TS_Accounting_Bookingstack_Generator_Document($this, $aIgnoreErrors);
				$bSuccess = $oGenerator->createStack();

				if(!$bSuccess){
					return $bSuccess;
				}

				$this->insertRelease($iCreatorId);

				Ext_Gui2_Index_Stack::add('ts_document', $this->id, 0);

				// Bei Freigabe einer Creditnote
				// muss die Rechnung der Creditnote im Index
				// aktualisiert werden da sonst die Freigabe der Creditnote
				// nicht angezeigt wird
				if($this->type === 'creditnote' && $oParentDoc) {
					Ext_Gui2_Index_Stack::add('ts_document', $oParentDoc->id, 0);
				}

			}
			
			return true;
		}
		else {
			$this->_sError = 'DOCUMENT_RELEASED';
			return false;
		}
	}

	/**
	 * Dokumentenfreigabe löschen
	 */
	public function removeDocumentRelease() {

		$sSql = "
			DELETE FROM
				`ts_documents_release`
			WHERE
				document_id = :document_id
		";

		DB::executePreparedQuery($sSql, ['document_id' => $this->id]);

		Ext_Gui2_Index_Stack::add('ts_document', $this->id, 0);

		// Bei CN auch Elternrechnung aktualisieren, da Änderung sonst nicht angezeigt wird
		if($this->type === 'creditnote') {
			$oParentDocument = $this->getParentDocument();
			if($oParentDocument) {
				Ext_Gui2_Index_Stack::add('ts_document', $oParentDocument->id, 0);
			}
		}

	}

	/**
	 *
	 * @return Ext_Thebing_Client_Inbox | string 
	 */
	public function getInbox($bGetObject = false)
	{
		$mInbox = null;
		
		if($this->type != 'manual_creditnote')
		{
			$oInquiry = $this->getInquiry();
			
			if($oInquiry)
			{
				if($bGetObject){
					$mInbox = $oInquiry->getInbox();
				} else {
					$mInbox = (string)$oInquiry->inbox;
				}
				
			}
		} else {			
			 $oCreditnote           = $this->getManualCreditnote();
			 if($oCreditnote){
				 $mInbox                = $oCreditnote->getInbox();
				if(!$bGetObject && $mInbox){
					$mInbox            = $mInbox->short;
				}
			 }
		}
				
		return $mInbox;
	}

	/**
	 * @return bool
	 */
	public function isLastInquiryDocument() {

		if($this->type === 'additional_document') {
			return false;
		}

		$oInquiry = $this->getInquiry();

		if($oInquiry instanceof Ext_TS_Inquiry_Abstract) {

			$sType = 'invoice';
			if($oInquiry->has_invoice) {
				$sType = 'invoice_without_proforma';
			}

			$oDoc = $oInquiry->getDocuments($sType, false, true);
			if($oDoc && $oDoc->getId() == $this->getId()){
				return true;
			}

		}

		return false;

	}

	/**
	 * Betrag pro Service-Typ mit komischer Logik für Rechnungsübersicht
	 *
	 * @param string $service
	 * @param string $type
	 * @return float
	 */
	public function getServiceAmountForOverview(string $service, string $type): float {

		$amountType = match ($type) {
			'gross', 'discount' => 'brutto',
			'commission' => 'commission',
			'open' => 'netto',
		};

		$version = $this->getLastVersion();
		if (!$version) {
			return 0;
		}

		/** @var Ext_Thebing_Inquiry_Document_Version_Item[]|Illuminate\Support\Collection $items */
		$items = collect($version->getJoinedObjectChilds('items', true));
		$getAmount = function (Ext_Thebing_Inquiry_Document_Version_Item $item) use ($type, $amountType) {
			$amount = $item->getAmount($amountType, false);
			$discount = $amount * ($item->amount_discount / 100);
			return $type === 'gross' ? $amount : ($type === 'discount' ? $discount : $amount - $discount);
		};

		// Übliche Sonderbehandlung für Specials
		// index_special_amount_gross etc. können nicht verwendet werden, da der Betrag auch ohne Discount benötigt wird
		$deductions = [];
		foreach ($items as $item) {
			if ($item->onPdf && $item->type === 'special') {
				if (!isset($deductions[$item->parent_id])) {
					$deductions[$item->parent_id] = $getAmount($item);
				} else {
					$deductions[$item->parent_id] += $getAmount($item);
				}
			}
		}

		return $items
			->filter(function (Ext_Thebing_Inquiry_Document_Version_Item $item) use ($service) {
				return $item->onPdf && \Illuminate\Support\Arr::get(Ext_Thebing_Inquiry_Document_Version_Item::SERVICE_MAPPING, $item->type) === $service;
			})
			->map(function (Ext_Thebing_Inquiry_Document_Version_Item $item) use ($type, $getAmount, $deductions) {
				$amount = $getAmount($item) + ($deductions[$item->id] ?? 0);
				if ($type === 'open') {
					$amount -= $item->getPayedAmount($this->getCurrencyId());
				}
				return $amount;
			})
			->sum();

	}

	public function getPrintDate(){
		// Absteigend holen und das letzte druck datum ausgeben
		$aVersions = $this->getAllVersions(false, 'DESC');
		foreach($aVersions as $oVersion){
			$aPrints    = $oVersion->getJoinedObjectChilds('print');
			$oPrint     = reset($aPrints);
			$iDate      = $oPrint->printed;
			if(!empty($iDate)){
				$sDate = strftime('%Y-%m-%dT%H:%M:%S', $iDate);
				return $sDate;
			}
		}
		return null;
	}

	public function getPrinter(){
		// Absteigend holen und den letzten drucker ausgeben
		$aVersions = $this->getAllVersions(false, 'DESC');
		foreach($aVersions as $oVersion){
			$aPrints    = $oVersion->getJoinedObjectChilds('print');
			$oPrint     = reset($aPrints);
			$iPrinter   = $oPrint->user_id;
			if($iPrinter){
				return $iPrinter;
			}
		}
		return null;
	}

	public function isSuccessPrinted(){
		// Absteigend holen und den letzten druck ausgeben
		$aVersions = $this->getAllVersions(false, 'DESC');
		foreach($aVersions as $oVersion){
			$aPrints    = $oVersion->getJoinedObjectChilds('print');
			$oPrint     = reset($aPrints);
			$iDate      = $oPrint->print_success;
			if(!empty($iDate)){
				$sDate = strftime('%Y-%m-%dT%H:%M:%S', $iDate);
				return $sDate;
			}
		}
		return null;
	}


	public function getSchool(){
		$oSchool = null;

		$iSchoolId = $this->getSchoolId();
		
		if($iSchoolId !== null)
		{
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		}
		
		return $oSchool;
	}

	/**
	 * @return \TsAccounting\Entity\Company
	 */
	public function getCompany() {
		
		// Hat die Version eine Firma zugewiesen?
		$version = $this->getLastVersion();
		$company = $version->getJoinedObject('company');
		
		if($company->exist()) {
			return $company;
		}
		
		// Standardfirma von Schule / Inbox
		$oSchool    = $this->getSchool();
		$oInbox     = $this->getInbox(true);

		if($oSchool) {
		    return \TsAccounting\Entity\Company::searchByCombination($oSchool, $oInbox);
        }

		return null;
	}

	public function isProforma()
	{
		$sType = $this->type;

		return strpos($sType, 'proforma') !== false;
	}

	public function isOffer() {
		return strpos($this->type, 'offer') !== false;
	}

	public function isInvoice() {

		return $this->checkDocumentType('invoice_with_creditnotes_and_without_proforma');

//		if(
//			$this->isProforma() ||
//			// #5559 - Angebote nur bei Proforma anzeigen
//			$this->isOffer()
//		) {
//			return false;
//		}
//
//		return true;

	}

	/**
	 * @return bool|null
	 */
	public function isConvertedProforma() {

		if (
			!$this->isProforma() ||
			$this->entity !== Ext_TS_Inquiry::class
		) {
			return null;
		}

		$documentSearch = new Ext_Thebing_Inquiry_Document_Search($this->entity_id);
		$documentSearch->setType('invoice_without_proforma');
		$documentSearch->setDraft(null); // Entwürfe mit berücksichtigen
		if (!empty($documentSearch->searchDocument(false, false))) {
			return true;
		}
		return false;

	}

	/**
	 * Liefert den Namen der GUI und das Set, in welcher das Dokument erstellt wurde
	 *
	 * @return string|null
	 */
	public function getGUINameAndSet() {
		$aGui2 = (array)$this->gui2;
		$aGui2 = reset($aGui2);

		// Hier MUSS wegen null_value null zurückkommen
		$sReturn = null;

		if(!empty($aGui2['name'])) {
			$sReturn = $aGui2['name'];

			if(!empty($aGui2['set'])) {
				$sReturn .= '_'.$aGui2['set'];
			}
		}

		return $sReturn;
	}

	/**
	 * Ist das Dokument bereits für den Schüler-Login freigegeben?
	 *
	 * @return bool
	 */
	public function isReleasedForApp() {
		return (bool)$this->released_student_login;
	}

	/**
	 * Alle Dokumente mit derselben Nummer: Relevant bei Gruppen, da die Dokumente nicht anderweitig verknüpft sind
	 *
	 * @param bool $bIncludeSelf
	 * @return Ext_Thebing_Inquiry_Document[]
	 */
	public function getDocumentsOfSameNumber($bIncludeSelf = true) {

		// Keine Suche bei Dokumenten ohne Nummer / Nummernkreis
		if(
			$this->numberrange_id == 0 ||
			$this->document_number == ''
		) {
			return [];
		}

		/** @var Ext_Thebing_Inquiry_Document[] $aGroupDocuments */
		$aDocuments = Ext_Thebing_Inquiry_Document::getRepository()->findBy(array(
			'document_number' => $this->document_number,
			'numberrange_id' => $this->numberrange_id
		));

		if(!$bIncludeSelf) {
			$aDocuments = array_filter($aDocuments, function($oDocument) {
				return $oDocument->id != $this->id;
			});
		}

		return $aDocuments;
	}

	/**
	 * @return \DateTime
	 */
	public function getDate() {
		
		if($this->id <= 0) {
			return new \DateTime();
		}
		
		return (new \DateTime())->setTimestamp($this->created);
	}
	
	/**
	 * Check, ob Leistungen teilweise abgerechnet wurden. Wichtig, falls die Rechnungen nur für Anzahlung und 
	 * Restzahlung aufgeteilt wurden
	 * 
	 * @todo Klappt so aktuell nicht, muss überdacht werden. Ist nur für das Icon "Teilrechnung" notwendig.
	 * @todo Eigenschaft in der DB speichern
	 * @return bool
	 */
	public function hasInstalments(): bool {
		return $this->partial_invoice;
		if(!$this->partial_invoice) {
			return false;
		}
		
		$latestVersion = $this->getLastVersion();
		$items = $latestVersion->getJoinedObjectChilds('items');
		
		foreach($items as $item) {
			if(!empty($item->additional_info['instalment'])) {
				return true;
			}
		}
		
		return false;		
	}

	/**
	 * @return int
	 */
	public function getSubObject() {
		return $this->getInquiry()->getSchool()->id;
	}

	public function getCorrespondenceLanguage() {
		$oInquiry = $this->getInquiry();
		$oContact = $oInquiry->getTraveller();
		return $oContact->getCorrespondenceLanguage();
	}

	public function getNextPayment(): ?\Ts\Dto\ExpectedPayment {
		$latestVersion = $this->getLastVersion();
		return $latestVersion?->getIndexPaymentTermData('paymentterms_next_payment_object');
	}

	public function getDuePayment(): ?\Ts\Dto\ExpectedPayment {

		$nextPayment = $this->getNextPayment();

		if ($nextPayment instanceof \Ts\Dto\ExpectedPayment && $nextPayment->isDue()) {
			return $nextPayment;
		}

		return null;
	}

	public function getAdditionalFees() {
		return Ext_Thebing_Inquiry_Document_Version_Item::query()
			->where('version_id', $this->getLastVersion()->id)
			->where('type', 'LIKE', 'additional%')
			->where('onPdf', '1')
			->get()
			->toArray();
	}

	/**
	 * @todo Das muss entfernt werden weil es Mist ist. Aber wichtig, um eine noch nicht gespeicherte Buchung mit dem Dokument verknüpfen zu können
	 */
	public function setEntity($entity) {
		$this->entityObject = $entity;
	}

	/**
	 * True, wenn das Dokument editierbar ist
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isMutable(): bool
	{
		if (
			// Erlauben wenn flag nicht gesetzt
			!\Ext_Thebing_Client::immutableInvoicesForced() ||
			// Type mutable sind immer veränderbar
			!$this->isInvoice() ||
			// Erlauben wenn neu oder draft
			$this->originalIsDraft()
		) {
			return true;
		}
		return false;
	}

	/**
	 * True, wenn das Dokument im Orginal ein Entwurf ist.
	 * Zum Benutzen, z.B. bei Editierung.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function originalIsDraft(): bool
	{
		if ($this->exist()) {
			return (bool)$this->getOriginalData('draft');
		} else {
			// Neues Dokument ist draft
			return $this->shouldCreateAsDraft();
		}
	}

	/**
	 * True, wenn das Dokument ein Entwurf ist
	 *
	 * @return bool
	 */
	public function isDraft(): bool
	{
		return (bool)$this->draft;
	}

	/**
	 * True, wenn Rechnung als Entwurf angelegt werden muss
	 *
	 * @return bool
	 */
	public function shouldCreateAsDraft(): bool
	{
		if (
			\Ext_Thebing_School::draftInvoicesActive($this->getSchool()) &&
			$this->isInvoice() &&
			$this->getInquiry() instanceof Ext_TS_Inquiry &&
			!$this->getInquiry()->hasGroup() &&
			!$this->overrideCreationAsDraft
		) {
			return true;
		}
		return false;
	}

	/**
	 * Wandelt einen Entwurf zu einer normalen Rechnung um.
	 *
	 * @return bool
	 */
	public function finalize(): bool
	{
		$result = true;
		try {

			// Diese Funktion wird auch bei Nicht-Entwürfen aufgerufen und sollte
			// dann nur die Registrierung ausführen.
			if ($this->originalIsDraft()) {
				// Zuerst version path leeren, da sonst für eine persistente Rechnung
				// keine neue pdf generiert wird. Siehe Ext_Thebing_Pdf_Basic->createPDF()
				$version = $this->getLastVersion();
				$version->path = null;
				// Datum der Rechnung auf aktuellen Tag setzen
				$version->date = date('Y-m-d');
				$paymentTerms = array_values($version->getPaymentTerms());
				foreach ($paymentTerms as $paymentTerm) {
					if ($paymentTerm->date < date('Y-m-d')) {
						$paymentTerm->date = date('Y-m-d');
					}
				}
				$version->save();
				// Schritt 1: Draft 0 setzen, Nummer generieren und speichern.
				$this->draft = 0;
				if (empty($this->document_number)) { // Nur generieren, wenn nicht schon geschehen.
					$this->generateNumber(); // generateNumber ruft save() auf.
				} else {
					$this->save();
				}

				// Schritt 2: PDF erstellen und Hash an Office weitergeben
				$this->createPdf(false, $this->getLastVersion()->template_language);

				$inquiry = $this->getInquiry();

				if (
					!$inquiry->has_invoice &&
					in_array($this->type, ['brutto', 'netto'])
				) {
					$items = $version->getItemObjects(true);
					Ext_Thebing_Document::reallocatePaymentAmounts($inquiry, $items);
				}

				$inquiry->setInquiryStatus($this->type);

				$version->updateSpecialIndexFields();
				$inquiry->getAmount(false, true);
				$inquiry->getAmount(true, true);

				$this->initUpdateTransactions();
			}

			// Schritt 3: Rechnung an Behörde und Office Übermitteln
			\System::wd()->executeHook('ts_register_invoice', $this);

		} catch (Exception $e) {
			Log::getLogger()->error('Finalisation of document failed.',
				[
					'document_id' => $this->id,
					'document_number' => $this->document_number,
					'message' => $e->getMessage()
				]
			);
		}

		return $result;
	}

	public function getCommunicationAdditionalRelations(): array
	{
		return [
			$this->getEntity()
		];
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsAccounting\Communication\Application\Invoices::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return $this->document_number;
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getInquiry()->getSchool();
	}
}