<?php


/*
 * -- webDynamics pdf classes --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/wdpdf/wdpdf.php
 *
 * 
 */


class wdPDF_TCPDF extends \Pdf\Service\Fpdi {

	
	
	protected $aFontSize = array(
								'default' => 12,
								'H1' => 28,
								'H2' => 24,
								'H3' => 18,
								'H4' => 14,
								'H5' => 12,
								'H6' => 9,
								'H7' => 8,
	);

	/**
	 * ...
	 *
	 * Taken from the webDyanmics office extension.
	 *
	 * @var string
	 */
	protected $B = 0;


	/**
	 * ...
	 *
	 * Taken from the webDyanmics office extension.
	 *
	 * @var string
	 */
	protected $I = 0;


	/**
	 * ...
	 *
	 * Taken from the webDyanmics office extension.
	 *
	 * @var string
	 */
	protected $U = 0;


	/**
	 * ...
	 *
	 * Taken from the webDyanmics office extension.
	 *
	 * @var string
	 */
	protected $HREF = '';
	protected $Align = '';
	
	protected $iWriteHTMLX = 0;

	/**
	 * The standard fonts
	 */
	protected $_aFontsChecklist = array(
		'courier', 'helvetica', 'times', 'symbol', 'zapfdingbats'
	);


	/**
	 * The webDynamics pdf object this instance is assigned to.
	 *
	 * @var wdPDF
	 */
	protected $_oWDPDF = null;

	protected $_aFonts = array();
	
	public $fLineHeightFactor = 1;

	public function __construct(wdPDF $oWDPDF, $sOrientation = 'P', $sUnit = 'mm', $sFormat = 'A4') {

		// call parent constructor
		parent::__construct($sOrientation, $sUnit, $sFormat);

		// store the webDynamics pdf object		
		$this->_oWDPDF = $oWDPDF;

	}

	public final function setDefaultFontSize($iSize){
		$this->aFontSize['default'] = (int) $iSize;
	}
	
	protected function _replacePlaceholdersInText($sText) {

		// convert the passed arguments
		$sText = (string)$sText;

		// get the wdPDF object
		$objWDPDF = $this->_oWDPDF;

		// replace default placeholders
		$sText = str_replace('{DocumentCurrentDate}', strftime("%x", time()), $sText);
		$sText = str_replace('{DocumentCurrentDateTime}', strftime("%x %X", time()), $sText);

		$sText = str_replace('{DocumentPagesCurrent}', $this->getAliasNumPage(), $sText);
		$sText = str_replace('{DocumentPagesTotal}', $this->getAliasNbPages(), $sText);	

		// call wdPDF method to replace additional placeholders
		$sText  = $objWDPDF->replaceAdditionalPlaceholdersInText($sText);

		// return the modified input string
		return $sText;

	}

	public function addFonts($aFonts) {

		foreach((array)$aFonts as $aFont) {
			if(!in_array(strtolower($aFont['file']), $this->_aFontsChecklist))
			{
				$this->AddFont($aFont['file'], $aFont['style']);
				$strFontKey = strtolower($aFont['file'].$aFont['style']);
				$this->_aFonts[$strFontKey] = $aFont;
			}
		}

	}	

	protected function _setSpecifiedFont($sFont, $iSize, $sStyle="") {

		// convert the passed arguments
		$sFont = (string)$sFont;
		$iSize = (int)$iSize;

		// set the specified font
		switch ($sFont) {
			case 'courier':
				$this->SetFont('courier', '', $iSize);
				break;
			case 'helveticab':
				$this->SetFont('helvetica', 'B', $iSize);
				break;
			case 'helveticabi':
				$this->SetFont('helvetica', 'BI', $iSize);
				break;
			case 'helveticai':
				$this->SetFont('helvetica', 'I', $iSize);
				break;
			case 'symbol':
				$this->SetFont('symbol', '', $iSize);
				break;
			case 'times':
				$this->SetFont('times', '', $iSize);
				break;
			case 'timesb':
				$this->SetFont('times', 'B', $iSize);
				break;
			case 'timesbi':
				$this->SetFont('times', 'BI', $iSize);
				break;
			case 'timesi':
				$this->SetFont('times', 'I', $iSize);
				break;
			case 'zapfdingbats':
				$this->SetFont('zapfdingbats', '', $iSize);
				break;
			case 'helvetica':
				$this->SetFont('helvetica', '', $iSize);
				break;
			default:
				$strFontKey = strtolower($sFont.$sStyle);
				if(isset($this->_aFonts[$strFontKey])) {
					$this->SetFont($sFont, $sStyle, $iSize);
				}
				break;
		}

	}

