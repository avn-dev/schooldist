<?php

abstract class Ext_Thebing_Number {

	protected $_sPrefix				= '';
	protected $_sFormat				= '%count';
	protected $_sCurrentFormat		= '%count';
	protected $_iDigits				= 3;
	protected $_iOffset				= 1;
	protected $_iOffsetIteration	= 1;
	protected $_aOptions			= array();
	protected $_iTimestamp			= null;

	public function __set($sName, $sValue) {
		switch($sName) {
			case 'format':
				$this->_sFormat = $sValue;
				break;
			case 'offset':
				$this->_iOffset = $sValue;
				break;
			case 'offset_iteration':
				$this->_iOffsetIteration = $sValue;
				break;
			case 'digits':
				$this->_iDigits = $sValue;
				break;
			case 'prefix':
				$this->_sPrefix = $sValue;
				break;
			default:
				if(isset($this->_aOptions[$sName])) {
					$this->_aOptions[$sName] = $sValue;
				} else {
					throw new Exception('Option "'.$sName.'" does not exists.');
				}
				break;
		}
	}

	public function generateNumber() {

		$this->_sFormat = $this->_sPrefix.$this->_sFormat;

		$sFormat = $this->_sFormat;

		if(strpos($sFormat, '%count') === false) {
			$this->_sFormat .= '%count';
			$sFormat = $this->_sFormat;
		}

		$sFormat = str_replace('%count', '', $sFormat);

		if(
			is_null($this->_iTimestamp) ||
			empty($this->_iTimestamp)
		) {
			$this->_iTimestamp = time();
		}

		preg_match_all('/(\%[a-zA-Z0-9]*)/', $sFormat, $aParts);

		$aTemp = array();
		$bCountOk = false;
		foreach((array)$aParts[0] as $sPart) {
			$aTemp[$sPart] = strftime($sPart, $this->_iTimestamp);
		}

		$this->_sCurrentFormat = $this->_sFormat;

		foreach((array)$aTemp as $sPlaceholder => $sData) {
			$this->_sCurrentFormat = str_replace($sPlaceholder, $sData, $this->_sCurrentFormat);
		}

		list($sPrefix, $sPostfix) = explode('%count', $this->_sCurrentFormat, 2);

		$sLatestNumber = $this->_searchLatestNumber($sPrefix, $sPostfix);

		if(!empty($sLatestNumber)) {

			$sLatestNumber = str_replace($sPrefix, '', $sLatestNumber);
			$sLatestNumber = str_replace($sPostfix, '', $sLatestNumber);

			$iLatestNumber = (int)$sLatestNumber;

			$sCount = str_pad($iLatestNumber + 1, $this->_iDigits, '0', STR_PAD_LEFT);

		} else {

			$sAnyNumber = $this->_searchLatestNumber('', '');

			// Wenn noch gar keine Nummer gesetzt wurde, dann Offset verwenden
			if(empty($sAnyNumber)) {
				$iOffset = $this->_iOffset;
			} else {
				$iOffset = $this->_iOffsetIteration;
			}

			$sCount = str_pad($iOffset, $this->_iDigits, '0', STR_PAD_LEFT);

		}

		$sNumber = str_replace('%count', $sCount, $this->_sCurrentFormat);

		return $sNumber;

	}

	abstract protected function _searchLatestNumber($sPrefix, $sPostfix);
}