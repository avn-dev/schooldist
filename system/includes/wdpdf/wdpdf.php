<?php


/*
 * -- webDynamics pdf classes --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Depends on:
 * - fpdi/fpdf library classes (/system/includes/fpdi/)
 *
 * 
 */

// define fpdf font path (this is required to generate pdf files)
if (!defined('K_PATH_FONTS')) {
	$sFontDir = \Util::getDocumentRoot().'system/bundles/Pdf/Resources/fonts/';
	if(file_exists($sFontDir)) {
		define('K_PATH_FONTS', $sFontDir);
	}
}
if (!defined('FPDF_FONTPATH')) {
	if(defined('K_PATH_FONTS')) {
		define('FPDF_FONTPATH', K_PATH_FONTS);
	} else {
		define('FPDF_FONTPATH', realpath(dirname(__FILE__).'/../').'/fpdi/font/');
	}
}

// include wdpdf files
include_once(realpath(dirname(__FILE__)).'/wdpdf_fpdi.php');
include_once(realpath(dirname(__FILE__)).'/wdpdf_document.php');
include_once(realpath(dirname(__FILE__)).'/wdpdf_extendeddocument.php');
include_once(realpath(dirname(__FILE__)).'/wdpdf_extendeddocument_invoiceitem.php');
include_once(realpath(dirname(__FILE__)).'/wdpdf.extendeddocument_invoicecalculator.php');


/**
 * webDynamics PDF object.
 *
 * The following variable can be accessed using the magic
 * get and set methods:
 * - (wdPDF_FPDI) fpdf [read only]
 *   The internal fpdf/fpdi object (same as fpdi)
 * - (wdPDF_FPDI) fpdi [read only]
 *   The internal fpdf/fpdi object (same as fpdf)
 * - (array) documentItems [read only]
 *   The list of document items (same as blockList)
 * - (array) blockList [read only]
 *   The list of document items (same as documentItems)
 * - (array) variables [read only]
 *   The list of specified variables.
 * - (string) itemFont [read/write]
 *   The default font for document items (helvetica).
 * - (integer) itemFontSize [read/write]
 *   The default font size for document items (8).
 * - (string) itemDisplay [read/write]
 *   The default display setting for document items (FIRST).
 * - (string) itemAlignment [read/write]
 *   The default alignment setting for document items (L).
 * - (boolean) utf8convert [read/write]
 *   Convert input strings from utf-8 to cp1252 (true).
 */
class wdPDF {


	/**
	 * Convert input text from utf-8 tp cp1252 by default.
	 *
	 * @var boolean
	 */
	protected $_bUTF8Convert = true;

	/**
	 * The internal fpdf/fpdi object.
	 *
	 * @var wdPDF_FPDI
	 */
	protected $_oFPDI = null;


	/**
	 * The list of document items.
	 *
	 * Text block items that are show on various
	 * pages of the pdf file.
	 *
	 * Each entry will have the following format:
	 * array(
	 *     'x'         => <integer>,
	 *     'y'         => <integer>,
     *     'width'     => <integer>,
	 *     'size'      => <integer>,
	 *     'font'      => <string>,
	 *     'alignment' => <string>,
	 *     'display'   => <string>,
	 *     'content'   => <string>
	 * )
	 *
	 * @var array 
	 */
	protected $_aDocumentItems = array();


	/**
	 * The list of specified variables.
	 *
	 * @var array
	 */
	protected $_aVariables = array();


	/**
	 * The default font for document items.
	 *
	 * @var string
	 */
	protected $_sItemFont = 'helvetica';
	
	
	/**
	 * The default font color for document items.
	 *
	 * @var string
	 */
	protected $_sItemFontColor = '000000';


	/**
	 * The default font size for document items.
	 *
	 * @var integer
	 */
	protected $_iItemFontSize = 8;

	/**
	 * The default font style for document items.
	 *
	 * @var integer
	 */
	protected $_sItemFontStyle = "";


	/**
	 * The default display setting for document items.
	 *
	 * @var string
	 */
	protected $_sItemDisplay = 'FIRST';


	/**
	 * The default alignment setting for document items.
	 *
	 * @var string
	 */
	protected $_sItemAlignment = 'L';


	/**
	 * Orientation of the internal fpdf/fpdi object.
	 *
	 * @var string
	 */
	protected $_sPDFOrientation = 'P';


