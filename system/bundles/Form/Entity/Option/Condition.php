<?php

namespace Form\Entity\Option;

class Condition extends \WDBasic {
	
	protected $_sTable = 'form_options_conditions';
	protected $_sTableAlias = 'f_oc';
	
	public function __get($sName) {
		
		$mValue = parent::__get($sName);
		
		if($sName === 'value') {
			$mValue = trim($mValue);
		}
		
		return $mValue;
	}
	
	public function __set($sName, $mValue) {
		
		
		if($sName === 'value') {
			$mValue = trim($mValue);
		}
		
		parent::__set($sName, $mValue);
		
	}
	
}