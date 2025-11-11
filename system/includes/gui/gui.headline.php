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


class GUI_Headline implements GUI_Element {

	protected $_sString       = '';
	protected $_bEscapeString = true;
	protected $_iType         = 1;
	protected $_sName         = '';
	protected $_sID           = '';
	protected $_sCss          = '';
	protected $_sStyle        = '';
	protected $_sHTML         = null;

	public function __construct(array $aConfig = array()) {
		foreach ($aConfig as $sCurrentIndex => $mixedCurrentValue) {
			switch ($sCurrentIndex) {
				case 'text':
				case 'string':
					if ($mixedCurrentValue instanceof GUI_Element) {
						$this->_sString       = (string)trim($mixedCurrentValue->generateHTML());
						$this->_bEscapeString = false;
					} else {
						$this->_sString       = (string)trim($mixedCurrentValue);
						$this->_bEscapeString = true;
					}
					break;
				case 'type':
					$this->_iType = intval($mixedCurrentValue);
					break;
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
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sString', $this->_sString);
			$objSmarty->assign('bEscapeString', $this->_bEscapeString);
			$objSmarty->assign('iType', $this->_iType);
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$this->_sHTML = $objSmarty->parseTemplate('gui.headline.tpl');
		}
		return $this->_sHTML;
	}

}
