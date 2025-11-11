<?php


/*
 * -- webDynamics GUI --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/gui/gui.php
 * The list of dependencies is available in that file.
 */


/**
 * Class that represents a html password input element.
 */
class GUI_FormPassword extends GUI_FormSimple {

	
	protected $_sOnChange 		= ''; //pd
	protected $_sOnSelect 		= ''; //pd

	/**
	 * Constructor.
	 *
	 * The following configuration options are accepted
	 * by the constructor of the parent class:
	 * - (string) name
	 * - (string) id
	 * - (string) value
	 * - (string) css
	 * - (string) style
	 * - (string) appendCss
	 * - (string) appendStyle
	 * - (string) onClick
	 * - (string) template
	 *
	 * The option "template" will by overwritten by this class.
	 *
	 * @param array $aConfig
	 * @return void
	 */
	public function __construct(array $aConfig = array()) {
		$aConfig['template'] = 'gui.formpassword.tpl';
		$this->_sCss = 'txt';
		if(array_key_exists('onChange', $aConfig)){
			$this->_sOnChange = $aConfig['onChange'];
			unset($aConfig['onChange']);
		} 
		if(array_key_exists('onSelect', $aConfig)){
			$this->_sOnSelect = $aConfig['onSelect'];
			unset($aConfig['onSelect']);
		}
		parent::__construct($aConfig);
	}

	protected function _assignTemplateVars(GUI_SmartyWrapper $objSmarty) {
		$objSmarty->assign('sOnChange',$this->_sOnChange);
		$objSmarty->assign('sOnSelect',$this->_sOnSelect);
	}


}
