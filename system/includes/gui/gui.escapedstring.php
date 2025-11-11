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
class GUI_EscapedString extends GUI_String {


	/**
	 * Constructor.
	 *
	 * The input value will NOT be escaped even if
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
			$this->_sHTML = (string)$mInput;
		}

	}


}
