<?php

/**
 * @deprecated
 */
class Ext_Thebing_Pdf_Document {

	public $aTables = [];

	protected $_aData = array();
	protected $_aAdditional = array();
	
	/**
	 * Flag der gesetzt wird, falls das PDF schon fertig generiert wurde
	 * @var boolean
	 */
	protected $_bGenerated = false; 

	/*
	 * 	
	 $element_date = '';
	 $element_subject = '';
	 $element_address = '';
	 $element_text1 = '';
	 $element_text2 = '';
	 $element_signature_text = '';
	 $element_signature_img = '';
	
	 */
	
	public function __get($sField){
		$sValue = $this->_aData[$sField];
		return $sValue;
	}
	
	public function __set($sField, $mValue){
		$this->_aData[$sField] = $mValue;
	}

	public function setGenerated($bGenerated) {
		$this->_bGenerated = (bool)$bGenerated;
	}
	
	public function checkGenerated() {
		return $this->_bGenerated;
	}
	
	public function setAdditional($aAdditional) {
		$this->_aAdditional = $aAdditional;
	}

	public function getAdditional() {
		return $this->_aAdditional;
	}

}

