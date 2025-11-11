<?php


/*
 * -- webDynamics GUI --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/gui/gui.php
 * The list of dependencies is available in that file.
 *
 * 
 */


class GUI_Page extends GUI_ElementList {

	protected $_bAdminPage          = true;
	protected $_bIncludeAdminHeader = true;
	protected $_bIncludeAdminFooter = true;
	protected $_aHeaderOptions		= array();

	public function __get($sName) {
		switch ($sName) {
			case 'bAdminPage':
				return $this->_bAdminPage;
			case 'bIncludeAdminHeader':
				return $this->_bIncludeAdminHeader;
			case 'bIncludeAdminFooter':
				return $this->_bIncludeAdminFooter;
		}
		throw new Exception('Unable to get property "'.$sName.'".');
	}

	public function __set($sName, $mixedValue) {
		switch ($sName) {
			case 'sJsLib':
				$this->_aHeaderOptions['jslib'] = $mixedValue;
				return;
			case 'bAdminPage':
				if (!is_bool($mixedValue)) {
					throw new Exception('Value of property "'.$sName.'" must be of type boolean.');
				}
				$this->_bIncludeAdminHeader = $mixedValue;
				$this->_bIncludeAdminFooter = $mixedValue;
				$this->_bAdminPage          = $mixedValue;
				$this->_sHTML               = null;
				return;
			case 'bIncludeAdminHeader':
				if (!is_bool($mixedValue)) {
					throw new Exception('Value of property "'.$sName.'" must be of type boolean.');
				}
				$this->_bIncludeAdminHeader = $mixedValue;
				$this->_bAdminPage          = ($this->_bIncludeAdminHeader == true && $this->_bIncludeAdminFooter == true);
				$this->_sHTML               = null;
				return;
			case 'bIncludeAdminFooter':
				if (!is_bool($mixedValue)) {
					throw new Exception('Value of property "'.$sName.'" must be of type boolean.');
				}
				$this->_bIncludeAdminFooter = $mixedValue;
				$this->_bAdminPage          = ($this->_bIncludeAdminHeader == true && $this->_bIncludeAdminFooter == true);
				$this->_sHTML               = null;
				return;
		}
		throw new Exception('Unable to set property "'.$sName.'".');
	}

	public function __construct($bAdminPage = true) {

		// call parent consturctor
		parent::__construct();

		if ($bAdminPage === false) {
			$this->_bAdminPage          = false;
			$this->_bIncludeAdminHeader = false;
			$this->_bIncludeAdminFooter = false;
		}
	}

	public function generateHTML() {
		if ($this->_sHTML === null) {
			$sHTML = '';
			if ($this->_bIncludeAdminHeader) {
				$objAdminHeader = new GUI_AdminHeader($this->_aHeaderOptions);
				$sHTML .= $objAdminHeader->generateHTML();
			}
			$sHTML .= parent::generateHTML();
			if ($this->_bIncludeAdminFooter) {
				$objAdminFooter = new GUI_AdminFooter();
				$sHTML .= $objAdminFooter->generateHTML();
			}
			$this->_sHTML = $sHTML;
		}
		return $this->_sHTML;
	}

}
