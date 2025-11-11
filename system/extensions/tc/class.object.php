<?php 

class Ext_TC_Object {
	
	/**
	 * Dummyfunktion die auf CORE ein Array mit Sprachen liefert.
	 * @param type $bSelect
	 * @return type 
	 */
	public static function getLanguages($bSelect) {
		
		if($bSelect) {
			$aLanguages = Ext_TC_Language::getSelectOptions();

			// Auswahl für Core begrenzen! ~dg
			$aAllow = array('de', 'en', 'es');
			foreach($aLanguages as $sIso => $sLang) {
				if(!in_array($sIso, $aAllow)) {
					unset($aLanguages[$sIso]);
				}
			}
			
		} else {
			$aLanguages = Ext_TC_Language::getList();
		}
		
		return $aLanguages;
		
	}

	public static function getCorrespondenceLanguages($bReturnFormat = false) {

		$aLanguages = Ext_TC_Language::getSelectOptions();

		$aReturn = array();

		// Auswahl für Core begrenzen! ~dg
		$aAllow = array('de', 'en', 'es');

		foreach($aLanguages as $sIso => $sLang)
		{
			if(!in_array($sIso, $aAllow))
			{
				unset($aLanguages[$sIso]);
			}
			else if($bReturnFormat)
			{
				$aReturn[] = array(
					'iso'	=> $sIso,
					'name'	=> $sLang
				);
			}
		}

		if($bReturnFormat)
		{
			return $aReturn;
		}

		return $aLanguages;
	}

	/**
	 * Dummyfunktion für die Auflistung der Subobjects (Schulen, Büros)
	 * @param type $bSelect 
	 */
	public static function getSubObjects($bSelect) {
		
		if($bSelect) {
			$aObjects = array(1=>'Object 1', 2=>'Object 2');
		}
		
		return $aObjects;
		
	}

	/**
	 * Büro
	 * @return type 
	 */
	public static function getSubObjectLabel(bool $bPlural=true) {
		
		if($bPlural === true) {
			$sLabel = L10N::t('Objekte');
		} else {
			$sLabel = L10N::t('Objekt');
		}
		
		return $sLabel;
		
	}
	
	/**
	 * Liefert den Namen des Objektes
	 */
	public static function getName()
	{
		return 'Thebing Core - Development';
	}
	
	/**
	 * Soll die Standard-E-Mail-Adresse anzeigen. Dabei muss unterschieden werden, 
	 *	ob in der jeweiligen Einstellung ein E-Mail Account ausgewählt ist oder nicht.
	 * @return string 
	 */
	public static function getStandardEmailAddress()
	{
		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$sStandardEMailAddress = $oConfig->getValue('standard_emailaddress');
		return $sStandardEMailAddress;
	}
	
	/**
	 * Liefert die Standard-E-Mail-Account
	 * @return int 
	 */
	public static function getStandardEmailAccount()
	{
		return 0;
	}
	
	/**
	 * Liefert alle User
	 */
	public static function getUsers()
	{
		$oUser = new Ext_TC_User;
		$aUsers = $oUser->getArrayList();
		return $aUsers;
	}
		
	/**
	 * Liefert die E-Mail-Adresse für generelle Fehlermeldungen
	 * 
	 * @return string
	 */
	public static function getErrorEmailAddress() {
		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$sStandardEmailAdress = $oConfig->getValue('error_emailaddress');
		return $sStandardEmailAdress;
	}

	/**
	 * System-Typen für Kontakte
	 *
	 * 'system_type' => [
	 * 		'label' => 'Schulkontakt',
	 * 		'tab' => Tab::class, (optional) // Eigene Dialog-Felder für diese Kontaktart
	 * 		'global' => true (optional) // globale Kontakte können zentral angelegt werden
	 * ]
	 *
	 * @return array
	 */
	public static function getContactSystemTypes(): array {
		return [];
	}

	static public function getCommunicationName() {
		return 'Dummy object';
	}
	
}