	/**
	 * Unit of the internal fpdf/fpdi object.
	 *
	 * @var string
	 */
	protected $_sPDFUnit = 'mm';


	/**
	 * Format of the internal fpdf/fpdi object.
	 *
	 * @var string
	 */
	protected $_sPDFFormat = 'A4';

	protected $_aPageMargins = array('left' => 25, 'top' => 20, 'right' => 15, 'bottom' => 20);

	/**
	 * Set object properties.
	 *
	 * The following properties can be set:
	 * - itemFont
	 * - itemFontSize
	 * - itemDisplay
	 * - itemAlignment
	 * - utf8convert
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

			// default document item font
			case 'itemFont':
				$sItemFont        = (string)$mValue;
				$sItemFont        = $this->_verifyDocumentItemFont($sItemFont);
				$this->_sItemFont = $sItemFont;
				return;
			
			// default document item font color
			case 'itemFontColor':
				$sItemFontColor        = (string)$mValue;
				$this->_sItemFontColor = $sItemFontColor;
				return;

			// default document item font size
			case 'itemFontSize':
				$iItemFontSize        = (int)$mValue;
				$this->_iItemFontSize = $iItemFontSize;
				return;

			// default document item display setting
			case 'itemDisplay':
				$sItemDisplay        = (string)$mValue;
				$sItemDisplay        = $this->_verifyDocumentItemDisplay($sItemDisplay);
				$this->_sItemDisplay = $sItemDisplay;
				return;

			// default document item alignment setting
			case 'itemAlignment':
				$sItemAlignment        = (string)$mValue;
				$sItemAlignment        = $this->_verifyDocumentItemAlignment($sItemAlignment);
				$this->_sItemAlignment = $sItemAlignment;
				return;

			// convert input string from utf-8 to cp1252
			case 'utf8convert':
				$bUTF8Convert        = (bool)$mValue;
				$this->_bUTF8Convert = $bUTF8Convert;
				return;

			// set page margin
			case 'margin':
				$aPageMargin        = (array)$mValue;
				$this->_aPageMargins = $aPageMargin;
				$this->_oFPDI->SetMargins($this->_aPageMargins['left'], $this->_aPageMargins['top'], $this->_aPageMargins['right']);
				$this->_oFPDI->SetAutoPageBreak(true, $this->_aPageMargins['bottom']);
				return;

		}

		// the specified property was not found
		throw new Exception('Unable to set property "'.$sName.'".');

	}


	/**
	 * Get object properties.
	 *
	 * The following properties can be read:
	 * - fpdf
	 * - fpdi
	 * - documentItems
	 * - blockList
	 * - variables
	 * - itemFont
	 * - itemFontSize
	 * - itemDisplay
	 * - itemAlignment
	 * - utf8convert
	 *
	 * @param string $sName
	 * @return mixed
	 */
	public function __get($sName) {

		// convert the passed arguments
		$sName = (string)$sName;

		// return the specified value
		switch ($sName) {

			// fpdf/pdfi object
			case 'fpdf':
			case 'fpdi':
				$oReturn = $this->_oFPDI;
				return $oReturn;

			// list of document items
			case 'documentItems':
			case 'blockList':
				$aReturn = (array)$this->_aDocumentItems;
				return $aReturn;

			// list of variables
			case 'variables':
				$aReturn = (array)$this->_aVariables;
				return $aReturn;

			// default document item font
			case 'itemFont':
				$sReturn = (string)$this->_sItemFont;
				return $sReturn;
			
			// default document item font color
			case 'itemFontColor':
				$sReturn = (string)$this->_sItemFontColor;
				return $sReturn;

			// default document item font size
			case 'itemFontSize':
				$iReturn = (int)$this->_iItemFontSize;
				return $iReturn;

			// default document item display setting
			case 'itemDisplay':
				$sReturn = (string)$this->_sItemDisplay;
				return $sReturn;

			// default document item alignment setting
			case 'itemAlignment':
				$sReturn = (string)$this->_sItemAlignment;
				return $sReturn;

			// convert input string from utf-8 to cp1252
			case 'utf8convert':
				$bReturn = (bool)$this->_bUTF8Convert;
				return $bReturn;

		}

		// the specified property was not found
		throw new Exception('Unable to get property "'.$sName.'".');

	}


