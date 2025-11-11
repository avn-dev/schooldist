<?php

/*
 * -- webDynamics pdf classes --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/wdpdf/wdpdf.php
 * 
 */
 
/**
 * webDynamics PDF object that contains invoice items.
 */
class wdPDF_ExtendedDocument extends wdPDF {


	/**
	 * The list of invoice items.
	 *
	 * Each element will be an instance
	 * of wdPDF_ExtendedDocument_InvoiceItem.
	 *
	 * @var array
	 */
	protected $_aInvoiceItems = array();


	/**
	 * The name of the quantity value variable.
	 *
	 * @var string
	 */
	protected $_sQuantityValueName = 'quantity';


	/**
	 * The name of the amount value variable.
	 *
	 * @var string
	 */
	protected $_sAmountValueName = 'amount';


	/**
	 * The name of the vat value variable.
	 *
	 * @var string
	 */
	protected $_sVatValueName = 'vat';


	/**
	 * The address line of the document.
	 *
	 * @var string
	 */
	protected $_sAddressLine = '';


	/**
	 * The address of the document.
	 *
	 * @var string
	 */
	protected $_sAddress = '';


	/**
	 * The subject of the document.
	 *
	 * @var string
	 */
	protected $_sSubject = '';


	/**
	 * The head text of the document.
	 *
	 * Will be show before the list of invoice items.
	 *
	 * @var string
	 */
	protected $_sHeadText = '';


	/**
	 * The foot text of the document.
	 *
	 * Will be show after the list of invoice items.
	 *
	 * @var string
	 */
	protected $_sFootText = ''; 


	/**
	 * The start position on the first page (below the subject)
	 */
	protected $_iStartPositionBelowSubject = 103;


	/**
	 * The document items were added to the wdpdf document.
	 *
	 * @var boolean
	 */
	protected $_bDocumentItemsAdded = false;


	/**
	 * The invoice items were added to the internal fpdf/fpdi object.
	 *
	 * @var boolean
	 */
	protected $_bInvoiceItemsAdded = false;


	/**
	 * The invoice item amounts are specified pre tax.
	 *
	 * @var boolean
	 */
	protected $_bInvoiceItemsPreTax = false;
	

	/**
	 * The invoice item amounts are specified pre tax.
	 *
	 * @var array
	 */
	protected $_aInvoiceTableConfig = array();


	/**
	 * The invoice item amounts are specified pre tax.
	 *
	 * @var array
	 */
	protected $_iInvoiceDiscount = 0;


	/**
	 * The invoice item amounts are specified pre tax.
	 *
	 * @var string
	 */
	protected $_sCurrency = '€';
	
	
	protected $_iSwitchSign = 0;


	protected $_aTranslations = array(
										'carryover'=>'Übertrag',
										'subtotal'=>'Zwischensumme',
										'less_discount'=>'abzgl. %s %% Gesamtrabatt',
										'net_total'=>'Gesamt Netto',
										'plus_vat'=>'zzgl. %s %% USt. auf',
										'total'=>'Gesamtbetrag'
										);

	/**
	 * The list of document templates.
	 *
	 * @var array
	 */
	protected $_aDocumentTemplates = array();


	protected $_aDocumentFont = array();


	/**
	 * height of invoice items table head
	 */
	protected $_intTableHeadHeight = 0;


	/**
	 * If header was already writen, allow the writing of subprices
	 */
	protected $_bAllowSubPrices = false;


	/**
	 * The document type
	 */
	protected $_bHighlightOfferNetPrice = false;


	/**
	 * Display or hide starttext, endtext, table and tablefoot
	 */
	protected $_aDisplayElements = array(
		'starttext'	=> 1,
		'endtext'	=> 1,
		'table'		=> 1,
		'tablefoot'	=> 1
	);


	/**
	 * Set object properties.
	 *
	 * @param string $sName
	 * @param mixed $mValue
	 * @return void
	 */
	public function __set($sName, $mValue) {

		// convert the passed arguments
		$sName = (string)$sName;

		// set the specified value
		switch ($sName) {

			// quantity value name
			case 'quantityValueName':
				$this->_sQuantityValueName = (string)$mValue;
				return;

			// amount value name
			case 'amountValueName':
				$this->_sAmountValueName = (string)$mValue;
				return;

			// vat value name
			case 'vatValueName':
				$this->_sVatValueName = (string)$mValue;
				return;

			// address line
			case 'addressLine':
				$this->_sAddressLine = (string)$mValue;
				return;

			// address
			case 'address':
				$this->_sAddress = (string)$mValue;
				return;

			// subject
			case 'subject':
				$this->_sSubject = (string)$mValue;
				return;

			// head text
			case 'headText':
				$this->_sHeadText = (string)$mValue;
				return;

			// foot text
			case 'footText':
				$this->_sFootText = (string)$mValue;
				return;

			// invoice items pre tax
			case 'invoiceItemsPreTax':
				$this->_bInvoiceItemsPreTax = (boolean)$mValue;
				return;

			// currency
			case 'currency':
				$this->_sCurrency = (string)$mValue;
				return;
			
			// document templates
			case 'documentTemplates':
				$this->_aDocumentTemplates = (array)$mValue;
				return;
				
			// document font
			case 'documentFont':
				$this->_aDocumentFont = (array)$mValue;
				return;

			case 'fLineHeightFactor':
				$this->_oFPDI->fLineHeightFactor = (float)$mValue;
				return;

			case 'startPosition':
				$this->_iStartPositionBelowSubject = (int)$mValue;
				return;

			case 'displayElements':
				$this->_aDisplayElements = (array)$mValue;
				return;

			case 'highlightOfferNetPrice':
				$this->_bHighlightOfferNetPrice = (boolean)$mValue;
				return;
				
			case 'translations':
				$this->_aTranslations = (array)$mValue;
				return;
				
			case 'switchSign':
				$this->_iSwitchSign = (int)$mValue;
				return;
		}

		// call parent method
		parent::__set($sName, $mValue);

	}


