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


class GUI_SmartyWrapper extends SmartyWrapper {

	public function __construct() {
		parent::__construct();
		$this->setTemplateDir(realpath(dirname(__FILE__)).'/smarty/templates');
		$this->setCompileDir(realpath(dirname(__FILE__)).'/smarty/templates_c');
		$this->setCacheDir(realpath(dirname(__FILE__)).'/smarty/cache');
	}

	public function parseTemplate($sTemplate) {
		ob_start();
		$this->display($sTemplate);
		$sContent = ob_get_contents();
		ob_end_clean();
		return $sContent;
	}

}
