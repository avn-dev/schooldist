<?php

class Ext_TC_ZenDesk_Errors_Error {
	
	public $sError;
	public $sDescription;
	public $aChilds;

	/**
	 * @return string
	 */
	public function __toString() {
		
		$sError = $this->sDescription;
		
		if(!empty($this->sError)) {
			$sError .= ' ('.$this->sError.')';
		}

		return $sError;
	}
	
}
	