	/**
	 * Get object properties.
	 *
	 * @param string $sName
	 * @return mixed
	 */
	public function __get($sName) {

		// convert the passed arguments
		$sName = (string)$sName;

		// return the specified value
		switch ($sName) {

			// quantity value name
			case 'quantityValueName':
				$sReturn = (string)$this->_sQuantityValueName;
				return $sReturn;

			// amount value name
			case 'amountValueName':
				$sReturn = (string)$this->_sAmountValueName;
				return $sReturn;

			// vat value name
			case 'vatValueName':
				$sReturn = (string)$this->_sVatValueName;
				return $sReturn;

			// list of invoice items
			case 'invoiceItems':
				$aReturn = (array)$this->_aInvoiceItems;
				return $aReturn;
				
			// document templates
			case 'documentTemplates':
				$aReturn = (array)$this->_aDocumentTemplates;
				return $aReturn;

			// address line
			case 'addressLine':
				$sReturn = (string)$this->_sAddressLine;
				return $sReturn;

			// address
			case 'address':
				$sReturn = (string)$this->_sAddress;
				return $sReturn;

			// subject
			case 'subject':
				$sReturn = (string)$this->_sSubject;
				return $sReturn;

			// head text
			case 'headText':
				$sReturn = (string)$this->_sHeadText;
				return $sReturn;

			// foot text
			case 'footText':
				$sReturn = (string)$this->_sFootText;
				return $sReturn;

			// invoice items pre tax
			case 'invoiceItemsPreTax':
				$bReturn = (bool)$this->_bInvoiceItemsPreTax;
				return $bReturn;

			// invoice discount
			case 'invoiceDiscount':
				$fReturn = (float)$this->_iInvoiceDiscount;
				return $fReturn;
			
			// document font
			case 'documentFont':
				$fReturn = (array)$this->_aDocumentFont;
				return $fReturn;
				
			// document font
			case 'startPosition':
				$iReturn = (int)$this->_iStartPositionBelowSubject;
				return $iReturn;

			// elements to display in the document
			case 'displayElements':
				$aReturn = (array)$this->_aDisplayElements;
				return $aReturn;

			// currency
			case 'currency':
				$aReturn = $this->_sCurrency;
				return $aReturn;

			case 'highlightOfferNetPrice':
				$sReturn = $this->_bHighlightOfferNetPrice;
				return $sReturn;
				
			case 'switchSign':
				$sReturn = $this->_iSwitchSign;
				return $sReturn;
				
		}

		// call parent method
		return parent::__get($sName);

	}



	/**
	 * Constructor.
	 *
	 * @param array $aConfig
	 * @param array $aData
	 * @return void
	 */
	public function __construct(array $aConfig = array(), array $aData = array()) {
		parent::__construct($aConfig, $aData);
	}

	public function setInvoiceTableConfig(array $aConfig) {
		
		$this->_aInvoiceTableConfig = $aConfig;
		
	}

	/**
	 * Add invoice item to the document.
	 *
	 * @param array $aInvoiceItem
	 * @return void
	 */
	public function addInvoiceItem(array $aInvoiceItem) {

		// initialize invoice item object
		$oInvoiceItem = new wdPDF_ExtendedDocument_InvoiceItem();
		$oInvoiceItem->setQuantityValueName($this->_sQuantityValueName);
		$oInvoiceItem->setAmountValueName($this->_sAmountValueName);
		$oInvoiceItem->setVatValueName($this->_sVatValueName);

		// add invoice item values
		foreach ($aInvoiceItem as $sIndex => $mValue) {
			$oInvoiceItem->setValue($sIndex, $mValue);
		}

		// add the invoice item object to the list
		$this->_addInvoiceItemObject($oInvoiceItem);

	}


	/**
	 * Add invoice item to the document.
	 *
	 * @param wdPDF_ExtendedDocument_InvoiceItem $oInvoiceItem
	 * @return void
	 */
	public function addInvoiceItemObject(wdPDF_ExtendedDocument_InvoiceItem $oInvoiceItem) {
		$this->_addInvoiceItemObject($oInvoiceItem);
	}


	/**
	 * Add invoice item to the document.
	 *
	 * Internal method to add the item.
	 *
	 * @param wdPDF_ExtendedDocument_InvoiceItem $oInvoiceItem
	 * @return void
	 */
	protected function _addInvoiceItemObject(wdPDF_ExtendedDocument_InvoiceItem $oInvoiceItem) {
		$this->_aInvoiceItems[] = $oInvoiceItem;
	}


	/**
	 * Remove the invoice item with the specified index.
	 *
	 * @param string $sIndex
	 * @return void
	 */
	public function removeInvoiceItem($sIndex) {
		$this->_removeInvoiceItem($sIndex);
	}


	/**
	 * Remove the invoice item with the specified index.
	 *
	 * Internal method to remove the item.
	 *
	 * @param string $sIndex
	 * @return void
	 */
	protected function _removeInvoiceItem($sIndex) {

		// convert the passed arguments
		$sIndex = (string)$sIndex;

		// remove the invoice item if possible
		if (array_key_exists($sIndex, $this->_aInvoiceItems)) {
			unset($this->_aInvoiceItems[$sIndex]);
		}

	}


	/**
	 * Return the content of the pdf file.
	 *
	 * This method will add the document blocks and invoice
	 * items to the document before returning the content
	 * of the pdf file.
	 *
	 * @return string
	 */
	public function getPDFString() {

		// add document items if required
		if ($this->_bDocumentItemsAdded !== true) {
			$this->_addDocumentItemsToDocument();
		}

		// process invoice items if required
		if ($this->_bInvoiceItemsAdded !== true) {
			$this->_addInvoiceItemsToDocument();
		}

		// call parent method and return pdf contents
		return parent::getPDFString();

	}
	
	public function showPDFFile($sFilename = '', $bClearOutputBuffer = false) {

		// add document items if required
		if ($this->_bDocumentItemsAdded !== true) {
			$this->_addDocumentItemsToDocument();
		}

		// process invoice items if required
		if ($this->_bInvoiceItemsAdded !== true) {
			$this->_addInvoiceItemsToDocument();
		}

		// call parent method and return pdf contents
		return parent::showPDFFile($sFilename, $bClearOutputBuffer);

	}
	

	/**
	 * Hook method that will be called automatically.
	 *
	 * Used by php function: serialize()
	 *
	 * @return array
	 */
	public function __sleep() {

		// call parent method
		$aReturn = parent::__sleep();

		// add additional variables
		$aReturn[] = '_aInvoiceItems';
		$aReturn[] = '_sQuantityValueName';
		$aReturn[] = '_sAmountValueName';
		$aReturn[] = '_sVatValueName';
		$aReturn[] = '_sAddressLine';
		$aReturn[] = '_sAddress';
		$aReturn[] = '_sSubject';
		$aReturn[] = '_sHeadText';
		$aReturn[] = '_sFootText';
		$aReturn[] = '_bDocumentItemsAdded';
		$aReturn[] = '_bInvoiceItemsPreTax';
		$aReturn[] = '_iStartPositionBelowSubject';

		// return the list of variables that
		// should be serialized
		return $aReturn;

	}