	/**
	 * Constructor.
	 *
	 * The following config values will be processed:
	 * - (string) orientation
	 * - (string) unit
	 * - (string) format
	 * - (bool) convert_utf8
	 *
	 * The following data values will be processed:
	 * - (array) document_items
	 *
	 * @param array $aConfig
	 * @param array $aData
	 * @return void
	 */
	public function __construct(array $aConfig = array(), array $aData = array()) {

		// process the pdf configuration array
		$this->_processConfigData($aConfig);

		// create the internal fpdf/fpdi object
		$this->_initializePDFObject();

		// process the pdf data array (document items)
		$this->_processPDFData($aData);

	}


	/**
	 * Process configuration array.
	 *
	 * Internal constructor helper method.
	 *
	 * The following config values will be processed:
	 * - (bool) convert_utf8
	 * - (string) orientation
	 * - (string) unit
	 * - (string) format
	 *
	 * @param array $aConfig
	 * @return void
	 */
	protected function _processConfigData(array $aConfig) {

		// config argument: convert_utf8
		if (array_key_exists('convert_utf8', $aConfig)) {
			$this->_bUTF8Convert = (bool)$aConfig['convert_utf8'];
		}

		// config argument: orientation
		if (array_key_exists('orientation', $aConfig)) {
			$this->_sPDFOrientation = (string)$aConfig['orientation'];
		}

		// config argument: unit
		if (array_key_exists('unit', $aConfig)) {
			$this->_sPDFUnit = (string)$aConfig['unit'];
		}

		// config argument: format
		if (array_key_exists('format', $aConfig)) {
			$this->_sPDFFormat = $aConfig['format'];
		}

	}


	/**
	 * Process data array.
	 *
	 * Internal constructor helper method.
	 *
	 * The following data values will be processed:
	 * - (array) document_items
	 *
	 * @param array $aData
	 * @return void
	 */
	protected function _processPDFData(array $aData) {

		// data value: document_items
		if (array_key_exists('document_items', $aData)) {
			$this->addDocumentItems((array)$aData['document_items']);
		}

	}


	/**
	 * Initialize internal fpdf/fpdi object.
	 *
	 * Internal constructor helper method, also used when
	 * unserializing the object instance.
	 *
	 * @return void
	 */
	protected function _initializePDFObject() {

		$this->_oFPDI = new wdPDF_TCPDF($this, $this->_sPDFOrientation, $this->_sPDFUnit, $this->_sPDFFormat);
		$this->_oFPDI->SetDisplayMode('fullpage', 'SinglePage');

		$this->_oFPDI->SetMargins($this->_aPageMargins['left'], $this->_aPageMargins['top'], $this->_aPageMargins['right']);
		$this->_oFPDI->SetAutoPageBreak(true, $this->_aPageMargins['bottom']);
	}

	public function addDocumentItems(array $aItems) {

		// process all specified document items
		foreach ($aItems as $aItem) {

			// the document item must be an array
			if (!is_array($aItem)) {
				continue;
			}

			// add the document item
			$this->_addDocumentItem($aItem);

		}

	}

	/**
	 * Updates a single value of a document item.
	 *
	 * @param string $sIdentifier
	 * @param string $sKey
	 * @param string $sValue
	 * @return void
	 */
	public function updateDocumentItem($sIdentifier, $sKey, $sValue) {
		if (array_key_exists($sIdentifier, $this->_aDocumentItems)) {
			$this->_aDocumentItems[$sIdentifier][$sKey] = $sValue;
		}
	}

	public function addDocumentItem(array $aItem, $sIdentifier = "") {
		$this->_addDocumentItem($aItem, $sIdentifier);
	}


