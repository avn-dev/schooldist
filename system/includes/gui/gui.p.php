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


class GUI_P extends GUI_ElementList {

	protected $_sName  = null;
	protected $_sID    = null;
	protected $_sCss   = null;
	protected $_sStyle = null;
	protected $_sHTML  = null;

	public function __construct(array $aConfig = array()) {

		// call parent consturctor
		parent::__construct();

		foreach ($aConfig as $sCurrentIndex => $mixedCurrentValue) {
			switch ($sCurrentIndex) {
				case 'name':
					$this->_sName = (string)trim($mixedCurrentValue);
					break;
				case 'id':
					$this->_sID = (string)trim($mixedCurrentValue);
					break;
				case 'css':
					$this->_sCss = (string)trim($mixedCurrentValue);
					break;
				case 'style':
					$this->_sStyle = (string)trim($mixedCurrentValue);
					break;
				case 'appendCss':
					$this->_sCss = (string)trim($this->_sCss.' '.trim($mixedCurrentValue));
					break;
				case 'appendStyle':
					$this->_sStyle = (string)trim($this->_sStyle.' '.trim($mixedCurrentValue));
					break;
				default:
					throw new Exception('Unprocessed configuration value "'.$sCurrentIndex.'".');
			}
		}
	}

	public function appendElement($mixedElement) {
		parent::appendElement($mixedElement);
		$this->_sHTML = null;
	}

	public function appendElementWrap($mixedElement) {
		parent::appendElementWrap($mixedElement);
		$this->_sHTML = null;
	}

	public function generateHTML() {
		if ($this->_sHTML === null) {
			$sInnerHMTL = parent::generateHTML();
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$objSmarty->assign('sInnerHTML', $sInnerHMTL);
			$this->_sHTML = $objSmarty->parseTemplate('gui.p.tpl');
		}
		return $this->_sHTML;
	}

}