	/**
	 * Calls _addDocumentItemsToDocument if not happened yet
	 * @return void
	 */
	public function addDocumentItemsToDocument() {
		
		// add document items if required
		if ($this->_bDocumentItemsAdded !== true) {
			$this->_addDocumentItemsToDocument();
		}

	}

	/**
	 * Add document items to the webDynamics pdf object.
	 *
	 * @return void
	 */
	protected function _addDocumentItemsToDocument() {

		// add the address line
		$aAddressLine              = array();
		$aAddressLine['x']         = 10;
		$aAddressLine['y']         = 50;
		$aAddressLine['font']      = 'helvetica';
		$aAddressLine['font_size'] = 6;
		$aAddressLine['content']   = $this->_sAddressLine;
		$aAddressLine['display']   = 'FIRST';
		$aAddressLine['alignment'] = 'L';
		$this->addDocumentItem($aAddressLine, 'address_line');

		// add the address
		$aAddress              = array();
		$aAddress['x']         = 10;
		$aAddress['y']         = 56;
		$aAddress['font']      = 'helvetica';
		$aAddress['font_size'] = 10;
		$aAddress['content']   = $this->_sAddress;
		$aAddress['display']   = 'FIRST';
		$aAddress['alignment'] = 'L';
		$this->addDocumentItem($aAddress, 'address');

		// add the subject
		$aSubject              = array();
		$aSubject['x']         = 10;
		$aSubject['y']         = 95;
		$aSubject['font']      = 'helveticab';
		$aSubject['font_size'] = 11;
		$aSubject['content']   = $this->_sSubject;
		$aSubject['display']   = 'FIRST';
		$aSubject['alignment'] = 'L';
		$this->addDocumentItem($aSubject, 'subject');

		// mark the method as being executed
		$this->_bDocumentItemsAdded = true;

	}


	/**
	 * Add invoice items to the internal fpdf/fpdi object.
	 *
	 * Will also add the head text and the foot text
	 * to the object, the text will be added on the current
	 * page at the currently set y-position, or at a
	 * fixed position below the subject if the current page
	 * is the first page.
	 *
	 * @return void
	 */
	protected function _addInvoiceItemsToDocument() {

		// get the internal fpdf/fpdi object
		$oFPDI = $this->_oFPDI;

		// if there are no pages yet, we must add a page
		if ($oFPDI->PageNo() < 1) {
			$oFPDI->AddPage();
		}

		// if we are on the first page, we must set the y-position
		// below the subject
		if ($oFPDI->PageNo() < 2) {
			$oFPDI->SetY($this->_iStartPositionBelowSubject);
		}

		if(empty($this->_aDocumentFont['size_table'])) {
			$this->_aDocumentFont['size_table'] = $this->_aDocumentFont['size'];
		}

		// set document font and colors
		$oFPDI->setWDFont($this->_aDocumentFont['font'], $this->_aDocumentFont['size'], $this->_aDocumentFont['style']);
		$oFPDI->SetTextColor(hexdec(substr($this->_aDocumentFont['color'], 0, 2)), hexdec(substr($this->_aDocumentFont['color'], 2, 2)), hexdec(substr($this->_aDocumentFont['color'], 4, 2)));
		$oFPDI->SetDrawColor(hexdec(substr($this->_aDocumentFont['line_color'], 0, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 2, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 4, 2)));

		if($this->_sHeadText && (bool)$this->_aDisplayElements['starttext']) {
			$oFPDI->SetX($this->_aPageMargins['left']);
			// write the head text
			$this->_writeHeadText();
		}

		// set document font and colors
		$oFPDI->setWDFont($this->_aDocumentFont['font'], $this->_aDocumentFont['size_table'], $this->_aDocumentFont['style']);
		$oFPDI->SetTextColor(hexdec(substr($this->_aDocumentFont['color'], 0, 2)), hexdec(substr($this->_aDocumentFont['color'], 2, 2)), hexdec(substr($this->_aDocumentFont['color'], 4, 2)));
		$oFPDI->SetDrawColor(hexdec(substr($this->_aDocumentFont['line_color'], 0, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 2, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 4, 2)));

		if(count($this->_aInvoiceItems) > 0 && (bool)$this->_aDisplayElements['table'])
		{
			$oFPDI->SetX($this->_aPageMargins['left']);

			// write invoice items
			$this->_writeInvoiceItems();
		}

		// set document font and colors
		$oFPDI->setWDFont($this->_aDocumentFont['font'], $this->_aDocumentFont['size'], $this->_aDocumentFont['style']);
		$oFPDI->SetTextColor(hexdec(substr($this->_aDocumentFont['color'], 0, 2)), hexdec(substr($this->_aDocumentFont['color'], 2, 2)), hexdec(substr($this->_aDocumentFont['color'], 4, 2)));
		$oFPDI->SetDrawColor(hexdec(substr($this->_aDocumentFont['line_color'], 0, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 2, 2)), hexdec(substr($this->_aDocumentFont['line_color'], 4, 2)));

		if($this->_sFootText && (bool)$this->_aDisplayElements['endtext']) {
			$oFPDI->SetX($this->_aPageMargins['left']);
			// write foot text
			$this->_writeFootText();
		}

		// mark the method as being executed
		$this->_bInvoiceItemsAdded = true;

	}


	/**
	 * Add the head text to the internal fpdf/fpdi object.
	 *
	 * The text will be added at the currently set y-position.
	 *
	 * @return void
	 */
	protected function _writeHeadText() {

		// get the internal fpdf/fpdi object
		$oFPDI = $this->_oFPDI;

		$oFPDI->resetLastH();

		$this->_sHeadText = $this->replaceAdditionalPlaceholdersInText($this->_sHeadText, 0);

		if(strpos($this->_sHeadText, '{ContactSignatureImage}')) {
			if(is_file($this->_aDisplayElements['signature']))
			{
				$arrTemp = explode('{ContactSignatureImage}', $this->_sHeadText);
				$this->_sHeadText = $arrTemp[0];
				$strHeadText2 = $arrTemp[1];
			}
		}

		// write the head text
		$oFPDI->SetXY($this->_aPageMargins['left'], $oFPDI->GetY()+8);
		$oFPDI->WriteHTML($this->convertUTF8String($this->_sHeadText));

		if(isset($strHeadText2)) {
			$this->setSignatureImage();
			$oFPDI->SetXY($this->_aPageMargins['left'],$oFPDI->GetY()+15);
			$oFPDI->WriteHTML($this->convertUTF8String($strHeadText2));
		}

		//$oFPDI->Ln();

	}

