<?php

define('WDPDF_USE_TCPDF',false);
define('PDF_FILE_EXTENSION', '.pdf');
define('PDF_FONT_NAME', 'arial');
define('PDF_FONT_BOLD', 'B');


class Ext_TC_Pdf extends Ext_TC_Pdf_Fpdi {

	/**
	 * Object for Template - Value connection
	 * @var object
	 */
	protected $_oObject;

	/**
	 * @var Ext_TC_Pdf_Template
	 */
	protected $_oTemplate;

	/**
	 * @var Ext_TC_Pdf_Layout
	 */
	protected $_oLayout;

	/**
	 * Template Language
	 * @var string
	 */
	protected $_sLanguage = 'en';

	/**
	 * current Page Count
	 * @var type
	 */
	protected $_iDocPageNr = 0;

	protected $_aErrors = array();

	/**
	 * create The PDF Class
	 * $oObject is the Object who is connect over tc_pdf_templates_objects
	 * @param Ext_TC_Pdf_Template $oTemplate
	 * @param object $oObject
	 * @param string $sLanguage
	 */
	public function __construct($oTemplate, $oObject, $sLanguage) {

		$this->_oTemplate				= $oTemplate;
		$this->_oLayout					= $this->_oTemplate->getLayout();
		$this->_oObject					= $oObject;
		$this->_sLanguage				= $sLanguage;

		if(is_object($this->_oTemplate)) {

			if(is_object($this->_oLayout)) {

				// Format auslesen
				$sOrientation	= $this->_oLayout->page_format;
				$aFormat[0]		= $this->_oLayout->page_format_width;
				$aFormat[1]		= $this->_oLayout->page_format_height;

				parent::__construct($sOrientation, 'mm', $aFormat);

			} else {
				$this->_aErrors[] = L10N::t('Es wurde kein passendes Layout für das Template gefunden!');
			}

		} else {
			$this->_aErrors[] = L10N::t('Es wurde kein Template gefunden!');
		}
				
		// Header und Footer einblenden
		$this->setPrintHeader(true);
		$this->setPrintFooter(true);

		$this->SetCreator('Fidelo Software GmbH');

		$this->SetDisplayMode('fullpage');

		/**
		 * Default-Margins setzen
		 */
		$aMargin = array();
		$aMargin['bottom'] 	= (float)$this->_oLayout->first_page_border_bottom;
		$aMargin['left'] 	= (float)$this->_oLayout->first_page_border_left;
		$aMargin['top'] 	= (float)$this->_oLayout->first_page_border_top;
		$aMargin['right'] 	= (float)$this->_oLayout->first_page_border_right;
		$this->SetMargins($aMargin['left'], $aMargin['top'], $aMargin['right'], true);

		$this->SetAutoPageBreak(TRUE, $aMargin['bottom']);

	}

