<?php


class Ext_TC_Frontend_Combination_Login_Form {

	protected $_aFields = array();
	protected static $iFieldCount = 1;
	protected $_oAbstract = null;
	protected $_bSetCleaner = false;

	public function __construct($oAbstract, $bSetCleaner = true){
		$this->_oAbstract = $oAbstract;
		if($bSetCleaner){
			$this->_bSetCleaner = true;
		}
	}

	public function addRow($sType = 'input', $sLabel = '', $sValue, $aOptions = array()){

		$sReadonly = '';
		$sClass = '';
		if($aOptions['readonly'] === true){
			$sReadonly = 'readonly="readonly" disabled="disabled"';
			$sClass = 'readonly';
		}

		$sLabel = '<label for="student_form_field_' . self::$iFieldCount . '">' . $this->_oAbstract->t($sLabel) . '</label>';
		switch($sType){
			case 'input':
				$sField = '<input id="student_form_field_' . self::$iFieldCount . '" ' . $sReadonly . ' class="' . $sReadonly . '" value="'.htmlspecialchars($sValue).'"/>';
				break;
			case 'upload':
				$sField = '<input id="student_form_field_' . self::$iFieldCount . '" ' . $sReadonly . ' class="' . $sReadonly . '" type="file"/>';
				break;
		}

		$sCleaner = '';
		if($this->_bSetCleaner){
			$sCleaner = '<div class="clearer"></div>';
		}

		$sRow = '<div class="form-row">';
		$sRow .= $sLabel . $sField . $sCleaner;
		$sRow .= '</div>';

		self::$iFieldCount++;

		$this->_aFields[] = $sRow;
	}

	public function addHeadline($sLable){
		$this->_aFields[] = '<h4>' . $this->_oAbstract->t($sLable) . '</h4>';
	}

	public function addLine(){
		$this->_aFields[] = '<hr class="hr_line"/>';
	}


	public function __toString(){

		$sBack = '';
		foreach($this->_aFields as $sField){
			$sBack .= $sField;
		}

		return $sBack;
	}

	public function reset($bResetFieldCounter=true){
		$this->_aFields = array();
		if($bResetFieldCounter){
			self::$iFieldCount = 1;
		}
	}
}