<?php

class Ext_Thebing_Pdf_Fpdi extends Ext_TC_Pdf_Fpdi {

	public $iDocPageNr = 0; // @TODO TCPDF hat auch eine Variable $this->page, die genau daselbe machen sollte
	public $aCustomData = array();
	public $sDocumentType = 'inquiry_document';
	public $oGui;

	/**
	 * @var Ext_Thebing_Placeholder
	 */
	public $oPlaceholder;
	
	/**
	 * Margins hier setzen, da dies vor Header() passiert und gerade bei einem automatischen
	 * Seitenumbruch von TCPDF im Header() die Margins viel zu spät gesetzt würden,
	 * da zuvor die Page-Dimensionen ausgerechnet werden, welche vor allem für Y wichtig sind.
	 *
	 * @see TCPDF::startPage()
	 * @see TCPDF::_beginpage()
	 * @see TCPDF::setPageOrientation()
	 * @param string $orientation
	 * @param string $format
	 * @param bool $tocpage
	 */
	public function startPage($orientation='', $format='', $tocpage=false) {

		$aMargins = $this->getCurrentPageMargins();

		if(
			!empty($aMargins) &&
			// colxshift['x'] ist immer dann gesetzt wenn z.B. eine Tabelle mit mehreren Spalten über mehr als eine Seite geht
			// Dann springt TCPDF auch für jede Spalte in in die startPage(), aber die veränderten Margins dürfen nicht verändert werden
			empty($this->colxshift['x'])
		) {
			$this->SetMargins($aMargins['left'], $aMargins['top'], $aMargins['right']);
		}

		parent::startPage($orientation, $format, $tocpage);

	}

	public function Header() {

		/*
		 * Für schönere Listen (UL) eingebaut.
		 * @todo Im Layout einstellbar machen
		 */
		$this->setListIndentWidth(2.4384);

		$iSchool = $this->aCustomData['school'];
		$oTemplate = &$this->aCustomData['oTemplate'];
		$oTemplateType = $this->aCustomData['oTemplateType'];
		$sLanguage = $this->aCustomData['language'];

		if($this->iDocPageNr == 1) {
            // wir brauchen nur den Dateinamen, da getUploadDir() schon alle anderen Pfade enthält
			$sBackgroundPdf = basename($this->aCustomData['template_url']);
		} else {
			$sBackgroundPdf = $oTemplate->getOptionValue($sLanguage, $iSchool, 'additional_page_pdf_template');
		}

        $sBackgroundPdf = \Ext_Thebing_Upload_File::buildPath($sBackgroundPdf);

		// Hintergründe der einzelnen Seiten Positionieren
		// FALLS vorhanden
		if(
			!empty($sBackgroundPdf) &&
			is_file($sBackgroundPdf)
		) {

			$iPage = 1;
			$iPageCount = $this->setSourceFile($sBackgroundPdf);

			// Wenn Folgeseite
			if($this->iDocPageNr > 1) {
				$iCurrentFollowingPage = $this->iDocPageNr - 1;
				// Wenn die Seite im PDF enthalten ist, dann verwenden, sonst letzte Seite des PDF verwenden
				if($iPageCount >= $iCurrentFollowingPage) {
					$iPage = $iCurrentFollowingPage;
				} else {
					$iPage = $iPageCount;
				}
			}

			$tplidx_2 = $this->importPage($iPage, '/MediaBox');
			$this->useTemplate($tplidx_2, 0, 0);

		}

		$aMargin = $this->getCurrentPageMargins();
		$this->SetAutoPageBreak(TRUE, $aMargin['bottom']);

	}

