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
 * Class that represents a simple html string.
 */
class GUI_String implements GUI_Element {


	/**
	 * The stored HTML string.
	 *
	 * @var string
	 */
	protected $_sHTML = '';


	/**
	 * Constructor.
	 *
	 * The input value will be escaped if
	 * it is not a GUI_Element.
	 *
	 * @param GUI_Element|string $mInput
	 * @return void
	 */
	public function __construct($mInput) {

		// the input element is a gui element
		if ($mInput instanceof GUI_Element) {
			$this->_sHTML = (string)$mInput->generateHTML();
		}

		// the input element is a string
		else {
			$this->_sHTML = (string)\Util::convertHtmlEntities((string)$mInput);
		}

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
