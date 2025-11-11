<?php

namespace TsMobile\Generator\Pages;

use TsMobile\Generator\AbstractPage;

class Error extends AbstractPage {
	
	protected $_sErrorMessage = '';
	
	public function render(array $aData = array()) {
		
		$sTemplate = $this->_sErrorMessage;
		
		return $sTemplate;
	}
	
	public function setErrorMessage($sErrorMessage) {
		$this->_sErrorMessage = $sErrorMessage;
	}
	
}