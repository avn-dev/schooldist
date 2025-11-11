<?php

class Ext_TC_Gui2_Format_Align extends Ext_Gui2_View_Format_Abstract {

	protected $_sAlign = null;

	public function __construct($sAlign='right') {

		$this->_sAlign = $sAlign;
		
	}
	
	public function align(&$oColumn = null){
		return $this->_sAlign;
	}

}