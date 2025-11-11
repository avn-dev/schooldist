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


class GUI_FormSelect implements GUI_Element {

	protected $_sName        = '';
	protected $_sID          = '';
	protected $_sCss         = 'txt form-control';
	protected $_sStyle       = '';
	protected $_sOnChange    = '';
	protected $_sOnBlur  		= ''; 
	protected $_sOnClick		= '';
	protected $_sOnDblClick 	= '';
	protected $_sOnFocus  		= '';
	protected $_sOnKeyDown  	= '';
	protected $_sOnKeyPress  	= '';
	protected $_sOnKeyUp  		= '';
	protected $_sOnMouseDown	= '';
	protected $_sOnMouseUp		= ''; 
	protected $_sOnMouseMove	= ''; 
	protected $_sOnMouseOut		= ''; 
	protected $_sOnMouseOver	= ''; 
	protected $_aSelected    = array();
	protected $_bMultiSelect = false;
	protected $_iMultiSize   = '8';
	protected $_aValues      = array();
	protected $_sHTML        = null;

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
				case 'selected':
					if (is_array($mixedCurrentValue)) {
						foreach ($mixedCurrentValue as $sCurrentValue) {
							$this->_aSelected[] = (string)trim($sCurrentValue);
						}
					} else {
						$this->_aSelected[] = (string)trim($mixedCurrentValue);
					}
					break;
				case 'css':
					$this->_sCss = (string)trim($mixedCurrentValue);
					break;
				case 'style':
					$this->_sStyle = (string)trim($mixedCurrentValue);
					break;
				case 'onChange':
					$this->_sOnChange = (string)trim($mixedCurrentValue);
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
				case 'appendCss':
					$this->_sCss = (string)trim($this->_sCss.' '.trim($mixedCurrentValue));
					break;
				case 'appendStyle':
					$this->_sStyle = (string)trim($this->_sStyle.' '.trim($mixedCurrentValue));
					break;
				case 'multi':
				case 'multiple':
				case 'multiSelect':
					$this->_bMultiSelect = ($mixedCurrentValue === true) ? true : false;
					break;
				case 'size':
				case 'multiSize':
					$this->_iMultiSize = intval($mixedCurrentValue);
					break;
				case 'options':
				case 'values':
					if (!is_array($mixedCurrentValue)) {
						throw new Exception('Configuration value "'.$sCurrentIndex.'" is not an array.');
					}
					foreach ($mixedCurrentValue as $aCurrentValueIndex => $aCurrentValue) {
						if (!is_array($aCurrentValue)) {
							$mCurrentValue = $aCurrentValue;
							$aCurrentValue = array();
							$aCurrentValue['display'] = $mCurrentValue;
							$aCurrentValue['value'] = $aCurrentValueIndex;
							//throw new Exception('Value of entry with index "'.$aCurrentValueIndex.'" is not an array in configuration value "'.$sCurrentIndex.'".');
						}
						if (!array_key_exists('value', $aCurrentValue)) {
							throw new Exception('Missing entry "value" for entry with index "'.$aCurrentValueIndex.'" in configuration value "'.$sCurrentIndex.'".');
						}
						if (!array_key_exists('display', $aCurrentValue)) {
							throw new Exception('Missing entry "display" for entry with index "'.$aCurrentValueIndex.'" in configuration value "'.$sCurrentIndex.'".');
						}
						$this->_aValues[trim($aCurrentValue['value'])] = (string)trim($aCurrentValue['display']);
					}
					break;
				default:
					throw new Exception('Unprocessed configuration value "'.$sCurrentIndex.'".');
			}
		}
		if ($this->_sValue !== null) {
			$bValueFound = false;
			foreach ($this->_aValues as $aCurrentValue) {
				if ($aCurrentValue['sValue'] == $this->_sValue) {
					$bValueFound = true;
					break;
				}
			}
			if ($bValueFound != true) {
				throw new Exception('Specified value "'.$this->_sValue.'" not found in list of allowed values.');
			}
		} else {
			$this->_sValue = '';
		}
	}

	public function generateHTML() {
		if ($this->_sHTML === null) {
			$objSmarty = new GUI_SmartyWrapper();
			$objSmarty->assign('sName', $this->_sName);
			$objSmarty->assign('sID', $this->_sID);
			$objSmarty->assign('sCss', $this->_sCss);
			$objSmarty->assign('sStyle', $this->_sStyle);
			$objSmarty->assign('sOnChange', $this->_sOnChange);
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
			$objSmarty->assign('aSelected', $this->_aSelected);
			$objSmarty->assign('bMultiSelect', $this->_bMultiSelect);
			$objSmarty->assign('iMultiSize', $this->_iMultiSize);
			$objSmarty->assign('aValues', $this->_aValues);
			$this->_sHTML = $objSmarty->parseTemplate('gui.formselect.tpl');
		}
		return $this->_sHTML;
	}

}