	/**
	 * Füllt das PDF mit Inhalt
	 */
	public function create() {

		if(!empty($this->_aErrors)) {
			return false;
		}

		$this->AddPage();

		$iBackgroundPdf		= (int)$this->_oTemplate->getOptionValue($this->_sLanguage, $this->_oObject->id, 'first_page_pdf_template');

		if($iBackgroundPdf <= 0){
			$this->_aErrors[] = L10N::t('Kein PDF Hintergrund gefunden!');
		} else {

			$oBackgroundPdf		= Ext_TC_Upload::getInstance($iBackgroundPdf);
			$sBackgroundPath	= (string)$oBackgroundPdf->getPath(true);

			if(!is_file($sBackgroundPath)) {

				$this->_aErrors[] = L10N::t('Der PDF Hintergrund wurde nicht gefunden!');

			} else {

				$this->setSourceFile($sBackgroundPath);
				$tplidx = $this->importPage(1, '/MediaBox');
				$this->useTemplate($tplidx);
				
				// Damit der Inhalt nicht unter den Hintergrund rutscht
				$this->setPageMark();

				// Fliesstextelement ausgeben, falls vorhanden
				$aElements = $this->_oLayout->getElements();

				$oText = null;
				$bContinuationPage = false;
				foreach((array)$aElements as $oElement) {
					if($oElement->element_type == 'main_text') {
						$oText = $oElement;
					} elseif($oElement->page === 'additional') {
						$bContinuationPage = true;
					}
				}

				if($oText !== null) {

					// Fliesstext geht über die volle Breite und fängt links an
					$iTempX = $this->_oLayout->first_page_border_left;
					$iTempY = $oText->y;
					$iElementWidth = $this->calculateElementWidth();

					$this->SetXY(
						$iTempX,
						$iTempY
					);
					
					$sPageBreakPlaceholder = '[page_break]';
					
					$mValue = $this->getElementValue($oText);

					$bPageBreak = false;
					if(mb_strpos($mValue, '{page_break}') !== false) {		
						// Platzhalter für Seitenumbruch umwandeln, damit es keinen Platzhalterfehler gibt
						$mValue = str_replace('{page_break}', $sPageBreakPlaceholder, $mValue);
						$bPageBreak = true;
					}
					
					// Mit hilfe von Platzhalterobjekten den Text ergänzen
					// und das html säubern
					$mValue = $this->cleanAndReplaceText(
						$this->convertNativePlaceholders($mValue)
					);
					
					// Schrifteinstellungen setzen
					$this->setFontSettings($oText);

					$bPositionTableHook = false;

					// Wenn spezielle Rechnungspositionstabelle angezeigt werden soll
					if(
						mb_strpos($mValue, '%position_table_hook%') !== false
					) {

						$oPurifier = new HTMLPurifierWrapper('all');

						$aContentParts = explode('%position_table_hook%', $mValue);
						$mValue = $oPurifier->purify($aContentParts[0]);
						$mSecondValue = $oPurifier->purify($aContentParts[1]);
						$bPositionTableHook = true;
					}

					// Wenn im Template ein Seitenumbruch eingebaut wurde
					if($bPageBreak) {
					
						// Inhalt in Abschnitte aufteilen
						$aChunks		= explode($sPageBreakPlaceholder, $mValue);						
						$iChunks		= count($aChunks);
						$iPageCount		= 1;

						// Für jeden Abschnitt eine neue Seite
						foreach($aChunks as $sChunk) {
							$sChunk = str_replace($sPageBreakPlaceholder, '', $sChunk);						

							if($iPageCount > 1) {
								$iTempX = $this->_oLayout->additional_page_border_left;
								$iTempY = $this->_oLayout->additional_page_border_top;
							}
							
							$this->writeHTMLCell(
								(float)$iElementWidth,
								0,
								$iTempX,
								$iTempY,
								$sChunk,
								0,
								1,
								false,
								true,
								'L',
								true
							);

							// Neue Seite erzeugen
							if($iPageCount < $iChunks) {
								$this->AddPage();
							}
							
							++$iPageCount;
						}
					
					} else {

						$this->writeHTMLCell(
							(float)$iElementWidth,
							0,
							$iTempX,
							$iTempY,
							$mValue,
							0,
							1,
							false,
							true,
							'L',
							true
						);

					}

					if($bPositionTableHook === true) {

						$this->executePositionTableHook();

						$iTempY = $this->getY();

						// Schrifteinstellungen setzen
						$this->setFontSettings($oText);

						$this->writeHTMLCell(
							(float)$iElementWidth,
							0,
							$iTempX,
							$iTempY,
							$mSecondValue,
							0,
							1,
							false,
							true,
							'L',
							true
						);

					}

				}

				// Wenn mindestens ein Element existiert, welches auf der Folgeseite angezeigt wird,
				// 	dann muss es mindestens zwei Seiten geben, damit das Element auch angezeigt wird!
				if(
					$bContinuationPage &&
					$this->getNumPages() < 2
				) {
					$this->AddPage();
				}

			}

		}

	}

	public function Header() {

		if(
			$this->bSkipHeader ||
			!$this->_oLayout
		){
			return false;
		}

		// Erste seite
		$aMargin = array();

		if($this->GetPage() == 1) {
			$aMargin['bottom'] 	= (float)$this->_oLayout->first_page_border_bottom;
			$aMargin['left'] 	= (float)$this->_oLayout->first_page_border_left;
			$aMargin['top'] 	= (float)$this->_oLayout->first_page_border_top;
			$aMargin['right'] 	= (float)$this->_oLayout->first_page_border_right;
		} else {
			$aMargin['bottom'] 	= (float)$this->_oLayout->additional_page_border_bottom;
			$aMargin['left'] 	= (float)$this->_oLayout->additional_page_border_left;
			$aMargin['top'] 	= (float)$this->_oLayout->additional_page_border_top;
			$aMargin['right'] 	= (float)$this->_oLayout->additional_page_border_right;
			
			$iBackgroundPdf		= (int)$this->_oTemplate->getOptionValue($this->_sLanguage, $this->_oObject->id, 'additional_page_pdf_template');

			if($iBackgroundPdf <= 0){
				$this->_aErrors[] = L10N::t('Kein PDF Hintergrund gefunden!');
			} else {

				$oBackgroundPdf		= Ext_TC_Upload::getInstance($iBackgroundPdf);
				$sBackgroundPath	= (string)$oBackgroundPdf->getPath(true);

				if(!is_file($sBackgroundPath)){
					$this->_aErrors[] = L10N::t('Der PDF Hintergrund wurde nicht gefunden!');
				} else {

					$this->setSourceFile($sBackgroundPath);
					$tplidx = $this->importPage(1, '/MediaBox');
					$this->useTemplate($tplidx);

					// Damit der Inhalt nicht unter den Hintergrund rutscht
					$this->setPageMark();

				}
			}
			
		}

		$this->SetMargins($aMargin['left'], $aMargin['top'], $aMargin['right'], true);

		$this->SetAutoPageBreak(TRUE, $aMargin['bottom']);

	}

