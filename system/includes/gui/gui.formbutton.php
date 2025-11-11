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
 * Class that represents a html button element.
 */
class GUI_FormButton extends GUI_FormSimple {


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
		$aConfig['template'] = 'gui.formbutton.tpl';
		$this->_sCss = 'btn btn-default';
		parent::__construct($aConfig);
	}


}
