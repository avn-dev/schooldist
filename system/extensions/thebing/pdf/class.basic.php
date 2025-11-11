<?php

if(!defined('WDPDF_USE_TCPDF')) {
	define('WDPDF_USE_TCPDF', false);
}
if(!defined('PDF_FILE_EXTENSION')) {
	define('PDF_FILE_EXTENSION', '.pdf');
}
if(!defined('PDF_FONT_NAME')) {
	define('PDF_FONT_NAME', 'arial');
}
if(!defined('PDF_FONT_BOLD')) {
	define('PDF_FONT_BOLD', 'B');
}

class Ext_Thebing_Pdf_Basic { 

	/**
	 * @var int ID von Ext_Thebing_Pdf_Template
	 */
	protected $_iTemplate = 0;

	/**
	 * @var Ext_Thebing_Pdf_Template
	 */
	protected $_oTemplate;

	/**
	 * @var Ext_Thebing_Pdf_Template_Type
	 */
	protected $_oTemplateType;

	/**
	 * @var Ext_Thebing_Pdf_Document[]
	 */
	protected $_aDocuments = array();

	protected $_aDocumentsTemplates = array();
	protected $_aFormat = array();

	/**
	 * @var string
	 */
	protected $_sLanguage = '';

	/**
	 * @var int ID von Ext_Thebing_School
	 */
	protected $_iSchool = 0;

	/**
	 * TODO: Das sollte Standard werden, da diese Klasse die Version einfach nicht speichern sollte (ggf. wird auch eine andere Version benutzt)
	 *
	 * @var bool
	 */
	protected $bAllowSave = true;

	protected $_aCustomData = array();

	/**
	 * GUI, die _bedarfsweise_ übergeben wird
	 *
	 * @var Ext_Gui2
	 */
	protected $_oGui;

	/**
	 * @var string
	 */
	public $sDocumentType = 'inquiry_document';

	protected $_bFromDialog;

	/**
	 * @var Ext_Thebing_Placeholder
	 */
	public $oLatestPlaceholder;

	/**
	 * @param int $iTemplate ID von Ext_Thebing_Pdf_Template
	 * @param null|int $iSchool ID von Ext_Thebing_School
	 * @param bool $bFromDialog
	 * @param null|Ext_Gui2 $oGui
	 */
	public function __construct($iTemplate, $iSchool = null, $bFromDialog = false, $oGui = null) {

		if($iSchool <= 0) {
			$oSchool = Ext_Thebing_Client::getFirstSchool();
			$iSchool = $oSchool->id;
		}

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance($iTemplate);
		$oTemplateType = Ext_Thebing_Pdf_Template_Type::getInstance($oTemplate->template_type_id);

		$this->_iTemplate = $iTemplate;
		$this->_oTemplate = $oTemplate;
		$this->_oTemplateType = $oTemplateType;
		$this->_iSchool = $iSchool;
		$this->_bFromDialog = $bFromDialog;
		$this->_oGui = $oGui;

	}

	/**
	 * @param string $sLang
	 */
	public function setLanguage($sLang) {
		$this->_sLanguage = $sLang;
	}

	/**
	 * Erzeugt ein Document für das PDF aufbauend auf einem Inquiry Document Objekt
	 *
	 * @TODO umbenennen in createInquiryDocument()
	 * @param Ext_Thebing_Inquiry_Document $oDocument
	 * @param Ext_Thebing_Inquiry_Document_Version $oVersion
	 * @param array $aTables
	 * @param array $aAdditional
	 */
	public function createDocument($oDocument, $oVersion, $aTables = [], $aAdditional = []) {

		$oPDFDocument = new Ext_Thebing_Pdf_Document();
		$oPDFDocument->setAdditional($aAdditional);
		$oPDFDocument->oDocument = $oDocument;
		$oPDFDocument->oDocumentVersion = $oVersion;
//		$oPDFDocument->inquiry_id = $oDocument->inquiry_id;

		// TODO besser wären Type-Hints an den Parametern dieser Methode, aber keine Ahnung ob das wirklich immer passt
		if($oDocument instanceof Ext_Thebing_Inquiry_Document) {
			$oPDFDocument->oEntity = $oDocument->getEntity();
			if ($oPDFDocument->oEntity instanceof \Ext_TS_Inquiry_Abstract) {
				$oPDFDocument->inquiry_id = $oPDFDocument->oEntity->id; // TODO Keine Ahnung, wofür das hier ist
			}
		}

		// Wenn Rechnungspositionen im Template aktiviert sind
		// es aber keine Tabellen Daten gibt, dann hole diese Daten
		$iInquiryPositionsView = (int)$oVersion->canShowInquiryPositions(); 

		if(
			$iInquiryPositionsView > 0 &&
			$oVersion->bDummy === false &&
			empty($aTables)
		) {
			$aTables = $oVersion->getItemsForPdf($oDocument);
		}

		$oPDFDocument->aTables = $aTables;

		$this->addDocument($oPDFDocument);

	}