	public function Footer() {

		$oTemplate 				= &$this->aCustomData['oTemplate'];
		/** @var Ext_Thebing_Pdf_Template_Type $oTemplateType */
		$oTemplateType 			= &$this->aCustomData['oTemplateType'];
		$sLanguage 				= $this->aCustomData['language'];

		// Alle Dynamischen Elemente des Templates holen
		if($oTemplateType) {
			$aElements = $oTemplateType->getElements();
		} else {
			$aElements = [];
		}

		foreach($aElements as $oElement) {

			// skip this entry if it must not be displayed on
			// the current page
			if(
				(
					$oElement->page == 'first' && $this->iDocPageNr > 1
				) || (
					$oElement->page == 'additional' && $this->iDocPageNr < 2
				) || (
					$oElement->page == 'individual' && 
					$oElement->checkDisplayOnPage($this->iDocPageNr) !== true
				)
			) {
				continue;
			}

			//$iTempX = ($oElement->x - (float)$this->aCustomData['oTemplateType']->first_page_border_left);
			//$iTempY = ($oElement->y - (float)$this->aCustomData['oTemplateType']->first_page_border_top);

			//Setzen der Elementkoordinaten
			$iTempX = ($oElement->x);
			$iTempY = ($oElement->y);

			// Rand abziehen da Set X/Y + writeHTML den Rand mit berücksichtigt
			$this->SetXY(
				$iTempX,
				$iTempY
			);

			$this->SetFontSize($oElement->font_size);

			// Falls das Feld "editable" ist muss dieses Feld aus der DB geholt werden
			$bLoadDefaultValue = true;
			if(
				$this->aCustomData['oInquiry'] &&
				$this->aCustomData['oInquiry']->oDocument
			) {

				$oLastVersion = $this->aCustomData['oInquiry']->oDocument->getLastVersion();

				if($oLastVersion) {
					$iLastVersionId = (int)$oLastVersion->id;
					if(
						$oElement->editable == 1 &&
						$iLastVersionId > 0
					) {
						$oEditableFieldElement = Ext_Thebing_Inquiry_Document_Version_Field::getFieldObject($iLastVersionId, $oElement->id);
						if($oEditableFieldElement) {
							$mValue = $oEditableFieldElement->content;
							$bLoadDefaultValue = false;
						}
					}
				}
			}

			//... ansonsten Default Value holen
			if($bLoadDefaultValue) {
				$mValue = $oElement->getValue($sLanguage, $oTemplate->id);
			}

			$this->setFontSettings($oElement);

			$mValue = $this->cleanAndReplaceText($mValue);

			if($oElement->element_type == 'img') {

				$sImgSrc = '';

				if($mValue == 'default_customer_picture') {
					$oCustomer = Ext_TS_Inquiry_Contact_Traveller::getInstance($this->aCustomData['iCustomer']);
					$sPhoto = $oCustomer->getPhoto();
					$sImgSrc = \Util::getDocumentRoot().$sPhoto;
				}

				if(
					!empty($sImgSrc) &&
					is_file($sImgSrc) &&
					getimagesize($sImgSrc) !== false
				) {
					$iWidth = (float)$oElement->img_width;
					$iHeight = (float)$oElement->img_height;
					$this->Image(
						$sImgSrc,
						$oElement->x,
						$oElement->y,
						$iWidth,
						$iHeight
					);
				}

			} else {

				$this->MultiCell(
					(float)$oElement->element_width,
					10,
					$mValue,
					0,
					'L',
					0,
					1,
					$iTempX,
					$iTempY,
					true,
					2,
					true,
					true,
					0
				);

			}

			$this->setDefaultFontSettings();

		}

		$this->iDocPageNr++;

	}

