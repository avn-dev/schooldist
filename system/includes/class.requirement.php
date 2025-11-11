<?php

abstract class Requirement implements Requirement_Interface {
	
	protected $_aErrors = array();
	
	/**
	 * prüft, ob der Server den Systemvoraussetzungen entspricht
	 * @return boolean 
	 */
	public function checkSystemRequirements() {
		return true;
	}
	
	/**
	 * gibt ein Array mit allen Fehlermeldungen zurück, die bei der Prüfung der 
	 * Systemvoraussetzungen geworfen wurden
	 * @return array 
	 */
	public function getErrorMessage() {
		return $this->_aErrors;
	}
	
}
