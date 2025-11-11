<?php

namespace Tc\Controller;

/*
 * Test
 */
class CronjobController  extends \MVC_Abstract_Controller {

	// Zugriffschutz geht über Lizenz
	protected $_sAccessRight = null;
	
	public function request() {
		global $_VARS;
		
		$_VARS = $this->_oRequest->getAll();
		
		header('Content-Type: text/html; charset=utf-8');

		$sReturn = \Ext_TC_System_Cronjob_Update::evaluateData();

		echo $sReturn;
		die();
		
	}
	
}