	/**
	 * Gibt je nach Typ des Dokumentes eine Placeholder Klasse zurück
	 *
	 * @TODO Hier wurde für Smarty noch nichts gemacht...
	 *
	 * @return Ext_Thebing_Placeholder|Ext_Thebing_Inquiry_Placeholder|bool
	 */
	protected function _getPlaceholderObject() {

		if($this->oPlaceholder !== null) {
			return $this->oPlaceholder;
		}
		
		$sTemplateLanguage = '';
		$mDocumentAddresses = null;

		/**
		 * $this->aCustomData['oInquiry']
		 * $this->aCustomData['language']
		 * $this->_sLanguage
		 * $this->sDocumentType
		 * $this->aCustomData['document_data']
		 * $this->aCustomData['iInquiry']
		 * $this->aCustomData['iCustomer']
		 * $this->oGui
		 */
		
		if(
			$this->aCustomData['oInquiry'] &&
			$this->aCustomData['oInquiry']->oDocumentVersion 
		) {
			$sTemplateLanguage = $this->aCustomData['oInquiry']->oDocumentVersion->template_language;
//			$sObjectType = $this->aCustomData['oInquiry']->oDocumentVersion->getTemplate()->getObjectClassFromType();
//			$mDocumentAddresses = $this->aCustomData['oInquiry']->oDocumentVersion->addresses;
		}

		// Wenn aus der Inquiry keine Sprache ermittelt werden konnte noch ein paar Alternativen ausprobieren (#9732)
		if(empty($sTemplateLanguage)) {
			if(!empty($this->aCustomData['language'])) {
				$sTemplateLanguage = $this->aCustomData['language'];
			} elseif(!empty($this->_sLanguage)) {
				$sTemplateLanguage = $this->_sLanguage;
			}
		}

		// TODO Das muss umgestellt werden; das Platzhalter-Objekt muss wegen AdditionalData usw. injected werden!
		switch($this->sDocumentType) {
			case 'cheque':
				$oPlaceholder = new Ext_Thebing_Accounting_Cheque_Placeholder($this->aCustomData['document_data']['cheque_id']);
				break;
			case 'examination':
				$oPlaceholder = new Ext_Thebing_Examination_Placeholder($this->aCustomData['iInquiry'], $this->aCustomData['iCustomer'], $this->aCustomData['document_data']['version_id']);
				break;
			case 'contract':
				$oPlaceholder = new Ext_Thebing_Contract_Placeholder($this->aCustomData['document_data']['version_id']);
				break;
			case 'inquiry_document':
//				if($sObjectType == 'Ext_TS_Inquiry') {
					$oPlaceholder = new Ext_Thebing_Inquiry_Placeholder($this->aCustomData['iInquiry'], $this->aCustomData['iCustomer']);
					// Wenn Platzhalter für ein bestimmtes Objekt ersetzt werden sollen, muss das Objekt auch gesetzt werden!
					if(
						$this->aCustomData['oInquiry']->oDocument && 
						$this->aCustomData['oInquiry']->oDocumentVersion
					) {
						$oPlaceholder->_oDocument = $this->aCustomData['oInquiry']->oDocument;
						$oPlaceholder->_oVersion = $this->aCustomData['oInquiry']->oDocumentVersion;
					}
				// TODO 16002 Wofür war das mit der Adresse gut? Funktioniert das noch?
//				} elseif($this->aCustomData['oInquiry']->oDocument) {
//					$oEnquiry = $this->aCustomData['oInquiry']->oDocument->getEnquiry();
//					if($sObjectType == 'Ext_TS_Enquiry_Offer') {
//						$oPlaceholder = new Ext_TS_Enquiry_Offer_Placeholder($this->aCustomData['oInquiry']->oDocument);
//					} else {
//						$oPlaceholder = new Ext_TS_Enquiry_Placeholder($oEnquiry);
//					}
//					$mAdditionalData = $oPlaceholder->getAdditionalData('document_address');
//					if(
//						(
//							!is_array($mAdditionalData) ||
//							empty($mAdditionalData)
//						) && (
//							is_array($mDocumentAddresses)
//						)
//					) {
//						$oPlaceholder->setAdditionalData('document_address', $mDocumentAddresses);
//					}
//				}
				break;
			case 'manual_creditnote':
				$oPlaceholder = new Ext_Thebing_Agency_Manual_Creditnote_Placeholder($this->aCustomData['document_data']['creditnote_id']);
				break;
			case 'payment_provider_teacher':
			case 'payment_provider_accommodation':
			case 'payment_provider_transfer':
			case 'course':
			case 'agency_overview':
				$oPlaceholder = $this->aCustomData['document_data']['placeholder'];
				break;
			default:
				// benötigt für PDF Preview
				$oPlaceholder = false;
				break;
		}

		if(!empty($oPlaceholder)) {
			$oPlaceholder->sTemplateLanguage = $sTemplateLanguage;
			$oPlaceholder->oGui = $this->oGui;
		}

		$this->oPlaceholder = $oPlaceholder;

		return $oPlaceholder;
	}

