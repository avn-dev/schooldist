<?php

abstract class Ext_Gui2_View_Format_Abstract implements Ext_Gui2_View_Format_Interface {

	protected $aOption;
	
	/**
	 * Spezielle Sprache falls gesetzt
	 * @var string 
	 */
	protected $_sLanguage = '';
    
	/**
	 * Flex-type
	 * @var string
	 */
	public $sFlexType = 'list';
	
	/**
	 * @var Ext_Gui2
	 */
	public $oGui;

	/**
	 * 
	 * @var \Tc\Service\LanguageAbstract
	 */
	protected $languageObject;
	
    public function __get($sOption){
		// für JS muss das Format umgewandelt werden
		// TODO Warum steht das eigentlich in dieser Klasse und nicht Ext_Gui2_View_Format_Date_Abstract?
		if($sOption == 'format_js'){

			$sFormat = strftime($this->format, mktime(12,13,14, 12, 31, 1990));
			$sFormat = str_replace(array('31','12','1990','90'), array('dd', 'mm', 'yyyy', 'yy'), $sFormat);
			return $sFormat;
		}
		return $this->aOption[$sOption];
	}

	public function __isset($sOption) {
		if(
			$sOption === 'format_js' ||
			isset($this->aOption[$sOption])
		) {
			return true;
		}

		return false;
	}

	public function __set($sOption, $mValue){
		$this->aOption[$sOption] = $mValue;
	}

	public function setLanguageObject(\Tc\Service\LanguageAbstract $languageObject) {
		$this->languageObject = $languageObject;
	}
	
	public function getLanguageObject(string $l10nPath=null) {
		
		if($this->languageObject === null) {
			$this->languageObject = new \Tc\Service\Language\Backend($this->_sLanguage);
			if($l10nPath !== null) {
				$this->languageObject->setContext($l10nPath);
			}
		}
		
		return $this->languageObject;
	}
	
	// formatiert den wert
	public function format($mValue, &$oColumn = null, &$aResultData = null){
		return $this->get($mValue, $oColumn, $aResultData);
	}

	// liefert den wert
	public function get($mValue, &$oColumn = null, &$aResultData = null){
		return $mValue;
	}

	// bestimmt die ausrichtung dieser Formatierung
	public function align(&$oColumn = null){
		return 'left';
	}

	// Wandelt den wert wieder in den ursprungswert um
	public function convert($mValue, &$oColumn = null, &$aResultData = null){
		return $mValue;
	}

	// Gibt den title / tooltip zu einem Feld aus
	public function getTitle(&$oColumn = null, &$aResultData = null) {
		return false;
	}
	
	/**
	 * Gibt den title / tooltip zu einem Feld aus
	 * Hierfür wird eine V5 Controller Klasse benötigt
	 * @param type $oColumn
	 * @param type $aResultData
	 * @return type 
	 */
	public function getMVCTitle(&$oColumn = null, &$aResultData = null) {
		return false;
	}

	public function getSumValue($mValue, &$oColumn = null, &$aResultData = null){
		return $this->get($mValue, $oColumn, $aResultData);
	}
	
	// Mit dieser Funktion wird die Summenzeile formatiert
	public function formatSum($mValue, &$oColumn = null, &$aResultData = null){
		return $this->format($mValue, $oColumn, $aResultData);
	}
	
	/**
	 * setzt ein ISO
	 * @param string $sIso 
	 */
	public function setLanguage($sIso){
		$this->_sLanguage = $sIso;
	}

	/**
	 * Wert formatieren mit $aResult, $mValue und $oColumn umgehen
	 *
	 * @param array $aResult
	 * @return string
	 */
	public function formatByResult($aResult) {

		$mDummy = '';
		$oDummy = new stdClass();

		return $this->format($mDummy, $oDummy, $aResult);

	}

	/**
	 * Wert formatieren mit $mValue, $oColumn und $aResult ignorieren
	 *
	 * @param mixed $mValue
	 * @return string
	 */
	public function formatByValue($mValue) {

		$oDummy = new stdClass();
		$aResult = array();

		return $this->format($mValue, $oDummy, $aResult);

	}

	public function setExcelValue($mValue, $oCell, $oColumn, $aValue, $aResultData=null) {
		$oCell->setValueExplicit(
			$mValue,
			\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
		);
	}
	
}
