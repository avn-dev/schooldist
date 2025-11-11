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


class GUI_Image implements GUI_Element {

	protected $_sImageURL        = null;
	protected $_bEscapeImageURL  = null;
	protected $_sAltText         = null;
	protected $_bEscapeAltText   = null;
	protected $_sTitleText       = null;
	protected $_bEscapeTitleText = null;
	protected $_iWidth           = null;
	protected $_iHeight          = null;
	protected $_sName            = '';
	protected $_sID              = '';
	protected $_sCss             = '';
	protected $_sStyle           = '';
	protected $_sHTML            = null;

	public function __construct(array $aConfig = array()) {
		foreach ($aConfig as $sCurrentIndex => $mixedCurrentValue) {
			switch ($sCurrentIndex) {
				case 'url':
					$this->_sImageURL = $mixedCurrentValue;
					break;
				case 'alt':
					$this->_sAltText = $mixedCurrentValue;
					break;
				case 'title':
					$this->_sTitleText = $mixedCurrentValue;
					break;
				case 'width':
					$this->_iWidth = intval($mixedCurrentValue);
					break;
				case 'height':
					$this->_iHeight = intval($mixedCurrentValue);
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
		if ($this->_sImageURL === null) {
			throw new Exception('No image url specified.');
		}
		if ($this->_sImageURL instanceof GUI_EscapedString) {
			$this->_sImageURL       = (string)trim($this->_sImageURL->generateHTML());
			$this->_bEscapeImageURL = false;
		} else {
			$this->_sImageURL       = (string)trim($this->_sImageURL);
			$this->_bEscapeImageURL = true;
		}
		if ($this->_sAltText === null && $this->_sAltText === null) {
			$this->_setAltText(basename($this->_sImageURL));
			$this->_setTitleText(basename($this->_sImageURL));
		} elseif ($this->_sAltText === null && $this->_sTitleText !== null) {
			$this->_setAltText(basename($this->_sImageURL));
			$this->_setTitleText($this->_sTitleText);
		} elseif ($this->_sAltText !== null && $this->_sTitleText === null) {
			$this->_setAltText($this->_sAltText);
			$this->_setTitleText($this->_sAltText);
		} elseif ($this->_sAltText !== null && $this->_sTitleText !== null) {
			$this->_setAltText($this->_sAltText);
			$this->_setTitleText($this->_sTitleText);
		}
	}

	public function generateHTML() {
		if ($this->_sHTML === null) {
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sImageURL', $this->_sImageURL);
			$objSmarty->assign('bEscapeImageURL', $this->_bEscapeImageURL);
			$objSmarty->assign('sAltText', $this->_sAltText);
			$objSmarty->assign('bEscapeAltText', $this->_bEscapeAltText);
			$objSmarty->assign('sTitleText', $this->_sTitleText);
			$objSmarty->assign('bEscapeTitleText', $this->_bEscapeTitleText);
			$objSmarty->assign('iWidth', $this->_iWidth);
			$objSmarty->assign('iHeight', $this->_iHeight);
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$this->_sHTML = $objSmarty->parseTemplate('gui.image.tpl');
		}
		return $this->_sHTML;
	}

	protected function _setAltText($sText) {
		if ($sText instanceof GUI_EscapedString) {
			$this->_sAltText       = (string)trim($sText->generateHTML());
			$this->_bEscapeAltText = false;
		} else {
			$this->_sAltText       = (string)trim($sText);
			$this->_bEscapeAltText = true;
		}
	}

	protected function _setTitleText($sText) {
		if ($sText instanceof GUI_EscapedString) {
			$this->_sTitleText       = (string)trim($sText->generateHTML());
			$this->_bEscapeTitleText = false;
		} else {
			$this->_sTitleText       = (string)trim($sText);
			$this->_bEscapeTitleText = true;
		}
	}

}
