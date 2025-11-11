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


abstract class GUI_FormSimple implements GUI_Element {

	protected $_sName     		= '';
	protected $_sID       		= '';
	protected $_sValue    		= '';
	protected $_sCss      		= '';
	protected $_sStyle    		= '';
	protected $_sOnClick  		= ''; 
	protected $_sOnDblClick  	= ''; 
	protected $_sOnFocus  		= ''; 
	protected $_sOnBlur  		= ''; 
	protected $_sOnKeyDown  	= ''; 
	protected $_sOnKeyPress  	= ''; 
	protected $_sOnKeyUp  		= ''; 
	protected $_sOnMouseDown	= ''; 
	protected $_sOnMouseUp		= ''; 
	protected $_sOnMouseMove	= ''; 
	protected $_sOnMouseOut		= ''; 
	protected $_sOnMouseOver	= ''; 
	protected $_sTemplate 		= null;
	protected $_sHTML     		= null;

	public function __construct(array $aConfig = array()) {
		foreach ($aConfig as $sCurrentIndex => $mixedCurrentValue) {
			switch ($sCurrentIndex) {
				case 'name':
					$this->_sName = (string)trim($mixedCurrentValue);
					break;
				case 'id':
					$this->_sID = (string)trim($mixedCurrentValue);
					break;
				case 'value':
					$this->_sValue = (string)trim($mixedCurrentValue);
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
				case 'onClick':
					$this->_sOnClick = (string)trim($mixedCurrentValue);
					break;
				case 'onDblClick':
					$this->_sOnDblClick = (string)trim($mixedCurrentValue);
					break;
				case 'onFocus':
					$this->_sOnFocus = (string)trim($mixedCurrentValue);
					break;
				case 'onBlur':
					$this->_sOnBlur = (string)trim($mixedCurrentValue);
					break;
				case 'onKeyDown':
				    $this->_sOnKeyDown = (string)trim($mixedCurrentValue);
				    break;
				case 'onKeyPress':
				    $this->_sOnKeyPress = (string)trim($mixedCurrentValue);
				    break;
				case 'onKeyUp':
				    $this->_sOnKeyUp = (string)trim($mixedCurrentValue);
				    break;
				case 'onMouseDown':
				    $this->_sOnMouseDown = (string)trim($mixedCurrentValue);
				    break;
				case 'onMouseUp':
				    $this->_sOnMouseUp = (string)trim($mixedCurrentValue);
				    break;
				case 'onMouseMove':
				    $this->_sOnMouseMove = (string)trim($mixedCurrentValue);
				    break;
				case 'onMouseOut':
				    $this->_sOnMouseOut = (string)trim($mixedCurrentValue);
				    break;    
				case 'onMouseOver':
				    $this->_sOnMouseOver = (string)trim($mixedCurrentValue);
				    break;          
				case 'template':
					$this->_sTemplate = (string)trim($mixedCurrentValue);
					break;
				default:
					throw new Exception('Unprocessed configuration value "'.$sCurrentIndex.'".');
			}
		}
		if ($this->_sTemplate === null) {
			throw new Exception('Template not specified.');
		}
	}


	/**
	 * Generate HTML output.
	 *
	 * @return string
	 */
	public function generateHTML() {

		// generate the output if required
		if ($this->_sHTML === null) {
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sValue', $this->_sValue);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$objSmarty->assign('sOnClick', $this->_sOnClick);
			$objSmarty->assign('sOnDblClick', $this->_sOnDblClick);
			$objSmarty->assign('sOnFocus', $this->_sOnFocus);
			$objSmarty->assign('sOnBlur', $this->_sOnBlur);
			$objSmarty->assign('sOnKeyDown', $this->_sOnKeyDown);
			$objSmarty->assign('sOnKeyPress', $this->_sOnKeyPress);
			$objSmarty->assign('sOnKeyUp', $this->_sOnKeyUp);
			$objSmarty->assign('sOnMouseDown', $this->_sOnMouseDown);
			$objSmarty->assign('sOnMouseUp', $this->_sOnMouseUp);
			$objSmarty->assign('sOnMouseMove', $this->_sOnMouseMove);
			$objSmarty->assign('sOnMouseOut', $this->_sOnMouseOut);
			$objSmarty->assign('sOnMouseOver', $this->_sOnMouseOver);
			$this->_assignTemplateVars($objSmarty);
			$this->_sHTML = $objSmarty->parseTemplate($this->_sTemplate);
		}
		// return the cached output
		return $this->_sHTML;
	}


	/**
	 * Method to assign custom template variables by extending classes.
	 *
	 * @param GUI_SmartyWrapper $objSmarty
	 * @return void
	 */
	protected function _assignTemplateVars(GUI_SmartyWrapper $objSmarty) {}


}
