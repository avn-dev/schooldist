<?php

class Ext_TC_ZenDesk_Errors_Iterator implements Iterator {
	
	private $aErrors = array();
	private $iKey = 0;
	
    public function __construct(array $aErrors) {
        $this->aErrors = $aErrors;
		$this->iKey = 0;
    }

    function rewind() {
        $this->iKey = 0;
    }

    function current() {
        return $this->aErrors[$this->iKey];
    }

    function key() {
        return $this->iKey;
    }

    function next() {
        ++$this->iKey;
    }
	
    function valid() {
        return isset($this->aErrors[$this->iKey]);
    }
}