	protected function _setTemplates($aDocumentTemplates)
	{
		$this->documentTemplates = $aDocumentTemplates;
	}


	protected function _writeSubTotal($iLineLength, $iSubSumme)
	{

		$iSubTotalY = $this->_oFPDI->getPageHeight() - $this->_aPageMargins['bottom'] - (($this->_oFPDI->getCurrentLineHeight() * 2) + ($this->_oFPDI->GetLineWidth() * 2));

		// add space after last position on this page
		$this->_oFPDI->SetY($iSubTotalY);

		$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
		$this->_oFPDI->SetX($this->_aPageMargins['left']);
		$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($this->_aTranslations['subtotal']), 0, 0, 'L');
		$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
		$iTmpXSumme = number_format($iSubSumme, 2, ',', '.').' '.$this->convertUTF8String($this->_sCurrency);
		$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $iTmpXSumme, 0, 0, 'R');
		$this->_oFPDI->Ln();
		$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
		$this->_oFPDI->AddPage();

	}

//TODO:
	protected function _writeNumberFormat($sNumber)
	{
		if($this->switchSign == 1 && $sNumber < 0)
		{
			$sNumber = $sNumber * -1;
		}
		
		$sNumberFormated = number_format($sNumber, 2, ',', '.').' '.$this->convertUTF8String($this->_sCurrency);

		return $sNumberFormated;
	}


	/**
	 * Add the invoice items to the internal fpdf/fpdi object.
	 *
	 * The items will be added at the currently set y-position.
	 *
	 * @return void
	 */
	protected function _writeInvoiceItems() {

		// calc height of table head
		$this->_intTableHeadHeight = $this->_oFPDI->getCurrentLineHeight() * 1.5 + $this->_oFPDI->GetLineWidth() * 2;
		$this->_aPageMargins['top'] += $this->_intTableHeadHeight;
		$this->_oFPDI->SetTopMargin($this->_aPageMargins['top']); 

		$aInvoiceTableOnlyTextConfig = array();
        $aInvoiceTablePricedGroupConfig = array();
        $aInvoiceTableWithoutPricesConfig = array();

		$iLineLength = 0;
		$bBeforeText = true;
		foreach ($this->_aInvoiceTableConfig as $sKey => $aValues) {

			$iLineLength += $aValues['width'];

			if ($bBeforeText) {
				$aInvoiceTableOnlyTextConfig[$sKey] = $aValues;
                $aInvoiceTablePricedGroupConfig[$sKey] = $aValues;
                $aInvoiceTableWithoutPricesConfig[$sKey] = $aValues;
			} elseif (in_array($sKey, array('discount', 'vat', 'totalamount'))) {
				$aInvoiceTableOnlyTextConfig['text']['width'] += $aValues['width'];
                $aInvoiceTablePricedGroupConfig[$sKey] = $aValues;
                $aInvoiceTableWithoutPricesConfig['text']['width'] += $aValues['width'];
			} else {
				$aInvoiceTableOnlyTextConfig['text']['width'] += $aValues['width'];
                $aInvoiceTablePricedGroupConfig['text']['width'] += $aValues['width'];
                $aInvoiceTableWithoutPricesConfig['text']['width'] += $aValues['width'];
			}

			if ($sKey == 'text') {
				$bBeforeText = false;
			}

		}
		$iLineLength += $this->_aPageMargins['left'];

		// write the item list headline
		$iInitialY = $this->_oFPDI->GetY()+$this->_oFPDI->getCurrentLineHeight();

		$this->_writeInvoiceItemsHead($iInitialY, $iLineLength);

		// expand top margin for table head on next pages
		$intTableHeadHeightDiff = $this->_oFPDI->getCurrentLineHeight()*1.5 + $this->_oFPDI->GetLineWidth();
		$this->_intTableHeadHeight += $intTableHeadHeightDiff;
		$this->_aPageMargins['top'] += $intTableHeadHeightDiff;
		$this->_oFPDI->SetTopMargin($this->_aPageMargins['top']); 

		// get x-positions of all table columns
		$intXPos = $this->_aPageMargins['left'];
		foreach ((array)$this->_aInvoiceTableConfig as $sKey => $aValue) {

			$this->_aInvoiceTableConfig[$sKey]['x'] = $intXPos;
			$intXPos += $aValue['width'];

			// add decimal counts if not set
			if (!isset($aValue['decimal_places'])) {
				$this->_aInvoiceTableConfig[$sKey]['decimal_places'] = 2;
			}

		}

		$intXPos = $this->_aPageMargins['left'];
		foreach ((array)$aInvoiceTableOnlyTextConfig as $sKey => $aValue) {

			$aInvoiceTableOnlyTextConfig[$sKey]['x'] = $intXPos;
			$intXPos += $aValue['width'];

			// add decimal counts if not set
			if (!isset($aValue['decimal_places'])) {
				$aInvoiceTableOnlyTextConfig[$sKey]['decimal_places'] = 2;
			}

		}

		$intXPos = $this->_aPageMargins['left'];
		foreach ((array)$aInvoiceTablePricedGroupConfig as $sKey => $aValue) {

			$aInvoiceTablePricedGroupConfig[$sKey]['x'] = $intXPos;
			$intXPos += $aValue['width'];

			// add decimal counts if not set
			if (!isset($aValue['decimal_places'])) {
				$aInvoiceTablePricedGroupConfig[$sKey]['decimal_places'] = 2;
			}

		}

		$intXPos = $this->_aPageMargins['left'];
		foreach ((array)$aInvoiceTableWithoutPricesConfig as $sKey => $aValue) {

			$aInvoiceTableWithoutPricesConfig[$sKey]['x'] = $intXPos;
			$intXPos += $aValue['width'];

			// add decimal counts if not set
			if (!isset($aValue['decimal_places'])) {
				$aInvoiceTableWithoutPricesConfig[$sKey]['decimal_places'] = 2;
			}

		}

		// set spacer before first invoice item
		$iNextY = $this->_oFPDI->GetY()+($this->_oFPDI->getCurrentLineHeight()/4);
		$this->_oFPDI->SetXY($this->_aPageMargins['left'], $iNextY);

		$iSubSumme = 0;
		$dTotalReminded = 0;
		$iItemsTmpCounter = 1;
		$iCounter = count($this->_aInvoiceItems);

		$aGroupCache = array();
		$iCurrentGroup = null;

		foreach ((array)$this->_aInvoiceItems as $sKey => $oInvoiceItem) {

			if (
				$oInvoiceItem->only_text &&
				$oInvoiceItem->groupsum &&
                $oInvoiceItem->display_amount == $oInvoiceItem->getEmptyValueReturn()
			) {
                $iCurrentGroup = count($aGroupCache);
                $aGroupCache[$iCurrentGroup] = array(
                    'label' => $oInvoiceItem->title,
                    'amount' => 0
                );
			}

			if ($iCurrentGroup !== null) {
				$aGroupCache[$iCurrentGroup]['amount'] += $oInvoiceItem->getTotalAmount();
			}

            // only text + group amount
			if ($oInvoiceItem->only_text && $oInvoiceItem->display_amount != $oInvoiceItem->getEmptyValueReturn()) {
				$aCurrentTableConfig = $aInvoiceTablePricedGroupConfig;
            }
            // only text
            elseif ($oInvoiceItem->only_text) {
                $aCurrentTableConfig = $aInvoiceTableOnlyTextConfig;
            }
            // hide amount = invoice item with hidden amount (amount displayed at group)
            elseif ($oInvoiceItem->hide_amount != $oInvoiceItem->getEmptyValueReturn()) {
                $aCurrentTableConfig = $aInvoiceTableWithoutPricesConfig;
			}
            // default invoice item
            else {
				$aCurrentTableConfig = $this->_aInvoiceTableConfig;
			}

			// calc needed height for position and table footer
			$iValueY  = $this->_oFPDI->GetY();
			$iValueY += $this->_aPageMargins['bottom'];
			$iValueY += $this->_oFPDI->getCurrentLineHeight() * 3 + $this->_oFPDI->GetLineWidth() * 2;

			if(
				$iValueY > $this->_oFPDI->getPageHeight() && 
				(bool)$this->_aDisplayElements['tablefoot']
			) {
				$this->_writeSubTotal($iLineLength, $iSubSumme);
			}

			// set spacer before invoice item
			$iItemY = $this->_oFPDI->GetY()+($this->_oFPDI->getCurrentLineHeight()/4);
			$this->_oFPDI->SetXY($this->_aPageMargins['left'], $iItemY);

			// Allow the writing of sub prices
			$this->_bAllowSubPrices = true;

			if($aCurrentTableConfig['position'])
			{
				$this->_oFPDI->SetX($aCurrentTableConfig['position']['x']);
				if(!$oInvoiceItem->only_text) {
					$this->_oFPDI->Cell($aCurrentTableConfig['position']['width'], $this->_oFPDI->getCurrentLineHeight(), $oInvoiceItem->position, 0, 0, $aCurrentTableConfig['position']['align']);
				}
			}
			if($aCurrentTableConfig['quantity'])
			{
				$this->_oFPDI->SetX($aCurrentTableConfig['quantity']['x']);
				if(!$oInvoiceItem->only_text) {
					if(!isset($aCurrentTableConfig['quantity']['format']))
					{
						$strQuantity = number_format($oInvoiceItem->getQuantity(), $aCurrentTableConfig['quantity']['decimal_places'], ",", ".");
					}
					else
					{
						$strQuantity = $oInvoiceItem->quantity;
					}
					$this->_oFPDI->Cell($aCurrentTableConfig['quantity']['width'], $this->_oFPDI->getCurrentLineHeight(), $strQuantity, 0, 0, $aCurrentTableConfig['quantity']['align']);
				}
			}
			if($aCurrentTableConfig['unit'])
			{
				$this->_oFPDI->SetX($aCurrentTableConfig['unit']['x']);
				if(!$oInvoiceItem->only_text) {
					$this->_oFPDI->Cell($aCurrentTableConfig['unit']['width'], $this->_oFPDI->getCurrentLineHeight(), $this->convertUTF8String($oInvoiceItem->unit), 0, 0, $aCurrentTableConfig['unit']['align']);
				}
			}
			if($aCurrentTableConfig['number'])
			{
				$this->_oFPDI->SetX($aCurrentTableConfig['number']['x']);
				if(!$oInvoiceItem->only_text) {
					$this->_oFPDI->Cell($aCurrentTableConfig['number']['width'], $this->_oFPDI->getCurrentLineHeight(), $oInvoiceItem->number, 0, 0, $aCurrentTableConfig['number']['align']);
				}
			}
			if($aCurrentTableConfig['amount'])
			{
				$this->_oFPDI->SetX($aCurrentTableConfig['amount']['x']);
				if(!isset($aCurrentTableConfig['amount']['format']))
				{
					$sItemAmount = $oInvoiceItem->getAmount();
					if($this->switchSign == 1 && $sItemAmount < 0)
					{
						$sItemAmount = $sItemAmount * -1;
					}
					$strAmount = number_format($sItemAmount, $aCurrentTableConfig['amount']['decimal_places'], ",", ".").' '.$this->convertUTF8String($this->_sCurrency);
				}
				else
				{
					$sAmount = $oInvoiceItem->amount;
					if($this->switchSign == 1 && $sAmount < 0)
					{
						$sAmount = $sAmount * -1;
					}
					$strAmount = $this->convertUTF8String($sAmount);
				}
				$this->_oFPDI->Cell($aCurrentTableConfig['amount']['width'], $this->_oFPDI->getCurrentLineHeight(), $strAmount, 0, 0, $aCurrentTableConfig['amount']['align']);
			}
			if($aCurrentTableConfig['discount'])
			{
				$this->_oFPDI->SetX($aCurrentTableConfig['discount']['x']);
				if(!isset($aCurrentTableConfig['discount']['format']))
				{
					$strDiscount = number_format($oInvoiceItem->getDiscount(), $aCurrentTableConfig['discount']['decimal_places'], ",", ".") . ' %';
				}
				else
				{
					$strDiscount = $this->convertUTF8String($oInvoiceItem->discount);
				}
				$this->_oFPDI->Cell($aCurrentTableConfig['discount']['width'], $this->_oFPDI->getCurrentLineHeight(), $strDiscount, 0, 0, $aCurrentTableConfig['discount']['align']);
			}
			if($aCurrentTableConfig['vat'])
			{
				$this->_oFPDI->SetX($aCurrentTableConfig['vat']['x']);
				if(!isset($aCurrentTableConfig['vat']['format']))
				{
					$strVat = number_format($oInvoiceItem->getVat(), $aCurrentTableConfig['vat']['decimal_places'], ",", ".") . ' %';
				}
				else
				{
					$strVat = $oInvoiceItem->vat;
				}
				$this->_oFPDI->Cell($aCurrentTableConfig['vat']['width'], $this->_oFPDI->getCurrentLineHeight(), $strVat, 0, 0, $aCurrentTableConfig['vat']['align']);
			}
			if($aCurrentTableConfig['totalamount'])
			{
				$this->_oFPDI->SetX($aCurrentTableConfig['totalamount']['x']);
				if(!isset($aCurrentTableConfig['totalamount']['format']))
				{
					$sTotalAmount = $oInvoiceItem->getTotalAmount();
					if($this->switchSign == 1 && $sTotalAmount < 0)
					{
						$sTotalAmount = $sTotalAmount * -1;
					}

					$strTotalAmount = number_format($sTotalAmount, $aCurrentTableConfig['totalamount']['decimal_places'], ",", ".").' '.$this->convertUTF8String($this->_sCurrency);
					// add item amount to internal variable
					$iSubSumme += $sTotalAmount;
				}
				else
				{
					$sTotalAmount = $oInvoiceItem->totalamount;
					if($this->switchSign == 1 && $sTotalAmount < 0)
					{
						$sTotalAmount = $sTotalAmount * -1;
					}
					$strTotalAmount = $this->convertUTF8String($sTotalAmount);
					$dTotalReminded += intval(str_replace(array('.', ','), array('', ''), $sTotalAmount))/100;
				}
				$this->_oFPDI->Cell($aCurrentTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight(), $strTotalAmount, 0, 0, $aCurrentTableConfig['totalamount']['align']);
			}

			if($aCurrentTableConfig['text'])
			{
				
				$sProduct = $this->convertUTF8String($oInvoiceItem->title);
				$aProduct = $this->getArrayFromText($sProduct, $aCurrentTableConfig['text']['width']);
				
				$this->_oFPDI->SetStyle("B");
				
				foreach($aProduct['lines'] as $iLineKey => $sLineValue) {

					$this->_oFPDI->SetX($aCurrentTableConfig['text']['x']);
					$this->_oFPDI->Cell($aCurrentTableConfig['text']['width'], $this->_oFPDI->getCurrentLineHeight(), $sLineValue, 0, 0, $aCurrentTableConfig['text']['align']);
					$this->_oFPDI->SetY($this->_oFPDI->GetY()+$this->_oFPDI->getCurrentLineHeight());
				
					// calc needed height for position and table footer
					$iValueY  = $this->_oFPDI->GetY();
					$iValueY += $this->_aPageMargins['bottom'];
					$iValueY += $this->_oFPDI->getCurrentLineHeight() * 3 + $this->_oFPDI->GetLineWidth() * 2;

					if(
						$iValueY >= $this->_oFPDI->getPageHeight() && 
						(bool)$this->_aDisplayElements['tablefoot']
					) {
						$this->_oFPDI->SetStyle("B", 0);
						$this->_writeSubTotal($iLineLength, $iSubSumme);
						$this->_oFPDI->SetStyle("B");
					}

				}
				
				$this->_oFPDI->SetStyle("B", 0);

				if($oInvoiceItem->description) {
					$sProduct = $this->convertUTF8String($oInvoiceItem->description);
					$aDescription = $this->getArrayFromText($sProduct, $aCurrentTableConfig['text']['width']);
					foreach($aDescription['lines'] as $iLineKey => $sLineValue)
					{
						// calc needed height for position and table footer
						$iValueY  = $this->_oFPDI->GetY();
						$iValueY += $this->_aPageMargins['bottom'];
						$iValueY += $this->_oFPDI->getCurrentLineHeight() * 3 + $this->_oFPDI->GetLineWidth() * 2;

						if(
							$iValueY >= $this->_oFPDI->getPageHeight() && 
							(bool)$this->_aDisplayElements['tablefoot']
						) {
							$this->_writeSubTotal($iLineLength, $iSubSumme);
						}

						$this->_oFPDI->SetX($aCurrentTableConfig['text']['x']);
						$this->_oFPDI->Cell($aCurrentTableConfig['text']['width'], $this->_oFPDI->getCurrentLineHeight(), $sLineValue, 0, 0, $aCurrentTableConfig['text']['align']);
						$this->_oFPDI->SetY($this->_oFPDI->GetY()+$this->_oFPDI->getCurrentLineHeight());

					}
				}
			}

			// set spacer after invoice item
			$this->_oFPDI->SetXY($this->_aPageMargins['left'], $this->_oFPDI->GetY()+($this->_oFPDI->getCurrentLineHeight()/4));

			// get end of longest col
			$iNextY = $this->_oFPDI->GetY();

			// page break
			if($iNextY < $iInitialY) {
				$iInitialY = $this->_aPageMargins['top'];
				$this->_writeInvoiceItemsHead($iInitialY - $this->_intTableHeadHeight, $iLineLength, $iSubSumme);
			}
			$iInitialY = $iNextY;
			$this->_oFPDI->SetY($iNextY);
			$iItemsTmpCounter++;

			$iCounter--;
			if($iCounter == 0)
			{
				$iValueY  = $this->_oFPDI->GetY();
				$iValueY += $this->_aPageMargins['bottom'];
				$iValueY += $this->_oFPDI->getCurrentLineHeight() * 3 + $this->_oFPDI->GetLineWidth() * 2 + $this->_oFPDI->getCurrentLineHeight() * 2;

				if(
					$iValueY > $this->_oFPDI->getPageHeight() && 
					(bool)$this->_aDisplayElements['tablefoot']
				) {
					$this->_oFPDI->SetXY($this->_aPageMargins['left'], $this->_oFPDI->GetY()-($this->_oFPDI->getCurrentLineHeight()/4));
					$this->_writeSubTotal($iLineLength, $iSubSumme);
					$bEndOfTable = true;
				}
			}

		}

		// set spacer after invoice items
		$this->_oFPDI->SetXY($this->_aPageMargins['left'], $this->_oFPDI->GetY()+($this->_oFPDI->getCurrentLineHeight()/4));

		// if show table foot
		if(
			!isset($this->_aInvoiceTableConfig['position']['format']) && 
			(bool)$this->_aDisplayElements['tablefoot']
		) {

			$objInvoiceCalculator = new wdPDF_ExtendedDocument_InvoiceCalculator($this);

// { Übertrag, falls nötig >>>
			$iValueY  = $this->_oFPDI->GetY();
			$iValueY += $this->_aPageMargins['bottom'];

			// Netto- und Bruttobetrag (4 Linien + 4 Textzeilen)
			$iValueY += $this->_oFPDI->getCurrentLineHeight() * 4 + $this->_oFPDI->GetLineWidth() * 4;

			$aVatValues = $objInvoiceCalculator->vatValues;

			// MWST-Sätze (1,5 Textzeilen pro Satz)
			foreach($aVatValues as $sKey => $fValue) {
				if (round($fValue, (int)$aCurrentTableConfig['totalamount']['decimal_places']) > 0) {
					$iValueY += $this->_oFPDI->getCurrentLineHeight() * 1.5;
				} else {
					unset($aVatValues[$sKey]);
				}
			}

			if($this->_iInvoiceDiscount > 0)
			{
				$iValueY += $this->_oFPDI->getCurrentLineHeight() * 3 + $this->_oFPDI->GetLineWidth() * 2;
			}

			if($iValueY >= $this->_oFPDI->getPageHeight())
			{
				$this->_writeSubTotal($iLineLength, $iSubSumme);
				$bEndOfTable = true;
				$this->_oFPDI->SetY($this->_oFPDI->GetY()+$this->_oFPDI->GetLineWidth() * 6);
			}
// TODO;
			if($bEndOfTable)
			{
				$this->_oFPDI->SetY($this->_oFPDI->GetY() - $this->_intTableHeadHeight - $this->_oFPDI->GetLineWidth() * 6);
				
				$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($this->_aTranslations['carryover']), 0, 0, 'L');
				$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
				$iTmpXSumme = $this->_writeNumberFormat($iSubSumme);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $iTmpXSumme, 0, 0, 'R');
				$this->_oFPDI->Ln();
				$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
			}