	protected function _addDocumentItem(array $aItem, $sIdentifier = "") {

		// initialize variables
		$iPositionX = 0;
		$iPositionY = 0;
		$iWidth     = 0;
		$iFontSize  = $this->_iItemFontSize;
		$sFontStyle = $this->_sItemFontStyle;
		$sFontName  = $this->_sItemFont;
		$sFontColor  = $this->_sItemFontColor;
		$sAlignment = $this->_sItemAlignment;
		$sDisplay   = $this->_sItemDisplay;
		$sContent   = '';

		// get configuation data from the item array
		if (array_key_exists('x', $aItem)) {
			$iPositionX = (int)$aItem['x'];
		}
		if (array_key_exists('y', $aItem)) {
			$iPositionY = (int)$aItem['y'];
		}
		if (array_key_exists('width', $aItem) && (int)$aItem['width'] > 0) {
			$iWidth = (int)$aItem['width'];
		}
		if (array_key_exists('font_size', $aItem) && (int)$aItem['font_size'] > 0) {
			$iFontSize = (int)$aItem['font_size'];
		}
		if (array_key_exists('font_style', $aItem)) {
			$sFontStyle = (string)$aItem['font_style'];
		}
		if (array_key_exists('font', $aItem)) {
			$sFontName = (string)$aItem['font'];
		}
		if (array_key_exists('font_color', $aItem)) {
			$sFontColor = (string)$aItem['font_color'];
		}
		if (array_key_exists('display', $aItem)) {
			$sDisplay = $this->_verifyDocumentItemDisplay($aItem['display']);
		}
		if (array_key_exists('alignment', $aItem)) {
			$sAlignment = $this->_verifyDocumentItemAlignment($aItem['alignment']);
		}
		if (array_key_exists('content', $aItem)) {
			$sContent = $this->convertUTF8String($aItem['content']);
		}

		// add the document item to the internal list, the items will be
		// added to the pdf later by the internal fpdf/fpdi object
		if (
			$iFontSize > 0 &&
			strlen($sFontName) > 0 &&
			strlen($sContent) > 0
		) {

			// create a new data array
			$aNewItem = array(
				'x'         => $iPositionX,
				'y'         => $iPositionY,
				'width'     => $iWidth, // > 0
				'font_size' => $iFontSize, // 8 || > 0
				'font_style'=> $sFontStyle, // 8 || > 0
				'font'      => $sFontName, // 'helvetica' || strlen() > 0
				'font_color'=> $sFontColor,
				'alignment' => $sAlignment, // 'L' || 'L', 'C', 'R', 'J'
				'display'   => $sDisplay, // 'FIRST' || 'FIRST', 'FOLLOWING', 'BOTH'
				'content'   => $sContent // strlen() > 0
			);

			// add the data array to the list of document items
			if (empty($sIdentifier)) {
				$this->_aDocumentItems[] = $aNewItem;
			} else {
				$this->_aDocumentItems[$sIdentifier] = $aNewItem;
			}

		}

	}


	protected function _verifyDocumentItemFont($sFont) {

		// convert the passed argument
		$sFont = strtolower((string)$sFont);

		// validate the specified value
		switch ($sFont) {
			case 'courier':
			case 'helvetica':
			case 'helveticab':
			case 'helveticabi':
			case 'helveticai':
			case 'symbol':
			case 'times':
			case 'timesb':
			case 'timesbi':
			case 'timesi':
			case 'zapfdingbats':
				break;
			default:
				$sFont = 'helvetica';
		}

		// return the validated value
		return $sFont;

	}


	protected function _verifyDocumentItemDisplay($sDisplay) {

		// convert the passed argument
		$sDisplay = strtoupper((string)$sDisplay);

		// validate the specified value
		switch ($sDisplay) {
			case 'FIRST':
			case 'FOLLOWING':
			case 'BOTH':
				break;
			default:
				$sDisplay = 'FIRST';
		}

		// return the validated value
		return $sDisplay;

	}


	protected function _verifyDocumentItemAlignment($sAlignment) {

		// convert the passed argument
		$sAlignment = strtoupper((string)$sAlignment);

		// validate the specified value
		switch ($sAlignment) {
			case 'L':
			case 'C':
			case 'R':
			case 'J':
				break;
			default:
				$sAlignment = 'L';
		}

		// return the validated value
		return $sAlignment;

	}


	/**
	 * Convert the specified string.
	 *
	 * Convert the specified string from utf-8 to cp1252
	 * if the utf8convert flag is set.
	 *
	 * @param string $sString
	 * @return string
	 */
	public function convertUTF8String($sString, $bForceConvert = false) {

		// convert the passed arguments
		$sString       = (string)$sString;

		// return the string
		return $sString;

	}


	/**
	 * Return the content of the pdf file.
	 *
	 * @return string
	 */
	public function getPDFString() {
		return $this->_oFPDI->Output('', 'S');
	}


