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
 * Class that represents a html checkbox element.
 */
class GUI_FormCheckbox extends GUI_FormSimple {


	/**
	 * Display the checkbox as checked.
	 *
	 * @var boolean
	 */
	protected $_bChecked = false;
	
	protected $_sOnChange 	= '';

	/**
	 * Constructor.
	 *
	 * The following configuration options are accepted:
	 * - (boolean) checked
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
		$aConfig['template'] = 'gui.formcheckbox.tpl';
		if (array_key_exists('checked', $aConfig)) {
			$this->_bChecked = ($aConfig['checked'] === true) ? true : false;
			unset($aConfig['checked']);
		}
		if(array_key_exists('onChange', $aConfig)){
			$this->_sOnChange = $aConfig['onChange'];
			unset($aConfig['onChange']);
		}
		parent::__construct($aConfig);
	}


	/**
	 * Assign custom template variables.
	 *
	 * @param GUI_SmartyWrapper $objSmarty
	 * @return void
	 */
	protected function _assignTemplateVars(GUI_SmartyWrapper $objSmarty) {
		$objSmarty->assign('bChecked', $this->_bChecked);
	}


}