// } Übertrag ENDE <<<

			if($this->_iInvoiceDiscount > 0)
			{
				$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());

				$this->_oFPDI->SetX($this->_aPageMargins['left']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($this->_aTranslations['subtotal']), 0, 0, 'L');
				$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, number_format($objInvoiceCalculator->totalAmountPreTax, 2, ',', '.').' '.$this->convertUTF8String($this->_sCurrency), 0, 0, 'R');

				$this->_oFPDI->Ln();

				$sSum = '-'.$this->_writeNumberFormat($objInvoiceCalculator->totalDiscountAmount);
	
				$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());

				$this->_oFPDI->SetX($this->_aPageMargins['left']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String(sprintf($this->_aTranslations['less_discount'], number_format($this->_iInvoiceDiscount, 4, ',', '.'))), 0, 0, 'L');
				$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $sSum, 0, 0, 'R');

				$this->_oFPDI->Ln();
			}

			if(!empty($aGroupCache)) {
				
				$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
				
				foreach($aGroupCache as $aGroup) {

					$sGroupAmount = $this->_writeNumberFormat($aGroup['amount']);
					$this->_oFPDI->SetX($this->_aPageMargins['left']);
					$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($aGroup['label']), 0, 0, 'L');
					$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
					$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $sGroupAmount, 0, 0, 'R');

					$this->_oFPDI->Ln();
				}
				
			}
			
			if(!empty($aVatValues)) {
				// Netto
				$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());

				if($this->_bHighlightOfferNetPrice == true)
				{
					$this->_oFPDI->SetY($this->_oFPDI->GetY() + $this->_oFPDI->getCurrentLineHeight());
	
					$this->_oFPDI->SetStyle("B");
				}

				$strNet = $this->_writeNumberFormat($objInvoiceCalculator->totalAmountPreTaxAfterDiscount);
				$this->_oFPDI->SetX($this->_aPageMargins['left']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($this->_aTranslations['net_total']), 0, 0, 'L');
				$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $strNet, 0, 0, 'R');
	
				if($this->_bHighlightOfferNetPrice == true)
				{
					$this->_oFPDI->SetStyle("B", 0);
				}
	
				$this->_oFPDI->Ln();
				$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
			}

			// MWST
			foreach($aVatValues as $sKey => $sValue) {//__out($sKey.' '.$sValue);

				$sPreTax = $objInvoiceCalculator->vatAmountsPreTax[$sKey];
				if($this->switchSign == 1 && $sPreTax < 0)
				{
					$sPreTax = $sPreTax * -1;
				}

				$sTax = $objInvoiceCalculator->vatTaxAmounts[$sKey];
				if($this->switchSign == 1 && $sTax < 0)
				{
					$sTax = $sTax * -1;
				}

				$this->_oFPDI->SetX($this->_aPageMargins['left']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['amount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String(sprintf($this->_aTranslations['plus_vat'], number_format($sValue, 2, ',', '.'))), '', 0,'L');
				$this->_oFPDI->SetX($this->_aInvoiceTableConfig['amount']['x']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['amount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, number_format($sPreTax, 2, ',', '.')." ".$this->convertUTF8String($this->_sCurrency), '', 0, 'R');
				$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
				$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, number_format($sTax, 2, ',', '.')." ".$this->convertUTF8String($this->_sCurrency), '', 0, 'R');

				$this->_oFPDI->Ln();

			}
			$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());



			// Brutto
			if($this->_bHighlightOfferNetPrice == false)
			{
				$this->_oFPDI->SetY($this->_oFPDI->GetY() + $this->_oFPDI->getCurrentLineHeight());

				$this->_oFPDI->SetStyle("B");
			}

			$strBrut = $this->_writeNumberFormat($objInvoiceCalculator->totalAmountAfterTax);
			$this->_oFPDI->SetX($this->_aPageMargins['left']);
			$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($this->_aTranslations['total']), 0, 0, 'L');
			$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
			$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $strBrut, 0, 0, 'R');

			if($this->_bHighlightOfferNetPrice == false)
			{
				$this->_oFPDI->SetStyle("B", 0);
			}

			$this->_oFPDI->Ln();
			$this->_oFPDI->SetLineWidth(0.3);
			$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
		}
		else if((bool)$this->_aDisplayElements['tablefoot'])
		{
			$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
			$this->_oFPDI->SetStyle("B");
			$this->_oFPDI->SetX($this->_aPageMargins['left']);

			// Total für Mahnungen
			$this->_oFPDI->SetX($this->_aPageMargins['left']);
			$strBrut = $this->_writeNumberFormat($dTotalReminded);
			$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($this->_aTranslations['total']), 0, 0, 'L');
			$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
			$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $strBrut, 0, 0, 'R');

			$this->_oFPDI->Ln();
			$this->_oFPDI->SetLineWidth(0.3);
			$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());

			$this->_oFPDI->SetStyle("B", 0);
		}

		// reset top margin
		$this->_aPageMargins['top'] -= $this->_intTableHeadHeight;
		$this->_oFPDI->SetTopMargin($this->_aPageMargins['top']); 

	}

	protected function _writeInvoiceItemsHead($iPositionY, $iLineLength, $iSubSumme = 0) {

		// Table-Header
		$this->_oFPDI->SetXY($this->_aPageMargins['left'], $iPositionY);

		$aInvoiceTableHeaderConfig = $this->_aInvoiceTableConfig;
		$aInvoiceTableHeaderConfig['quantity']['width'] += $aInvoiceTableHeaderConfig['unit']['width'];
		unset($aInvoiceTableHeaderConfig['unit']);
		$iPositionX = $this->_aPageMargins['left'];
		foreach((array)$aInvoiceTableHeaderConfig as $aItem)
		{
			$this->_oFPDI->SetXY($iPositionX, $iPositionY);
			if(trim($this->_aDocumentFont['head_bg_color']) != '')
			{
				$this->_oFPDI->SetFillColor(hexdec(substr($this->_aDocumentFont['head_bg_color'], 0, 2)), hexdec(substr($this->_aDocumentFont['head_bg_color'], 2, 2)), hexdec(substr($this->_aDocumentFont['head_bg_color'], 4, 2)));
				$this->_oFPDI->Cell($aItem['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($aItem['title']), 0, 0, 'L', 1);
			}
			else
			{
				$this->_oFPDI->Cell($aItem['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($aItem['title']), 0, 0, 'L', 0);
			}
			$iPositionX += $aItem['width'];
		}
		
		$this->_oFPDI->Line($this->_aPageMargins['left'], $iPositionY, $iLineLength, $iPositionY);

		$this->_oFPDI->Ln();
		$iPositionY = $this->_oFPDI->GetY();
		$this->_oFPDI->Line($this->_aPageMargins['left'], $iPositionY, $iLineLength, $iPositionY);

		if($this->_oFPDI->PageNo() > 1 && $this->_bAllowSubPrices)
		{
			$this->_oFPDI->SetX($this->_aPageMargins['left']);
			$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['x'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $this->convertUTF8String($this->_aTranslations['carryover']), 0, 0, 'L');
			$this->_oFPDI->SetX($this->_aInvoiceTableConfig['totalamount']['x']);
			$iTmpXSumme = number_format($iSubSumme, 2, ',', '.').' '.$this->convertUTF8String($this->_sCurrency);
			$this->_oFPDI->Cell($this->_aInvoiceTableConfig['totalamount']['width'], $this->_oFPDI->getCurrentLineHeight()+$this->_oFPDI->getCurrentLineHeight()/2, $iTmpXSumme, 0, 0, 'R');
			$this->_oFPDI->Ln();
			$this->_oFPDI->Line($this->_aPageMargins['left'], $this->_oFPDI->GetY(), $iLineLength, $this->_oFPDI->GetY());
		}

	}

	/**
	 * Add the foot text to the internal fpdf/fpdi object.
	 *
	 * The text will be added at the currently set y-position.
	 *
	 * @return void
	 */
	protected function _writeFootText() {

		// get the internal fpdf/fpdi object
		$oFPDI = $this->_oFPDI;

		$oFPDI->resetLastH();

		$this->_sFootText = $this->replaceAdditionalPlaceholdersInText($this->_sFootText, 0);

		if(strpos($this->_sFootText, '{ContactSignatureImage}')) {
			if(is_file($this->_aDisplayElements['signature']))
			{
				$arrTemp = explode('{ContactSignatureImage}', $this->_sFootText);
				$this->_sFootText = $arrTemp[0];
				$strFootText2 = $arrTemp[1];
			}
		}

		// write the foot text
		$oFPDI->SetXY($this->_aPageMargins['left'],$oFPDI->GetY()+5);
		$oFPDI->WriteHTML($this->convertUTF8String($this->_sFootText));

		if(isset($strFootText2)) {
			$this->setSignatureImage();
			$oFPDI->SetXY($this->_aPageMargins['left'],$oFPDI->GetY()+15);
			$oFPDI->WriteHTML($this->convertUTF8String($strFootText2));
		}

		$oFPDI->SetY($oFPDI->GetY() + 8);

	}


	/**
	 * Sets signature image in foot text
	 */
	private function setSignatureImage()
	{
		$oFPDI = $this->_oFPDI;

		if($this->_aDisplayElements['signature']) {
			$oFPDI->Image($this->_aDisplayElements['signature'], $oFPDI->GetX(), $oFPDI->GetY(), 60);
		}
	}

	/**
	 * returns array with single lines from text and width of text box
	 * @param	string	text
	 * @param	int	width of text box
	 * @return	array	array with number of lines, lines and wrappen text
	 */
	function getArrayFromText($strText,$intWidth) {

		$arrBox[2] = $this->_oFPDI->GetStringWidth($strText);

		$intBoxSpace = $this->_oFPDI->GetStringWidth(" ");

		// Text mehrzeilig?
		$bolMulti = 0;
		if(strpos($strText,"\n") !== false) {
			$bolMulti = 1;
		}

		$intTotal = 0;
		if($bolMulti || $arrBox[2] > $intWidth) {
			$arrWidth = array();
			$strText = preg_replace("/(\r\n|\n|\r)/", "\n", $strText);
			$arrLines = explode("\n",$strText);
			foreach((array)$arrLines as $strLine) {
				$arrText = explode(" ",trim($strLine));
				foreach($arrText as $v) {
					$arrBox[2] = $this->_oFPDI->GetStringWidth($v);
					// if word is longer than textbox, cut word.	
					while($arrBox[2] > $intWidth) {
						$v = substr($v, 0, -1);
						$arrBox[2] = $this->_oFPDI->GetStringWidth($v);
					}
					$arrWidth[] = array($v,$arrBox[2]);
					$intTotal++;
				}
				$arrWidth[] = array("\n",0);
			}

			array_pop($arrWidth);

			$arrRows = array();

			$c=0;
			$i=0;
			while(isset($arrWidth[$c])) {
				$intRowWidth = 0;
				do {
					if($arrWidth[$c][0] == "\n") {
						$c++;
						$bolNewline = 1;
						$i--;
						break;
					}
					$arrRows[$i][] = $arrWidth[$c][0];
					$intRowWidth += $arrWidth[$c][1]+$intBoxSpace;
					$c++;
				} while($arrWidth[$c][1] > 0 && ($intRowWidth+$arrWidth[$c][1]) <= $intWidth);
				if($bolNewline) {
					$bolNewline = 0;
					$i++;
					continue;
				}
				$arrLines[$i] = implode(" ",$arrRows[$i]);
				$i++;
			}
			$aResult['text'] = implode("\n",$arrLines);
			$aResult['lines'] = $arrLines;
		} else {
			$aResult['text'] = $strText;
			$aResult['lines'] = array($strText);
		}

		$aResult['count'] = count($aResult['lines']);

		return $aResult;

	}

}
