<?php

namespace TcAccounting\Service\eInvoice\Exceptions;

class BuildException extends \RuntimeException {
	
	protected $aParameters = [];
	
	public function bindParameter($sParameter) {
		$this->aParameters[] = $sParameter;
		return $this;
	}
	
	public function getTranslatedMessage() {

		switch($this->message) {
			default:
				$sErrorMessage = 'Unknown';
		}
		
		return $sErrorMessage;
	}
	
}
