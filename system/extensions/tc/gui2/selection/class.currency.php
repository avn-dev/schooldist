<?php

/**
 * Allgemeine Selection-Klasse für Währungen
 * 
 * Hat zwei Parameter, Kommentar über dem Konstruktor beachten!
 */
class Ext_TC_Gui2_Selection_Currency extends Ext_Gui2_View_Selection_Abstract {

	private $_aCurrencies = array();
	private $_sField = '';
	
	/**
	 * Optional kann man angeben, welche Währungen aufgenommen werden.
	 * Dies dient der Performance.
	 * 
	 * Beispiel sind die Währungen, die eine Agentur unter TA zuweisen kann.
	 * 
	 * @param string $sField Das WDBASIC-Feld im Dialog
	 * @param array $aCurrencies Die Währungen
	 */
	public function __construct($sField, $aCurrencies = array())
	{
		
		$this->_sField = $sField;
		
		if(empty($aCurrencies)) {
			$aCurrencies = Ext_TC_Currency::getSelectOptions();
		} else {
			$this->_aCurrencies = $aCurrencies;
		}
		
	}
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		
		$sField = $this->_sField;
		
		$aReturn = array();

		foreach((array)$oWDBasic->$sField as $sKey)
		{
			$aReturn[$sKey] = $this->_aCurrencies[$sKey];
		}

		return $aReturn;

	}
	
}
?>