	public function setWDFont($sFont, $iSize, $sStyle="") {

		// convert the passed arguments
		$sFont = (string)$sFont;
		$iSize = (int)$iSize;

		// adjust the font size if required
		if ($iSize < 1) {
			$iSize = 12;
		}

		// set the specified font
		$this->_setSpecifiedFont($sFont, $iSize, $sStyle);

	}



	public function Header() {

		// call parent header method
		//parent::Header();

		// get the wdPDf object
		$oWDPDF		= $this->_oWDPDF;

		// process document templates for wdPDF_ExtendedDocument
		if ($oWDPDF instanceof wdPDF_ExtendedDocument) {

			$aTemplates = $oWDPDF->documentTemplates;
			$iPage		= $this->PageNo();

			$iTemplateId = 0;
			if ($iPage < 2) {
				$strTemplateFile = $aTemplates['first'];
			} else {
				$strTemplateFile = $aTemplates['following'];
			}
			if (is_file($strTemplateFile)) {
				$iPageCount = $this->setSourceFile($strTemplateFile);
				if ($iPageCount) {
					$iTemplateId = $this->importPage(1);
					if ($iTemplateId) {
						$this->useTemplate($iTemplateId);
					}
				}
			}

		}

		// call the header method of the webDynamics pdf object
		$oWDPDF->runAdditionalHeader();

	}


	public function Footer() {

		// call parent footer method
		//parent::Footer();

		// add all document items to the current page
		$oWDPDF         = $this->_oWDPDF;
		$aDocumentItems = $oWDPDF->documentItems;

		$iPage          = $this->PageNo();
		foreach($aDocumentItems as $aItem) {

			// skip this entry if it must not be displayed on
			// the current page
			if (
				(
					$aItem['display'] == 'FIRST' && $iPage > 1
				) || (
					$aItem['display'] == 'FOLLOWING' && $iPage < 2
				)
			) {
				continue;
			}

			// set the specified font
			$this->_setSpecifiedFont($aItem['font'], $aItem['font_size'], $aItem['font_style']);
			$this->SetTextColor(hexdec(substr($aItem['font_color'], 0, 2)), hexdec(substr($aItem['font_color'], 2, 2)), hexdec(substr($aItem['font_color'], 4, 2)));

			// set the specified position
			$this->SetXY(
				$aItem['x'],
				$aItem['y']
			);

			// calc line height
			$intLineHeight = $aItem['font_size'] * 0.44;

			// replace placeholders
			$aItem['content'] = $this->_replacePlaceholdersInText($aItem['content']);

			// add the specified data
			$this->MultiCell(
				$aItem['width'],
				$this->getCurrentLineHeight(),
				$aItem['content'],
				0,
				$aItem['alignment'],
				0,
				1,
				'',
				'',
				true,
				0,
				true
			);

		}

		// call the footer method of the webDynamics pdf object
		$oWDPDF->runAdditionalFooter();

	}

	
	public function getCurrentLineHeight() {
		$intLineHeight = $this->getFontSizePt() * 0.44 * $this->fLineHeightFactor;
		return $intLineHeight;
	}
	
	function _putinfo()	{
		$this->producer = 'WDPDF by Fidelo Software GmbH';
		return parent::_putinfo();
	}

	// prevent the object from being serialized or cloned
	private function __sleep() {}
	private function __wakeup() {}
	private function __clone() {}

	/**
	 * ...
	 *
	 * Taken from the webDyanmics office extension.
	 *
	 * @param string $tag
	 * @param boolean $enable
	 * @return void
	 */
	public function SetStyle($tag, $enable=1) {
		// modify style and select corresponding font
		$this->$tag += ($enable ? 1 : -1);
		$style = '';
		foreach (array('B', 'I', 'U') as $s) {
			if ($this -> $s > 0) {
				$style .= $s;
			}
		}

		$this->SetFont($this->FontFamily, $style);
	}

}