	/**
	 * Send the content of the pdf and show it in the web browser.
	 *
	 * The method will stop the script execution.
	 *
	 * @param string $sFilename
	 * @param boolean $bClearOutputBuffer
	 * @return void
	 */
	public function showPDFFile($sFilename = '', $bClearOutputBuffer = false) {

		// convert the passed arguments
		$sFilename          = (string)$sFilename;
		$bClearOutputBuffer = (bool)$bClearOutputBuffer;

		// there must be a filename
		if (strlen($sFilename) < 1) {
			$sFilename = 'document.pdf';
		}

		// clear output buffers
		if ($bClearOutputBuffer == true) {
			while (ob_get_level() > 0) {
				ob_end_clean();
			}
		}

		return $this->_oFPDI->Output($sFilename, 'I');
		die();

		// get the contents of the pdf file
		$sPDFString = $this->getPDFString();
		
		// send header
		header('Content-Type: application/pdf');
		header('Cache-Control: maxage=3600');
		header('Content-Disposition: inline; filename='.$sFilename);
		header('Content-Length: '.$this->_strBytes($sPDFString));
		header('Pragma: public');

		// send data
		echo $sPDFString;

		// stop the script execution
		die(); 

	}


	/**
	 * Send the content of the pdf and send it as download to the web browser.
	 *
	 * The method will stop the script execution.
	 *
	 * @param string $sFilename
	 * @param boolean $bClearOutputBuffer
	 * @return void
	 */
	public function sendPDFFile($sFilename = '', $bClearOutputBuffer = false) {

		// convert the passed arguments
		$sFilename          = (string)$sFilename;
		$bClearOutputBuffer = (bool)$bClearOutputBuffer;

		// there must be a filename
		if (strlen($sFilename) < 1) {
			$sFilename = 'document.pdf';
		}

		// clear output buffers
		if ($bClearOutputBuffer == true) {
			while (ob_get_level() > 0) {
				ob_end_clean();
			}
		}

		// get the contents of the pdf file
		$sPDFString = $this->getPDFString();

		// send header
		header('Content-Type: application/force-download');
		header('Cache-Control: maxage=3600');
		header('Content-Disposition: attachment; filename='.$sFilename);
		header('Content-Length: '.$this->_strBytes($sPDFString));
		header('Pragma: public');

		// send data
		echo $sPDFString;

		// stop the script execution
		die();

	}


	/**
	 * saves the pdf file.
	 *
	 * @return bool
	 */
	public function savePDFFile($strFilePath) {
		// get the contents of the pdf file
		$this->getPDFString();
		return $this->_oFPDI->Output($strFilePath, 'F');
	}

	/**
	 * Count the number of bytes in a string.
	 *
	 * @param string $str
	 * @return integer
	 */
	protected function _strBytes($str) {
		// STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT

		// Number of characters in string
		$strlen_var = strlen($str);

		// string bytes counter
		$d = 0;

		/*
		 * Iterate over every character in the string,
		 * escaping with a slash or encoding to UTF-8 where necessary
		 */
		for ($c = 0; $c < $strlen_var; ++$c) {

			$ord_var_c = ord($str[$d]);

			switch (true) {
				case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
					// characters U-00000000 - U-0000007F (same as ASCII)
					$d++;
					break;
				case (($ord_var_c & 0xE0) == 0xC0):
					// characters U-00000080 - U-000007FF, mask 110XXXXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d+=2;
					break;
				case (($ord_var_c & 0xF0) == 0xE0):
					// characters U-00000800 - U-0000FFFF, mask 1110XXXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d+=3;
					break;
				case (($ord_var_c & 0xF8) == 0xF0):
					// characters U-00010000 - U-001FFFFF, mask 11110XXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d+=4;
					break;
				case (($ord_var_c & 0xFC) == 0xF8):
					// characters U-00200000 - U-03FFFFFF, mask 111110XX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d+=5;
					break;
				case (($ord_var_c & 0xFE) == 0xFC):
					// characters U-04000000 - U-7FFFFFFF, mask 1111110X
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d+=6;
					break;
				default:
					$d++;   
			}
		}

		return $d;
	}


	/**
	 * Hook method that will be called automatically.
	 *
	 * The method will be called after the page was added to the document,
	 * all operations will belong to the new page.
	 *
	 * The method can be overridden by extending classes to
	 * add further functionality, but these classes should call
	 * the parent method in any case.
	 *
	 * @return void
	 */
	public function runAdditionalHeader() {
		// do nothing by default
	}


	/**
	 * Hook method that will be called automatically.
	 *
	 * The method will be called before the page will be added to the document,
	 * all operations will belong to the old page.
	 *
	 * The method can be overridden by extending classes to
	 * add further functionality, but these classes should call
	 * the parent method in any case.
	 *
	 * @return void
	 */
	public function runAdditionalFooter() {
		// do nothing by default
	}