	/**
	 * Erzeugt ein "Dummy" Object eines Inquiry Document Objected
	 *
	 * Falls das PDF also nicht als Inquiry Document gespeichert wird aber gleich behandelt werden soll
	 * kann diese mthode genutzt werden
	 *
	 * @TODO umbenennen in createDocument
	 * @param array $aData
	 * @param array $aTable
	 * @param array $aTable2
	 * @param array $aAdditional
	 * @param bool $bVersionDummy
	 */
	public function createDummyDocument($aData, $aTable = array(), $aTable2 = array(), $aAdditional = array(), $bVersionDummy = false) {

		$this->bAllowSave = false;

		$oDocument = new Ext_Thebing_Inquiry_Document(0);
		$oDocument->entity = Ext_TS_Inquiry::class;
		$oDocument->entity_id = $aData['inquiry_id'];
		$oDocument->document_number = $aData['document_number'];

		$oVersion = $oDocument->newVersion();
		$oVersion->txt_intro = $aData['txt_intro'];
		$oVersion->txt_outro = $aData['txt_outro'];
		$oVersion->txt_address = $aData['txt_address'];
		$oVersion->txt_subject = $aData['txt_subject'];
		$oVersion->date = $aData['date'];
		$oVersion->txt_pdf = $aData['txt_pdf'];
		$oVersion->txt_signature = $aData['txt_signature'];
		$oVersion->signature = $aData['signature'];

		if(
			$bVersionDummy ||
			empty($aData['inquiry_id'])
		) {
			$oVersion->bDummy = true;
		}

		$aTables = [$aTable, $aTable2];
		$this->createDocument($oDocument, $oVersion, $aTables, $aAdditional);

	}

	public function addDocument($oDocument){
		$this->_aDocuments[] = $oDocument;
	}

	public function outputPDF($sName = 'Studentcard', $sFileOutput = 'I') {
		return $this->createPDF('', $sName, '', 'P', $sFileOutput);
	}