	/**
	 * @TODO Wenn hier Smarty eingebaut wird, müssen auch die Fehler abgefangen werden!
	 *
	 * @param string $mText
	 * @return string
	 */
	public function cleanAndReplaceText($mText){

		$bReplacePdf = false;
		$oReplace = $this->_getPlaceholderObject();
		$bHtml = preg_match('/<.*>/', $mText);

		if(!$bHtml) {
			$mText = nl2br($mText);
		}

		if($oReplace && method_exists($oReplace, 'replace')){
			$mText = $oReplace->replace($mText);
			$bReplacePdf = method_exists($oReplace, 'replaceForPdf');
		}

		// PDF Platzhalter, nur aufrufen, wenn Methode vorhanden
		if($bReplacePdf) {
			$mText = $oReplace->replaceForPdf($this->aCustomData['oInquiry']->oDocument, $this->aCustomData['oInquiry']->oDocumentVersion, $mText);
		}

		if($this->aCustomData['oTemplateType']->html_as_textarea != 1) {
			$mText = Ext_TC_Purifier::p($mText);
		}

		// Platzhalter für Seite X von Y ersetzen
		$mText = str_replace('{current_page}', $this->getPageNumGroupAlias(), $mText);
		$mText = str_replace('{total_pages}', $this->getPageGroupAlias(), $mText);

		return $mText;

	}

