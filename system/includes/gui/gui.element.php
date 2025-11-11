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
 * Basic interface for gui elements.
 */
interface GUI_Element {


	/**
	 * Generate HTML output.
	 *
	 * @return string
	 */
	function generateHTML();


}