	public function createPDF($sDir = '', $sName = '', $sTitle = '', $sOrientation = 'P', $sFileOutput = 'F', $bDirect=false) {
		global $user_data;

		$oUser = Ext_Thebing_User::getInstance($user_data['id']);

		$oFirstDoc = reset($this->_aDocuments);
		$oEntity = $oFirstDoc->oEntity;
		$oSchool = null;

		if($this->_sLanguage == '') {
			if ($oEntity instanceof \Ts\Interfaces\Entity\DocumentRelation) {
				$oSchool = $oEntity->getSchool();
				$this->_sLanguage = $oEntity->getDocumentLanguage();
			} else if ($oFirstDoc->inquiry_id > 0) {
				$oFirstInquiry = new Ext_TS_Inquiry($oFirstDoc->inquiry_id);
				$oSchool = $oFirstInquiry->getSchool();
				$this->_sLanguage = $oFirstInquiry->getCustomer()->getLanguage();
			}
		}

		if(!($oSchool instanceof Ext_Thebing_School)) {
			if($this->_iSchool > 0) {
				$oSchool = Ext_Thebing_School::getInstance($this->_iSchool);
			} else {
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
				$this->_iSchool = $oSchool->id;
			}
		}

		if($this->_sLanguage == '') {
			$this->_sLanguage = $oSchool->getLanguage();
		}

		if($sDir == '') {
			$sDir = Util::getDocumentRoot().'storage/temp/';
		}

		Ext_Thebing_Util::checkDir($sDir);

		// Pfad + Name für das Haupt PDF
		$sFilePath = $sDir.$sName.'.pdf';
		
		// Name für das Temp PDF welches alle Doc. enthält
		$sFilePathTemp = $sDir.$sName.'_temp.pdf';

		$aFormat = [$this->_oTemplateType->page_format_width, $this->_oTemplateType->page_format_height];

		/*
		 * PDF erstellen, um Hintergrundgröße zu ermitteln
		 */
		$oPdf = new Ext_Thebing_Pdf_Fpdi($sOrientation, 'mm', 'A4', true, 'UTF-8', false, 2); /** @var Ext_Thebing_Pdf_Fpdi|TCPDF $oPdf */
		$sFile = $this->_oTemplate->getOptionValue($this->_sLanguage, $this->_iSchool, 'first_page_pdf_template');

		if($sFile) {

			$sTplPath = \Ext_Thebing_Upload_File::buildPath($sFile);

			if(is_file($sTplPath)) {

				$oPdf->setSourceFile($sTplPath);
				$tplidx = $oPdf->importPage(1, '/MediaBox');
				$aTemplateSize = $oPdf->getTemplateSize($tplidx);

				if(
					!empty($aTemplateSize['width']) &&
					!empty($aTemplateSize['height'])
				) {
					$aFormat = array(round($aTemplateSize['width'], 2), round($aTemplateSize['height'], 2));
				}

			}

		}

		/*
		 * Prüfen, ob das Dokument in das Zieldokument passt
		 * Falls nicht, Maße auf Zieldokument anpassen
		 */
		if(
			$aFormat[0] > $this->_oTemplateType->page_format_width ||
			$aFormat[1] > $this->_oTemplateType->page_format_height
		) {
			$aFormat[0] = $this->_oTemplateType->page_format_width;
			$aFormat[1] = $this->_oTemplateType->page_format_height;
		}

		$this->_aFormat = $aFormat;

		if($aFormat[0] > $aFormat[1]) {
			$sOrientation = 'L';
		} else {
			$sOrientation = 'P';
		}

		// Richtiges PDF erstellen
		$oPdf = new Ext_Thebing_Pdf_Fpdi($sOrientation, 'mm', $aFormat, true, 'UTF-8', false, 2);
		$oPdf->oGui = $this->_oGui;

		$oPdf->sDocumentType = $this->sDocumentType;

		// set document information
		$oPdf->SetCreator('Fidelo School Software');
		$oPdf->SetAuthor($oSchool->ext_1." - ".$oUser->name);
		$oPdf->SetTitle($sTitle);

		$oPdf->setPrintHeader(true);
		$oPdf->setPrintFooter(true);

		// set default font subsetting mode
		$oPdf->setFontSubsetting($this->_oTemplateType->font_subsetting);

		$fDefaultFontSize = (float)$this->_oTemplateType->font_size;

		$oPdf->aCustomData['oTemplate'] = &$this->_oTemplate;
		$oPdf->aCustomData['oTemplateType'] = &$this->_oTemplateType;
		$oPdf->aCustomData['font_size'] = $fDefaultFontSize;
		$oPdf->aCustomData['aDocumentsTemplates'] = &$this->_aDocumentsTemplates;

		$oPdf->setDefaultFontSettings();

		$bSetInquiryLanguage = false;
		if($this->_sLanguage == '') {
			$bSetInquiryLanguage = true;
		}

		/*
		 * Dokumenten wurden bereits einzeln generiert
		 * Man darf nicht generierte Dokumente mit nicht-generierten Dokumenten mischen
		 */
		$bPreGenerated = false;
		$aGeneratedFiles = array();

		// Alle Kundendatensätze durchlaufen und jeweils eine neue Seite anfangen
		foreach($this->_aDocuments as $oDocument) {

			// Für persistente Rechnungen dürfen pdfs nicht neue generiert werden
			if (
				!$oDocument->oDocument->isMutable() &&
				$oDocument->oDocumentVersion->getPath()
			) {
				$aGeneratedFiles[] = $oDocument->oDocumentVersion->getPath();
				continue;
			}

			// Wenn das Document bereits generiert wurde, dann nicht neu generieren
			if($oDocument->checkGenerated()) {
				$bPreGenerated = true;
				$aGeneratedFiles[] = $oDocument->oDocumentVersion->getPath();
				continue;
			}

			$oEntity = null;

			if($oDocument->oEntity instanceof \Ts\Interfaces\Entity\DocumentRelation) {

				$oEntity = $oDocument->oEntity;

				if($bSetInquiryLanguage) {
					$this->_sLanguage = $oEntity->getDocumentLanguage();
				}

				if($oEntity->exist()) {
					$this->_iSchool = $oEntity->getSchool()->id;
				}

			} elseif($oDocument->inquiry_id > 0) {

				$oEntity = new Ext_TS_Inquiry($oDocument->inquiry_id);

				if($bSetInquiryLanguage) {
					$this->_sLanguage = $oEntity->getCustomer()->getLanguage();
				}

				$this->_iSchool = $oEntity->getSchool()->id;

			} elseif(
				$oDocument->oDocument &&
				$oDocument->oDocument->getEnquiry()
			) {

				$oEntity = $oDocument->oDocument->getEnquiry();

			} else {

				$oEntity = new Ext_TS_Inquiry();

			}

			$oSchool = Ext_Thebing_School::getInstance($this->_iSchool);

			$oPdf->aCustomData['document_data'] = $oDocument->getAdditional();
			$oPdf->aCustomData['document_type'] = $oDocument->oDocument->type;
			$oPdf->aCustomData['document'] = $oDocument->oDocument;
			$oPdf->aCustomData['language'] = $this->_sLanguage;
			$oPdf->aCustomData['school'] = $this->_iSchool;
			if ($oEntity instanceof Ext_TS_Inquiry_Abstract) {
				$oPdf->aCustomData['iInquiry'] = $oEntity->id;
				$oPdf->aCustomData['iCustomer'] = $oEntity->getCustomer()->id;
				$oGroup	= $oEntity->getGroup();
				$oPdf->aCustomData['iGroup'] = $oGroup->id;
			}
			$oPdf->aCustomData['entity'] = $oEntity::class;
			$oPdf->aCustomData['entity_id'] = $oEntity->getId();
			$oPdf->aCustomData['oInquiry'] = &$oDocument;

			if(
				is_object($oDocument->oDocumentVersion) &&
				$oDocument->oDocumentVersion->id > 0
			) {

				$oPdf->aCustomData['template_url'] = $oDocument->oDocumentVersion->txt_pdf;
				$oPdf->aCustomData['signatur_txt'] = $oDocument->oDocumentVersion->txt_signature;
				$oPdf->aCustomData['signatur_img'] = $oDocument->oDocumentVersion->signature;

			} else {

				$oPdf->aCustomData['template_url'] = $this->_oTemplate->getOptionValue($this->_sLanguage, $this->_iSchool, 'first_page_pdf_template');

				// wenn kein Inquiry Doc. Object genutzt wird wird geschaut ob das Document daten für dieses Element hat
				$sDocSigText = $oDocument->element_signature_text;
				$sDocSigImg = $oDocument->element_signature_img;

				if(
					!empty($sDocSigText) ||
					!empty($sDocSigImg)
				) {

					$oPdf->aCustomData['signatur_txt'] = $sDocSigText;
					$oPdf->aCustomData['signatur_img'] = $sDocSigImg;

				} elseif($this->_oTemplate->user_signature != 1) {

					// Ansonsten wird die im Template angegebenen Daten genommen
					$oPdf->aCustomData['signatur_txt'] = $this->_oTemplate->getOptionValue($this->_sLanguage, $this->_iSchool, 'signatur_text');
					$oPdf->aCustomData['signatur_img'] = $this->_oTemplate->getOptionValue($this->_sLanguage, $this->_iSchool, 'signatur_img');

				} else {

					// wenn die Signatur pro User ist dann wird die User Einstellung geholt					
					$oPdf->aCustomData['signatur_txt'] = Ext_Thebing_User_Data::getData($user_data['id'], 'signature_pdf_'.$this->_sLanguage);
					$oPdf->aCustomData['signatur_img'] = Ext_Thebing_User_Data::getData($user_data['id'], 'signature_img_'.$this->_iSchool);

				}

			}

			// $oPdf->aCustomData['template_url'] kann leer aus $oDocument->oDocumentVersion->txt_pdf kommen
			if($oPdf->aCustomData['template_url'] == '') {
				$oPdf->aCustomData['template_url'] = $this->_oTemplate->getOptionValue($this->_sLanguage, $this->_iSchool, 'first_page_pdf_template');
			}

			// Zurücksetzen sobald sich Werte ändern
			$oPdf->oPlaceholder = null;
			
			// Muss nochmal gemacht werden, sonst geht der Margin bei der Positionstabelle ggf. verloren (niemand blickt hier durch)
			$oPdf->SetMargins((float)$this->_oTemplateType->first_page_border_left, (float)$this->_oTemplateType->first_page_border_top, (float)$this->_oTemplateType->first_page_border_right);
			$oPdf->SetAutoPageBreak(true, (float)$this->_oTemplateType->first_page_border_bottom);

			// Legt eine neue ERSTE Seite an
			$oPdf->startPageGroup();
			$oPdf->iDocPageNr = 1;
			$oPdf->AddPage($sOrientation, $this->_aFormat, true);

			if(\System::wd()->hasHook('ts_pdf_creation')) {
				\System::wd()->executeHook('ts_pdf_creation', $oPdf, $oDocument->oDocumentVersion);
			}

			$aStaticElements = array();

			if($this->_oTemplateType->element_date) { // nur wenn im Template das Element aktiv ist

				$aTemp = array();
				$aTemp['x'] = $this->_oTemplateType->element_date_x;
				$aTemp['y'] = $this->_oTemplateType->element_date_y;

				$aTemp['font'] = new stdClass();
				$aTemp['font']->font_type = $this->_oTemplateType->element_date_font_type;
				$aTemp['font']->font_style = $this->_oTemplateType->element_date_font_style;
				$aTemp['font']->font_size = $this->_oTemplateType->element_date_font_size;
				$aTemp['font']->font_spacing = $this->_oTemplateType->element_date_font_spacing;

				$aTemp['name'] = 'date';

				if(is_object($oDocument->oDocumentVersion)) {
					$aTemp['text'] = $oDocument->oDocumentVersion->date;
					$oFormat = new Ext_Thebing_Gui2_Format_Date();
					$aTempData = array('school_id' => $this->_iSchool);
					$oColumn = null;
					$aTemp['text'] = $oFormat->format($aTemp['text'], $oColumn, $aTempData);
				} else {
					// wenn kein Inquiry Doc. Object genutzt wird wird geschaut ob das Document daten für dieses Element hat
					$aTemp['text'] = $oDocument->element_date;
				}

				$aStaticElements[] = $aTemp;

			}

			if($this->_oTemplateType->element_address) { // nur wenn im Template das Element aktiv ist

				$aTemp = array();
				$aTemp['x'] = $this->_oTemplateType->element_address_x;
				$aTemp['y'] = $this->_oTemplateType->element_address_y;

				$aTemp['font'] = new stdClass();
				$aTemp['font']->font_type = $this->_oTemplateType->element_address_font_type;
				$aTemp['font']->font_style = $this->_oTemplateType->element_address_font_style;
				$aTemp['font']->font_size = $this->_oTemplateType->element_address_font_size;
				$aTemp['font']->font_spacing = $this->_oTemplateType->element_address_font_spacing;

				$aTemp['name'] = 'address';
				if(is_object($oDocument->oDocumentVersion)) {
					$aTemp['text'] = $oDocument->oDocumentVersion->txt_address;
				} else {
					// wenn kein Inquiry Doc. Object genutzt wird wird geschaut ob das Document daten für dieses Element hat
					$aTemp['text'] = $oDocument->element_address;
				}

				$aStaticElements[] = $aTemp;

			}

			if($this->_oTemplateType->element_subject) { // nur wenn im Template das Element aktiv ist

				$aTemp = array();
				$aTemp['x'] = $this->_oTemplateType->element_subject_x;
				$aTemp['y'] = $this->_oTemplateType->element_subject_y;

				$aTemp['font'] = new stdClass();
				$aTemp['font']->font_type = $this->_oTemplateType->element_subject_font_type;
				$aTemp['font']->font_style = $this->_oTemplateType->element_subject_font_style;
				$aTemp['font']->font_size = $this->_oTemplateType->element_subject_font_size;
				$aTemp['font']->font_spacing = $this->_oTemplateType->element_subject_font_spacing;

				$aTemp['name'] = 'subject';
				if(is_object($oDocument->oDocumentVersion)) {
					$aTemp['text'] = $oDocument->oDocumentVersion->txt_subject;
				} else {
					// wenn kein Inquiry Doc. Object genutzt wird wird geschaut ob das Document daten für dieses Element hat
					$aTemp['text'] = $oDocument->element_subject;
				}

				$aStaticElements[] = $aTemp;

			}

			if($this->_oTemplateType->element_text1) { // nur wenn im Template das Element aktiv ist

				$aTemp = array();
				$aTemp['x'] = $this->_oTemplateType->element_text1_x;
				$aTemp['y'] = $this->_oTemplateType->element_text1_y;

				$aTemp['font'] = new stdClass();
				$aTemp['font']->font_size = $fDefaultFontSize;

				$aTemp['name'] = 'text1';
				if(is_object($oDocument->oDocumentVersion)){
					$aTemp['text'] = $oDocument->oDocumentVersion->txt_intro;
				} else {
					// wenn kein Inquiry Doc. Object genutzt wird wird geschaut ob das Document daten für dieses Element hat
					$aTemp['text'] = $oDocument->element_text1;
				}

				$aStaticElements[] = $aTemp;

			}

			if($this->_oTemplateType->element_inquirypositions) { // nur wenn im Template das Element aktiv ist

				$aTemp = array();
				$aTemp['x'] = $this->_oTemplateType->element_text1_x;
				$aTemp['y'] = 'auto';

				$aTemp['font'] = new stdClass();
				$aTemp['font']->font_size = $fDefaultFontSize;

				$aTemp['name'] = 'inquirypositions';
				$aStaticElements[] = $aTemp;

			}

			if($this->_oTemplateType->element_text2) { // nur wenn im Template das Element aktiv ist

				$aTemp = array();
				$aTemp['x'] = $this->_oTemplateType->element_text1_x;
				$aTemp['y'] = 'auto';

				$aTemp['font'] = new stdClass();
				$aTemp['font']->font_size = $fDefaultFontSize;

				$aTemp['name'] = 'text2';
				if(is_object($oDocument->oDocumentVersion)){
					$aTemp['text'] = $oDocument->oDocumentVersion->txt_outro;
				} else {
					// wenn kein Inquiry Doc. Object genutzt wird wird geschaut ob das Document daten für dieses Element hat
					$aTemp['text'] = $oDocument->element_text2;
				}

				$aStaticElements[] = $aTemp;

			}

			foreach($aStaticElements as $aElement) {

				if($this->iDocPageNr > 1) {
					continue;
				}

				if($aElement['x'] === 'auto') {
					$aElement['x'] = $oPdf->getX();
				}

				if($aElement['y'] === 'auto') {
					$aElement['y'] = $oPdf->getY();
				}

				$oPdf->setFontSettings($aElement['font']);

				if($aElement['name'] != 'inquirypositions') {

					// wenn das Dokument vom Dokumentendialog aus gespeichert wird, muss immer der gesetzte Wert übernommen werden
					if(
						empty($aElement['text']) && 
						!$this->_bFromDialog
					) {
						$mValue = $this->_oTemplate->getStaticElementValue($this->_sLanguage, $aElement['name']);
					} else {
						$mValue = $aElement['text'];
					}

					$mValue = $oPdf->cleanAndReplaceText($mValue);

					$iWidth = $oPdf->getPageWidth() - $aElement['x'] - $this->_oTemplateType->first_page_border_right;
					
					$oPdf->MultiCell(
						$iWidth,
						10,
						$mValue,
						0,
						'L',
						0,
						1,
						$aElement['x'],
						$aElement['y'],
						true,
						2,
						true,
						true,
						0
					);

				} else {

					if(\System::wd()->hasHook('ts_pdf_print_line_items')) {
					
						\System::wd()->executeHook('ts_pdf_print_line_items', $oPdf, $oDocument, $aElement, $this->_sLanguage);
					
					} else {
					
						foreach($oDocument->aTables as $aTable) {

							if(empty($aTable['body'])) {
								continue;
							}

							$aHeader = $aTable['header'];
							$aData = $aTable['body'];
							$oPdf->setX($aElement['x']);

							// Wenn Überschrift: Als Zeile über Header mit Abstand (bei mehreren Tabellen) schreiben
							if(isset($aTable['caption'])) {
								$oPdf->drawPositionTr([['text' => '<br><br>'.$aTable['caption']]]);
							}

							if(
								$aTable['type'] === 'invoice_document' ||
								$aTable['type'] === 'invoice_document_without_header'
							) {
								$bHeader = true;
								if($aTable['type'] === 'invoice_document_without_header') {
									$bHeader = false;
								}
								$oPdf->writeInvoicePositions($aData, $this->_sLanguage, $bHeader);
							} else {
								$oPdf->writePositions($aHeader, $aData);
							}

						}
						
					}
					
				}

				$oPdf->setDefaultFontSettings();

			}

			if(
				is_object($oDocument->oDocumentVersion) &&
				$this->bAllowSave
			) {

				$oDocument->oDocumentVersion->path = $oDocument->oDocumentVersion->prepareAbsolutePath($sFilePath);
				$oDocument->oDocumentVersion->persist();

			}

			// Signaturen Schreiben!
			$imgX = $aElement['x'];
			$imgY = $oPdf->getY();
			$iSignatureY = $imgY;

			$iSigImg = $oPdf->aCustomData['signatur_img'];

			if(
				$this->_oTemplateType->element_signature_img && // nur wenn im Template das Element aktiv ist
				!empty($iSigImg)
			) {

				if(!is_numeric($iSigImg)) {
				    // wir brauchen nur den Dateinamen, da getUploadDir() schon alle anderen Pfade enthält
					$sSigImg = basename($iSigImg);
				} else {
					$oFile = Ext_Thebing_Upload_File::getInstance((int)$iSigImg);
					$sSigImg = $oFile->filename;
				}

				$sSigImg = \Ext_Thebing_Upload_File::buildPath($sSigImg);

				if(is_file($sSigImg)) {

					$aSignatureSize = getimagesize($sSigImg);

					if(($aSignatureSize[0] / $aSignatureSize[1]) > 1.25) { // Wenn Signaturbild Querformat
						$mWidth = null;
						$mHeight = 15;
					} else { // Wenn Hochformat
						$mWidth = 60;
						$mHeight = null;
					}

					$oPdf->Image($sSigImg, $imgX, $imgY, $mWidth, $mHeight);
					$iSignatureY += 15;

				}

			}

			$sSigText = $oPdf->aCustomData['signatur_txt'];

			if(
				$this->_oTemplateType->element_signature_text && // nur wenn im Template das Element aktiv ist
				!empty($sSigText)
			) {

				$oPdf->SetFontSize($fDefaultFontSize);
				$iWidth = $oPdf->getPageWidth() - $aElement['x'] - $this->_oTemplateType->first_page_border_right;
				$mValue = $sSigText;
				$mValue = $oPdf->cleanAndReplaceText($mValue);

				//$oPdf->writeHtml($mValue, true, 0, true, true); // geht nicht da nach einem zeilenumbruch die X Pos nichtmehr stimmt
				//$oPdf->writeHTMLCell($oPdf->getPageWidth(), 10, $aElement['x'], $aElement['y'], $mValue); // geht nicht da man durch GetY die Position nicht finden kann ( zeiger oben rechts )
				$oPdf->MultiCell(
					$iWidth,
					10,
					$mValue,
					0,
					'J',
					0,
					1,
					$imgX,
					$iSignatureY, // +15, da $oPdf->Image() die Y-Koordinate nicht verändert
					true,
					2,
					true,
					true,
					0
				);

			}

			// Vor dem ->Output(), weil danach Werte unset() werden (fontkeys z.B.).
			if (
				$this->_oTemplateType->element_swiss_qr_bill_code &&
				TcExternalApps\Service\AppService::hasApp(Ts\Handler\SwissQrBill\ExternalApp::APP_NAME)
			) {
				$swissQrBill = new \Ts\Service\SwissQrBill($oPdf, $oEntity, $oDocument->oDocumentVersion);
				$swissQrBill->handle();
			}

			$oPdf->endPage();
			
		}

		// Falls Entwurf, Wasserzeichen auf allen Seiten einsetzen
		if ($oDocument->oDocument->isDraft()) {
			$watermarkText = \L10N::t('ENTWURF');
			$fontSize = 50;
			$textColor = [255, 0, 0];
			$pageCount = $oPdf->PageNo();
			for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
				$oPdf->setPage($pageNo);
				$oPdf->SetFont('Helvetica', 'B', $fontSize);
				$oPdf->SetTextColor(...$textColor);
				$oPdf->SetAlpha(0.2);
				$pageWidth = $oPdf->GetPageWidth();
				$pageHeight = $oPdf->GetPageHeight();
				$oPdf->Rotate(45, $pageWidth / 2, $pageHeight / 2);
				$oPdf->Text($pageWidth / 4, $pageHeight / 2, $watermarkText);
				$oPdf->Rotate(0);
			}
		}