	/*
	 * Die Funktion schreibt die Rechnugnspositionen als Tabellen auf die Rechnungs PDFs
	 */
	public function writeInvoicePositions($aData, $sLanguage = 'en', $bDisplayHeader = true) {

		$oSchool				= Ext_Thebing_School::getInstance($this->aCustomData['school']);
		$iTax					= (int)$oSchool->tax;
		$aExclusive = $oSchool->getTaxExclusive();
		$iPositionsView			= $this->aCustomData['oTemplate']->inquirypositions_view;
		$sPositionColumnsType	= $this->aCustomData['document']->getPositionColumnsType($iPositionsView);

		$this->drawLine();

		$sType = '';

		$aTemp = reset($aData);
//		$aTemp	= $aData[0];
//		if(!is_array($aTemp)){
//			$aTemp = $aData[1];
//		}

		// Wenn Schulen keine Steuern hat, fällt diese Spalte weg muss aber wieder  hinzugefügt werden bei der Typermittlung
		if(is_array($aTemp)) {
			$iCountCol = count($aTemp);
		} else {
			$iCountCol = 0;
		}

		$iIncement = 0;
		if($iTax == 0 ){
			$iIncement = 1;
		}
		$iCountCol = $iCountCol + $iIncement;

		$sType = $this->aCustomData['document_type'];
		//////////////////////////////////////////////////////

		$bGroup = false;

		if($this->aCustomData['iGroup'] > 0){
			$bGroup = true;
		}

		$aHeader = array();

		// Alle Spalten counten, die keinen Amount anzeigen
		$iNoAmountColumns = 0;

		$i = 0;
		
		if(in_array(Ext_Thebing_Inquiry_Document::PDF_LINEITEM_NUMBERING, $aExclusive)) {
			$aHeader[$i]['width'] = '10';
			$aHeader[$i]['text'] = '#';
			$aHeader[$i]['align'] = 'R';
			$aHeader[$i]['column'] = 'number';
			$i++;
			$iNoAmountColumns++;
		}
		
		if(
			$bGroup
		) {
			$aHeader[$i]['width'] = '10';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Anzahl', $sLanguage);
			$aHeader[$i]['align'] = 'R';
			$aHeader[$i]['column'] = 'quantity';
			$i++;
			$iNoAmountColumns++;
		}

		// Beschreibung
		$aHeader[$i]['width'] = 'auto';
		$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Position', $sLanguage);
		$aHeader[$i]['align'] = 'L';
		$aHeader[$i]['column'] = 'description';
		$i++;
		$iNoAmountColumns++;

		if(
			!(
				$sPositionColumnsType == 'creditnote' &&
				$oSchool->commission_column == 1
			) ||
			!(
				$sPositionColumnsType == 'net' &&
				$oSchool->netto_column == 1
			)
		){
			$aHeader[$i]['width'] = '30';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Betrag', $sLanguage);
			$aHeader[$i]['align'] = 'R';
			$aHeader[$i]['column'] = 'amount';
			$i++;
		}

		if($sPositionColumnsType == 'net') {

			// Nur Nettospalte auf Nettorechnung
			if(
				$oSchool->netto_column != 1
			) {

				$aHeader[$i]['width'] = '25';
				$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Provision', $sLanguage);
				$aHeader[$i]['align'] = 'R';
				$aHeader[$i]['column'] = 'amount_provision';
				$i++;

				// Nettospalte kann hier auch ausgeblendet werden, da die Nettobeträge in der "Betrag" Spalte ankommn :)
			$aHeader[$i]['width'] = '30';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Netto', $sLanguage);
			$aHeader[$i]['align'] = 'R';
			$aHeader[$i]['column'] = 'amount_net';
			$i++;
			}


		} elseif($sPositionColumnsType == 'creditnote') {

			// Gutschrift nur mit Provisionsspalte
			if($oSchool->commission_column != 1) {
				$aHeader[$i]['width'] = '30';
				$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Netto', $sLanguage);
				$aHeader[$i]['align'] = 'R';
				$aHeader[$i]['column'] = 'amount_net';
				$i++;
			}

			$aHeader[$i]['width'] = '25';
			$aHeader[$i]['text'] = Ext_TC_Placeholder_Abstract::translateFrontend('Provision', $sLanguage);
			$aHeader[$i]['align'] = 'R';
			$aHeader[$i]['column'] = 'amount_provision';
			$i++;

		}

		// Steuerspalte anzeigen wenn Steuern in Schule UND im Multiselect ausgewählt
		if(
			$iTax > 0 &&
			(
				in_array( 0, $aExclusive) ||
				in_array( 1, $aExclusive)
			)
		) {
			$sVat = '';
			if($iTax == 1) {
				$sVat = Ext_TC_Placeholder_Abstract::translateFrontend('inkl. VAT', $sLanguage);
			}
			if($iTax == 2) {
				$sVat = Ext_TC_Placeholder_Abstract::translateFrontend('zzgl. VAT', $sLanguage);
			}
			$aHeader[$i]['width'] = '30';
			$aHeader[$i]['text'] = $sVat;
			$aHeader[$i]['align'] = 'R';
			$aHeader[$i]['column'] = 'amount_vat';
			$i++;
		}

		if(
			$sPositionColumnsType == 'creditnote' &&
			$oSchool->commission_column == 1
		) {
			Ext_Thebing_Inquiry_Document_Version::deletePositionsColumn('amount_net', $aData, true);
			Ext_Thebing_Inquiry_Document_Version::deletePositionsColumn('amount', $aData, true);
		}
		
		if(
			$sPositionColumnsType == 'net' &&
			$oSchool->netto_column == 1
		) {
			Ext_Thebing_Inquiry_Document_Version::deletePositionsColumn('amount_provision', $aData, true);
			Ext_Thebing_Inquiry_Document_Version::deletePositionsColumn('amount', $aData, true);
		}

		// Wenn inquirypositions = 2, dann sollen die einzelbeträge nicht angezeigt werden
		if($this->aCustomData['oTemplateType']->element_inquirypositions == 2) {

			foreach((array)$aData as $iKey=>$aItem) {
				
				if(!is_array($aItem)) {
					break;
				}
				
				for($i=$iNoAmountColumns; $i < count($aItem); $i++) {
					$aData[$iKey][$i]['text'] = '';
				}
				
			}

		}

		// "Tabelle" schreiben
		$this->writePositions($aHeader, $aData, $bDisplayHeader);

		// Leichter Abstand zum darauffolgenden text
		$this->setY($this->getY() + 5);

	}

