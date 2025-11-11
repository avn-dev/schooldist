<?php

class Ext_Gui2_View_Format_YesNo extends Ext_Gui2_View_Format_Abstract {

	protected $_sYes;
	protected $_sNo;
	protected $bStrict;

	public function __construct($bStrict=false) {
		$this->_sYes 	= L10N::t('Ja');
		$this->_sNo		= L10N::t('Nein');
		$this->bStrict = $bStrict;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if (is_array($mValue)) {
			// TODO in Index-Listen ist es nicht so einfach ein Array aus true/false Werten mit post_format zu formatieren
			return implode('<br/>', array_map(fn ($mSubValue) => $this->format($mSubValue, $oColumn, $aResultData), $mValue));
		}

		if($this->bStrict === true) {
			
			if(
				$mValue === 1 ||
				$mValue === '1'
			) {
				$mValue = $this->_sYes;
			} elseif(
				$mValue === 0 ||
				$mValue === '0'
			) {
				$mValue = $this->_sNo;
			} else {
				$mValue = '';
			}
			
		} else {
			
			if($mValue == 1) {
				$mValue = $this->_sYes;
			} else {
				$mValue = $this->_sNo;
			}
			
		}
		
		return $mValue;
	}
	
}