		$this->oLatestPlaceholder = $oPdf->oPlaceholder;
		
		// Wenn die Dokumente noch nicht generiert wurden
		if($bPreGenerated === false) {
			$oPdf->Output($sFilePathTemp, 'F');
			$aGeneratedFiles[] = $sFilePathTemp;
		} else {
			foreach($aGeneratedFiles as &$sFile) {
				$sFile = Util::getDocumentRoot().'storage/'.$sFile;
			}
		}

		$aFormat = array(
			$this->_oTemplateType->page_format_width,
			$this->_oTemplateType->page_format_height
		);

		if($this->_oTemplateType->page_format_width > $this->_oTemplateType->page_format_height) {
			$sOrientation = 'L';
		} else {
			$sOrientation = 'P';
		}

		$iDocHeight = $this->_aFormat[1];
		$iDocWidth = $this->_aFormat[0];

		$iPageHeight = $this->_oTemplateType->page_format_height;
		$iPageWidth = $this->_oTemplateType->page_format_width;

		$iXMaxCount = floor($iPageWidth / $iDocWidth);
		$iYMaxCount = floor($iPageHeight / $iDocHeight);

		$x = $this->_oTemplateType->page_margin_left;
		$y = $this->_oTemplateType->page_margin_right;
		$sBackground = $this->_oTemplateType->page_background;

