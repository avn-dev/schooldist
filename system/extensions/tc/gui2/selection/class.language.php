<?php

/**
 * Allgemeine Selection-Klasse für Sprachen
 * 
 * Hat zwei Parameter, Kommentar über dem Konstruktor beachten!
 */
class Ext_TC_Gui2_Selection_Language extends Ext_Gui2_View_Selection_Abstract {

	private $_aLanguages = array();
	private $_sField = '';
	
	/**
	 * Optional kann man angeben, welche Sprachen aufgenommen werden.
	 * Dies dient der Performance.
	 * 
	 * Sind keine Sprachen angegeben, wird eine lokalisierte Version
	 *	der Sprachen geholt, nach der Loginsprache des CMS.
	 * 
	 * Beispiel sind die Sprachen, die eine Agentur unter TA zuweisen kann.
	 * 
	 * @param string $sField Das WDBASIC-Feld im Dialog
	 * @param array $aLanguages Die Sprachen
	 */
	public function __construct($sField, $aLanguages = array())
	{
		global $session_data;
		
		$this->_sField = $sField;
		
		if(empty($aLanguages)) {
			$aLanguages = Ext_TC_Language::getSelectOptions(\System::getInterfaceLanguage());
		} else {
			$this->_aLanguages = $aLanguages;
		}
		
	}
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		
		$sField = $this->_sField;
		
		$aReturn = array();

		foreach((array)$oWDBasic->$sField as $sKey)
		{
			$aReturn[$sKey] = $this->_aLanguages[$sKey];
		}

		return $aReturn;

	}
	
}