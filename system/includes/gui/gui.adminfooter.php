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
 * Class that generates the webDynamics page footer.
 */
class GUI_AdminFooter implements GUI_Element {


	/**
	 * The generated html string.
	 *
	 * @var string
	 */
	protected $_sHTML = '';


	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		ob_start();
		echo '</div>';
		echo Admin_Html::loadAdminFooter();
		$this->_sHTML = ob_get_contents();
		ob_end_clean();
	}


	/**
	 * Generate HTML output.
	 *
	 * @return string
	 */
	public function generateHTML() {
		return $this->_sHTML;
	}


}