	/**
	 *
	 * @param Ext_TC_Pdf_Layout_Element $oElement
	 * @return string
	 */
	public function getElementValue($oElement){

		$mValue = $oElement->getValue($this->_sLanguage, $this->_oTemplate->id);
		return $mValue;

	}

	public function Footer() {

		if(
			$this->bSkipFooter ||
			!$this->_oLayout
		) {
			return false;
		}

		// Alle Dynamischen Elemente des Templates holen
		$aElements = $this->_oLayout->getElements();

		foreach((array)$aElements as $oElement) {

			if(
				(
					$oElement->page == 'first' && 
					$this->getPage() > 1
				) || 
				(
					$oElement->page == 'additional' && 
					$this->getPage() < 2
				) ||
				$oElement->element_type == 'main_text'
			) {
				continue;
			}
			
			//Setzen der Elementkoordinaten
			$iTempX = $oElement->x;
			$iTempY = $oElement->y;
			$iElementWidth = $oElement->element_width;

			$this->SetXY(
				$iTempX,
				$iTempY
			);

			$mValue = $this->getElementValue($oElement);

			// Mit hilfe von Platzhalterobjecten den Text ergänzen
			// und das html säubern
			$mValue = $this->cleanAndReplaceText(
				$this->convertNativePlaceholders($mValue)
			);
		
			// Schrifteinstellungen setzen
			$this->setFontSettings($oElement);

			if($oElement->element_type == 'img') {

				$sImgSrc = '';

				// TODO auslesen des Pfades
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
				
				$this->writeHTMLCell(
					(float)$iElementWidth,
					0,
					$iTempX,
					$iTempY,
					$mValue,
					0,
					1,
					false,
					true,
					'L',
					true
				);

			}

			$this->setDefaultFontSettings();

		}

		$this->_iDocPageNr++;

	}

	/**
	 * Methode kann abgeleitet werden, um eine spezielle Positionstabelle zu erzeugen 
	 */
	public function executePositionTableHook() {

	}

	/**
	 * Hier die nativen Platzhalter von {..} auf [..] umschreiben, damit die Platzhalter-Klasse nicht versucht
	 * diese zu ersetzen
	 *
	 * @see cleanAndReplaceText()
	 * @param $mText
	 * @return string|string[]
	 */
	protected function convertNativePlaceholders($mText) {
		$mText = str_replace('{current_page}', '[current_page]', $mText);
		$mText = str_replace('{total_pages}', '[total_pages]', $mText);

		return $mText;
	}

	/**
	 * Replace Placeholder and Clearn HTML
	 * @param string $mText
	 * @return string
	 */
	protected function cleanAndReplaceText($mText){

		// Platzhalter für Seite X von Y ersetzen
		$mText = str_replace('[current_page]', $this->getAliasNumPage(), $mText);
		$mText = str_replace('[total_pages]', $this->getAliasNbPages(), $mText);

		return $mText;

	}

	/**
	 * Set the Front Settings ( Size, Spacing, Type, Style )
	 * @param Ext_TC_Pdf_Layout $oElement
	 * @param boolean $bIgnoreStyle
	 * @param boolean $bForce
	 */
	protected function setFontSettings($oElement, $bIgnoreStyle=false, $bForce=false) {

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
			$oClientFont	= Ext_TC_System_Font::getInstance($sFontType);
			$sFontType		= $oClientFont->getFontName('');
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

	/**
	 * Set the Default Front Settings ( Size, Spacing, Type, Style )
	 */
	protected function setDefaultFontSettings() {

		$this->setFontSettings($this->_oLayout, true, true);

	}

	public function calculateElementWidth() {
		$iElementWidth = $this->_oLayout->page_format_width - $this->_oLayout->first_page_border_left - $this->_oLayout->first_page_border_right;
		return (float) $iElementWidth;
	}
	
	public function getErrors(){
		return $this->_aErrors;
	}

	public function getLayout() {
		return $this->_oLayout;
	}
	
}
