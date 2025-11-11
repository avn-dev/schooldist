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


class GUI_Span extends GUI_ElementList {

	protected $_sName  = '';
	protected $_sID    = '';
	protected $_sCss   = '';
	protected $_sStyle = '';

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

	public function generateHTML() {
		if ($this->_sHTML === null) {
			$sInnerHMTL = parent::generateHTML();
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$objSmarty->assign('sInnerHTML', $sInnerHMTL);
			$this->_sHTML = $objSmarty->parseTemplate('gui.span.tpl');
		}
		return $this->_sHTML;
	}

}