		$iXCount = 1;
		$iYCount = 1;

		$bAddPage = true;

		if(
			count($aGeneratedFiles) == 1 &&
			(
				$bDirect ||
				(
					empty($sBackground) &&
					$x == "0.00" &&
					$y == "0.00"						
				)
			)
		) {

			$oPdf2 = $oPdf;

		} else {

			// Einzelne Dokumente auf diesem PDF platzieren
			$oPdf2 = new Ext_Thebing_Pdf_Fpdi($sOrientation, 'mm', $aFormat, true, 'UTF-8', false, 2);

			// set document information
			$oPdf2->SetCreator($oSchool->ext_1);
			$oPdf2->SetProducer($oSchool->ext_1);
			$oPdf2->SetAuthor($oUser->name);
			$oPdf2->SetSubject($sTitle);
			$oPdf2->SetTitle($sTitle);
			$oPdf2->SetKeywords($sTitle);

			$oPdf2->setPrintHeader(false);
			$oPdf2->setPrintFooter(false);

			foreach($aGeneratedFiles as $sGeneratedFile) {

				$iPagecount = $oPdf2->setSourceFile($sGeneratedFile);

				// Seiten durchgehen und auf dem Hauptdoc. positionieren
				for($i = 1; $i <= $iPagecount; $i++) {

					// Seiten positionieren
					if($bAddPage) {

						$oPdf2->addPage();
						$x = $this->_oTemplateType->page_margin_left;
						$y = $this->_oTemplateType->page_margin_right;
						$iXCount = 1;
						$iYCount = 1;

						// Wenn das PDF auch einen Hintergrund hat
						if($this->_oTemplateType->page_background > 0) {
							$oFile = Ext_Thebing_Upload_File::getInstance($this->_oTemplateType->page_background);
							$oPdf2->setSourceFile($oFile->getPath());
							$iPageTplIndex = $oPdf2->importPage(1, '/MediaBox');
							$oPdf2->useTemplate($iPageTplIndex, 0, 0);
						}

						$bAddPage = false;

					}

					// Seiten positionieren
					$oPdf2->setSourceFile($sGeneratedFile);
					$tplidx = $oPdf2->importPage($i, '/MediaBox');
					$oPdf2->useTemplate($tplidx, $x, $y);

					if($iXCount < $iXMaxCount) {
						$iXCount++;
						$x += ($iDocWidth + $this->_oTemplateType->page_spacing_x);
					} elseif($iYCount < $iYMaxCount) {
						$iXCount = 1;
						$x = $this->_oTemplateType->page_margin_left;
						$iYCount++;
						$y += ($iDocHeight + $this->_oTemplateType->page_spacing_y);
					} else {
						$bAddPage = true;
					}

				}

			}

		}

