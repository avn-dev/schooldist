<?php

class Ext_Thebing_Pdf_Layout extends Ext_Thebing_Pdf_Fpdi {

	// The layout object
	protected $_oLayout;

	// The school object
	protected $_oSchool;

	// The backgrounds
	protected $_aBackgrounds = array();


	/**
	 * The constructor
	 *
	 * @param int $iLayoutId
	 * @param int $iSchoolId
	 */
	public function __construct($iLayoutId, $iSchoolId = null) {
		global $user_data;

		if(is_null($iSchoolId)) {
			$iSchoolId = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$this->_oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		$oUser = Ext_Thebing_User::getInstance($user_data['id']);

		$this->_oLayout = new Ext_Thebing_Pdf_Template_Type($iLayoutId);

		$aFormat[0] = $this->_oLayout->page_format_width;
		$aFormat[1] = $this->_oLayout->page_format_height;

		if($aFormat[0] > $aFormat[1]) {
			$sOrientation = 'L';
		} else {
			$sOrientation = 'P';
		}

		parent::__construct($sOrientation, 'mm', $aFormat, true, 'UTF-8', false);

		// Set document information
		$this->SetCreator($this->_oSchool->ext_1);
		$this->SetAuthor($oUser->name);

		$this->setPrintHeader(true);
		$this->setPrintFooter(true);

		$iDefaultFontSize = $this->_oLayout->font_size;
		$iDefaultFontId = $this->_oLayout->font_type;

		// Set default font subsetting mode
		$this->setFontSubsetting(false);

		if(!empty($iDefaultFontId)) {
			if(is_numeric($iDefaultFontId)) {
				$oFont	= Ext_TC_System_Font::getInstance($iDefaultFontId);
				$sDefaultFontType = $oFont->getFontName('');
			} else {
				$sDefaultFontType = $iDefaultFontId;
			}
			$this->SetFont($sDefaultFontType, '', $iDefaultFontSize);
		}

		$aMargin = array();
		$aMargin['bottom'] 	= (float)$this->_oLayout->first_page_border_bottom;
		$aMargin['left'] 	= (float)$this->_oLayout->first_page_border_left;
		$aMargin['top'] 	= (float)$this->_oLayout->first_page_border_top;
		$aMargin['right'] 	= (float)$this->_oLayout->first_page_border_right;

		$this->SetMargins($aMargin['left'], $aMargin['top'], $aMargin['right'], true);
		$this->SetAutoPageBreak(true, $aMargin['bottom']);
		
		$this->SetLineStyle(array('width' => 0.2, 'color' => array(100, 100, 100)));
		$this->SetDrawColor(100, 100, 100);
        $this->SetLineWidth(0.2);

		$this->tcpdflink = false;

	}


	/**
	 * Set
	 *
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function __set($sName, $mValue) {

		switch($sName) {
			case 'title':
				$this->SetTitle($sTitle);
				break;
			case 'background_first':
			case 'background_following':
				if(is_numeric($mValue)) {
					$this->_aBackgrounds[$sName] = $this->_getBackgroundPath((int)$mValue);
				} else {
					$this->_aBackgrounds[$sName] = $mValue;
				}
				break;
		}
		
	}

	/**
	 * See parent
	 */
	public function Header() {

		if($this->page == 1) {
			// First page
			$aMargin = array();
			$aMargin['bottom'] 	= (float)$this->_oLayout->first_page_border_bottom;
			$aMargin['left'] 	= (float)$this->_oLayout->first_page_border_left;
			$aMargin['top'] 	= (float)$this->_oLayout->first_page_border_top;
			$aMargin['right'] 	= (float)$this->_oLayout->first_page_border_right;
			$sBackgroundPath	= $this->_aBackgrounds['background_first'];
		} else if($this->page > 0) {
			$aMargin = array();
			$aMargin['bottom'] 	= (float)$this->_oLayout->additional_page_border_bottom;
			$aMargin['left'] 	= (float)$this->_oLayout->additional_page_border_left;
			$aMargin['top'] 	= (float)$this->_oLayout->additional_page_border_top;
			$aMargin['right'] 	= (float)$this->_oLayout->additional_page_border_right;
			$sBackgroundPath	= $this->_aBackgrounds['background_following'];

			if(empty($sBackgroundPath))
			{
				$sBackgroundPath = $this->_aBackgrounds['background_first'];
			}
		}

		$this->setSourceFile($sBackgroundPath);
		$iTplId = $this->importPage(1, '/MediaBox');
		$this->useTemplate($iTplId);

		$this->SetMargins($aMargin['left'], $aMargin['top'], $aMargin['right'], true);
		$this->SetAutoPageBreak(true, $aMargin['bottom']);

	}


	/**
	 * Get the PDF path by file ID
	 *
	 * @param int $iFileID
	 * @return string
	 */
	protected function _getBackgroundPath($iFileID)
	{
		$oFile = new Ext_Thebing_Upload_File($iFileID);

		$sPath = $oFile->getPath();

		return $sPath;
	}
}