	public function writePositions($aHeader, $aData, $bDisplayHeader = true ){

// BEISPIEL
//		if(empty($aHeader)){
//
//			$aHeader = array();
//			$aHeader[0]['width'] = '50';
//			$aHeader[0]['text'] = 'Test 1';
//			$aHeader[0]['align'] = 'L';
//			$aHeader[1]['width'] = '100';
//			$aHeader[1]['text'] = 'Test 2';
//			$aHeader[1]['align'] = 'R';
//
//		}
//
//		if(empty($aData)){
//
//			$aData = array();
//			$aData[0][0]['text'] = 'Test 1_1';
//			$aData[0][0]['align'] = 'L';
//			$aData[0][1]['text'] = 'Test 1_2';
//			$aData[0][1]['align'] = 'R';
//			$aData[1] = 'line';
//			$aData[2][0]['text'] = 'Test 2_1';
//			$aData[2][1]['text'] = 'Test 2_2';
//
//		}

		if(empty($aHeader)){
			$bDisplayHeader = false;
		}

		if($bDisplayHeader){
			$this->drawPositionTr($aHeader);

			$this->drawLine();

		} else {
			// breiten berechnen
			$this->calculateTdWidths($aHeader);
		}

		foreach($aData as $aTR){

			if(!is_array($aTR)){
				$this->drawLine();
			} else {
				$this->drawPositionTr($aTR);
			}
		}
		#$this->iLastMaxY = 0;

	}

	protected function calculateTdWidths(&$aData) {

		$aMargins = $this->getMargins();
		$iWidth = $this->getPageWidth();
		$x = $this->getX();

		$aTDWidths = array();

		$iWidthLeft = $iWidth - $x - $aMargins['right'];

		$iContentWidth = $iWidthLeft;

		$iAutoTdCount = 0;
		foreach((array)$aData as $iKey => $aTd){

			if(
				isset($aTd['width']) &&
				$aTd['width'] != 'auto'
			){
				$aTDWidths[$iKey] = (int)$aTd['width'];
				$iWidthLeft -= (int)$aTd['width'];
			} else {
				$aTDWidths[$iKey] = 0;
				$iAutoTdCount++;
			}

			if(!isset($aTd['align'])){
				$aData[$iKey]['align'] = 'L';
			}

		}

		foreach((array)$aTDWidths as $iKey => $w){
			if($w == 0){
				$w = $iWidthLeft / $iAutoTdCount;
				$aTDWidths[$iKey] = $w;
			}
			$aData[$iKey]['calculated_width'] = $w;
		}

		if(
			!is_array($aData) ||
			count($aData) != $iAutoTdCount
		) {
			$this->aTDWidths = $aTDWidths;
			$this->iContentWidth = $iContentWidth;
		}

	}

	protected $aTDWidths = array();
	protected $iContentWidth = 0;
	public $iLastMaxY = 0;
	public function drawPositionTr($aData){

		$aMargins = $this->getMargins();

		// Eine Positionszeile steht immer auf einer Seite, daher automatischen Umbruch verhindern
		// In seltenen Fällen kann es sonst passieren, dass die Zellen auf einzelne Seiten geschrieben werden
		$this->SetAutoPageBreak(false, $aMargins['bottom']);

		$x = $this->getX();
		if($this->iLastMaxY != 0){
			$y = $this->iLastMaxY;
		} else {
			$y = $this->getY();
		}
		$iMaxY = ($this->getPageHeight() - $this->aCustomData['oTemplateType']->first_page_border_bottom);

		// breiten berechnen ( falls welche gesetzt sind )
		$this->calculateTdWidths($aData);
		
		$fCellHeight = 0;

		// Höhe der größten Zelle ermitteln
		foreach($aData as $aCell) {
			if($aCell['column'] == 'description') {
				$fCellHeight = $this->getStringHeight($aCell['calculated_width'], $aCell['text']);
				break;
			}
		}

		// Wenn keine Spalte als description markiert ist, Höhe der ersten Spalte holen
		if($fCellHeight == 0) {
			$fCellHeight = $this->getStringHeight($aData[0]['calculated_width'], $aData[0]['text']);
		}

		$iMaxY -= $fCellHeight;

		// Prüfen, ob komplette Zeile noch auf die Seite passt
		if($y >= $iMaxY) {
			$this->drawLine();
			$this->AddPage();
			$y = $this->aCustomData['oTemplateType']->additional_page_border_top;
		}

		$this->iLastMaxY = $y;

		$aTDWidths = $this->aTDWidths;

		$xRow = $x;
		$yRow = $y + 0.7;

		foreach((array)$aData as $iKey => $aTd){

			if($aTd['colspan'] > 1) {
				$iCols = $aTd['colspan'] + $iKey;
				$iWidth = 0;
				for($iC=$iKey;$iC<=$iCols; $iC++) {
					$iWidth += $aTDWidths[$iC];
				}
			} else {
				$iWidth = $aTDWidths[$iKey];
			}

			$this->MultiCell(
								$iWidth,
								1,
								$this->cleanAndReplaceText($aTd['text']),
								0,
								$aTd['align'],
								0,
								2,
								$xRow,
								$yRow,
								true,
								0 ,
								true
					);

			$tempY = $this->getY();
			if($tempY > $this->iLastMaxY) {
				$this->iLastMaxY = $tempY;
			}

			$xRow += $aTDWidths[$iKey];

		}

		$this->iLastMaxY = $this->iLastMaxY + 0.7;

		$this->setX($x);

		// Automatischen Zeilenumbruch wieder aktivieren
		$this->SetAutoPageBreak(true, $aMargins['bottom']);

	}