		// Anhänge im PDF Anghängen
		$aAttachments = (array)$this->_oTemplate->getOptionValue($this->_sLanguage, $this->_iSchool, 'attachments');

		// Anhänge
		foreach($aAttachments as $iFileId) {
			$oFile = Ext_Thebing_Upload_File::getInstance($iFileId);
			$aFile = array('Subtype'=>'FileAttachment', 'Name' => 'PushPin', 'FS' => $oFile->getPath());
			$oPdf2->Annotation( -100, -100, 1, 1, Ext_TC_Placeholder_Abstract::translateFrontend('Anhänge', $this->_sLanguage), $aFile);
		}

		// Haupt PDF Speichern
		$oPdf2->Output($sFilePath, $sFileOutput);
		// Wenn erstes Dokument persistent ist, dann muss die gesamte Datei readonly sein, da für alle Dokumente das
		// Gleiche gelten sollte.
		if (
			$oFirstDoc &&
			!$oFirstDoc->oDocument->isMutable()
		) {
			Util::changeFileModeReadonly($sFilePath);
		} else {
			Util::changeFileMode($sFilePath);
		}
		// temp datei löschen
		if(is_file($sFilePathTemp)) {
			unlink($sFilePathTemp);
		}

		return $sFilePath;
	}

	public function setAllowSave($bAllowSave) {
		$this->bAllowSave = $bAllowSave;
	}

}