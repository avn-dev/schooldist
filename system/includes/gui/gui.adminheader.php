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
 * Class that generates the webDynamics page header.
 */
class GUI_AdminHeader implements GUI_Element {


	/**
	 * The generated html string.
	 *
	 * @var string
	 */
	protected $_sHTML = '';
	protected $_aOptions = '';


	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct($arrOptions=array()) {
		$this->_aOptions = $arrOptions;
	}

	public function setOption($strKey, $mixValue) {
		$this->_aOptions[$strKey] = $mixValue;
	}

	/**
	 * Generate HTML output.
	 *
	 * @return string
	 */
	public function generateHTML() {
		ob_start();
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		echo Admin_Html::loadAdminHeader($this->_aOptions);
		echo '<div style="margin: 30px;">';
		$this->_sHTML = ob_get_contents();
		ob_end_clean();
		return $this->_sHTML;
	}

}