	public function drawLine() {

		$aMargins = $this->getMargins();
		$iWidth = $this->getPageWidth();

		if($this->iLastMaxY <= 0) {
			$this->iLastMaxY = $this->getY();
		}

		$x = $this->getX();
		$y = $this->iLastMaxY;

		$x2 = $iWidth - $aMargins['right'];
		$y2 = $y;

		$this->Line($x, $y, $x2, $y2);

	}

	public function setFontSettings($oElement, $bIgnoreStyle=false, $bForce=false) {

		if(
			$bForce ||
			$oElement->font_spacing != 0
		) {
			$this->setFontSpacing($oElement->font_spacing);
		}

		if(
			$bForce ||
			$oElement->font_type
		) {
			$sFontType = $oElement->font_type;
		} else {
			$sFontType = $this->aCustomData['oTemplateType']->font_type;
		}

		$sFontFile = '';
		if(is_numeric($sFontType)){
			$oFont	= Ext_TC_System_Font::getInstance($sFontType);
			$sFontType		= $oFont->getFontName('');
		}

		if(
			!$bIgnoreStyle &&
			$oElement->font_style
		) {
			$sFontStyle = $oElement->font_style;
		} else {
			$sFontStyle = '';
		}

		$this->SetFont($sFontType, $sFontStyle);

		if(
			$bForce ||
			$oElement->font_size > 0
		) {
			$this->SetFontSize($oElement->font_size);
		}

	}

	public function setDefaultFontSettings() {

		$this->setFontSettings($this->aCustomData['oTemplateType'], true, true);

	}

	/**
	 * Margins für erste Seite oder Folgeseiten ermitteln
	 *
	 * @return array
	 */
	protected function getCurrentPageMargins() {

		$aMargins = [];

		if($this->iDocPageNr == 1) {
			$aMargins['bottom'] = (float)$this->aCustomData['oTemplateType']->first_page_border_bottom;
			$aMargins['left'] = (float)$this->aCustomData['oTemplateType']->first_page_border_left;
			$aMargins['top'] = (float)$this->aCustomData['oTemplateType']->first_page_border_top;
			$aMargins['right'] = (float)$this->aCustomData['oTemplateType']->first_page_border_right;
		} elseif($this->iDocPageNr > 0) {
			$aMargins['bottom'] = (float)$this->aCustomData['oTemplateType']->additional_page_border_bottom;
			$aMargins['left'] = (float)$this->aCustomData['oTemplateType']->additional_page_border_left;
			$aMargins['top'] = (float)$this->aCustomData['oTemplateType']->additional_page_border_top;
			$aMargins['right'] = (float)$this->aCustomData['oTemplateType']->additional_page_border_right;
		}

		return $aMargins;

	}

}