	/**
	 * Hook method that will be called automatically.
	 *
	 * The method will be called for each document item that is added
	 * to the document and will replace specified variables that
	 * are assigned to the document.
	 *
	 * The method can be overridden by extending classes to
	 * add further functionality, but these classes should call
	 * the parent method in any case.
	 *
	 * @param string $sText
	 * @return void
	 */
	public function replaceAdditionalPlaceholdersInText($sText, $bUTF8Convert = 1) {

		// convert the passed arguments
		$sText = (string)$sText;

		// replace all specified variables in the input text
		foreach((array)$this->_aVariables as $sKey => $sValue) {

			// convert the array data
			$sKey   = (string)$sKey;
			$sValue = (string)trim($sValue);

			// convert the text to cp1252 if required
			if($bUTF8Convert) {
				$sValue = $this->convertUTF8String($sValue);
			}

			// check if
			if(($iPos = strpos($sText, '{if '.$sKey.'}')) !== false) {
				if(empty($sValue)) {
					$iEndPos = strpos($sText, '{/if}', $iPos);
					$sText = substr($sText, 0, $iPos).substr($sText, $iEndPos + 5);
				} else {
					$iEndPos = strpos($sText, '{/if}', $iPos);
					$sText = substr($sText, 0, $iEndPos).substr($sText, $iEndPos + 5);
					$iLen = strlen('{if '.$sKey.'}');
					$sText = substr($sText, 0, $iPos).substr($sText, $iPos + $iLen);
				}
			}

			// check document date
			if($sKey == 'DocumentDate')
			{
				// get placeholder and format string
				$aMatches = array();
				$bMatch = preg_match_all('/\{DocumentDate\|(.*?)\}/', $sText, $aMatches);

				if($bMatch) {
					foreach((array)$aMatches[1] as $iKey => $sFormat) {
						// proof of errors
						if(empty($sFormat) || trim($sFormat) == '') {
							$sFormat = '%x';
						}

						// replace
						$sText = str_replace($aMatches[0][$iKey], strftime($sFormat, $sValue), $sText);
					}
				} else {
					if(is_numeric($sValue)) {
						$sText  = str_replace('{'.$sKey.'}', strftime('%x', $sValue), $sText);
					} else {
						$sText  = str_replace('{'.$sKey.'}', $sValue, $sText);		
					}
				}

			}
//			elseif($sKey == 'DocumentCurrency')
//			{
//				__pout($sText);
//				__pout($this->_aVariables);
//			}
			else
			{
				// replace the current variable
				$sText  = str_replace('{'.$sKey.'}', $sValue, $sText);
			}
		}

		// return the modified input text
		return $sText;

	}


	/**
	 * Add variables to the pdf file.
	 *
	 * The variables will be replaced in text elements automatically.
	 *
	 * Already defined variables will be replaced with the
	 * specified value.
	 *
	 * @param array $aVariables
	 * @return void
	 */
	public function addVariables(array $aVariables) {

		// process all specified variables
		foreach ($aVariables as $sKey => $mValue) {
			$this->_aVariables[(string)$sKey] = (string)$mValue;
		}

	}


	/**
	 * Set variables to the pdf file.
	 *
	 * The variables will be replaced in text elements automatically.
	 *
	 * All already specified variables will be reset.
	 *
	 * @param array $aVariables
	 * @return void
	 */
	public function setVariables(array $aVariables) {

		// unset the current variables
		$this->_aVariables = array();

		// set the spcified variables
		$this->addVariables($aVariables);

	}


	/**
	 * Hook method that will be called automatically.
	 *
	 * Used by php function: serialize()
	 *
	 * @return array
	 */
	public function __sleep() {
		return array(
			'_bUTF8Convert',
			'_aDocumentItems',
			'_aVariables',
			'_sItemFont',
			'_iItemFontSize',
			'_sItemDisplay',
			'_sItemAlignment',
			'_sPDFOrientation',
			'_sPDFUnit',
			'_sPDFFormat'
		);
	}


	/**
	 * Hook method that will be called automatically.
	 *
	 * Used by php function: unserialize()
	 *
	 * @return void
	 */
	public function __wakeup() {

		// create a new fpdf/fpdi object, the object was not
		// stored on serialization
		$this->_initializePDFObject();

	}


}
